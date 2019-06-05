<?php


namespace TcbManager\Services\Database;


use Exception;
use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;
use TcbManager\Utils;
use TencentCloudClient\Exception\TCException;
use Webmozart\PathUtil\Path;


class DatabaseManager extends AbstractService
{
    const DEFAULT_MGO_OFFSET = 0;
    const DEFAULT_MGO_LIMIT = 100;

    /**
     * @var string API endpoint
     */
    protected $endpoint = Endpoint::FLEXDB;

    /**
     * @var string API version
     */
    protected $version = "2018-11-27";

    /**
     * @var string
     */
    protected $region = "";

    /**
     * @var string 实例Id
     */
    public $instanceId;

    /**
     * @var string 实例状态
     */
    public $status;

    public function __construct(TcbManager $tcb, \stdClass $instanceInfo)
    {
        parent::__construct($tcb);

        $this->instanceId = $instanceInfo->InstanceId;
        $this->region = $instanceInfo->Region;
        $this->status = $instanceInfo->Status;
    }

    /**
     * 检查表是否存在
     * @param string $tableName
     * @return object
     */
    public function checkTableExists(string $tableName)
    {
        try {
            $result = $this->request("DescribeTable", [
                "Tag" => $this->instanceId,
                "TableName" => $tableName
            ]);
            return Utils::fromArrayToObject([
                "RequestId" => $result->RequestId,
                "Exists" => true
            ]);
        } catch (TCException $e) {
            return Utils::fromArrayToObject([
                "RequestId" => $e->getERequestId(),
                "Exists" => false
            ]);
        }
    }

    /**
     * 创建表/集合 - 如果表存在，则会报异常
     *
     * @param string $tableName - 表/集合名
     * @return mixed
     * @throws TCException
     */
    public function createTable(string $tableName)
    {
        return $this->request("CreateTable", [
            "Tag" => $this->instanceId,
            "TableName" => $tableName
        ]);
    }

    /**
     * 创建表/集合 - 如果表存在，则会报异常
     *
     * @param string $tableName - 表/集合名
     * @return mixed
     * @throws TCException
     */
    public function createTableIfNotExists(string $tableName)
    {
        $existsResult = $this->checkTableExists($tableName);
        if (!$existsResult->Exists) {
            $result = $this->createTable($tableName);
            return Utils::fromArrayToObject([
                "RequestId" => $result->RequestId,
                "IsCreated" => true,
                "ExistsResult" => $existsResult
            ]);
        }
        else {
            return Utils::fromArrayToObject([
                "RequestId" => "",
                "IsCreated" => false,
                "ExistsResult" => $existsResult
            ]);
        }
    }

    /**
     * 删除表/集合 - 如果表/集合不存在，也会正常返回
     *
     * @param string $tableName - 表/集合名
     * @return mixed
     * @throws Exception
     */
    public function deleteTable(string $tableName)
    {
        return $this->request("DeleteTable", [
            "Tag" => $this->instanceId,
            "TableName" => $tableName
        ]);
    }

    /**
     * 更新表/集合
     *
     * 目前只支持修改索引信息
     *
     * 注意：
     *  1. 索引创建时如果已经存在，则会先删除再创建索引
     *  2. 因为一次接口调用可同时创建多个索引，所以可能部分索引创建失败，部分创建成功，接口报异常
     *
     * @param string $tableName
     * @param array $options
     * @return mixed
     * @throws TCException
     */
    public function updateTable(string $tableName, array $options)
    {
        return $this->request("updateTable", array_merge([
            "Tag" => $this->instanceId,
            "TableName" => $tableName,
        ], $options));
    }

    /**
     * 查询表详细信息
     * @param string $tableName
     * @return mixed
     * @throws TCException
     */
    public function describeTable(string $tableName)
    {
        return $this->request("DescribeTable", [
            "Tag" => $this->instanceId,
            "TableName" => $tableName
        ]);
    }

    /**
     * 查询所有表信息
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public function listTables(array $options = [])
    {
        if (!array_key_exists("MgoOffset", $options)) {
            $options["MgoOffset"] = self::DEFAULT_MGO_OFFSET;
        }
        if (!array_key_exists("MgoLimit", $options)) {
            $options["MgoLimit"] = self::DEFAULT_MGO_LIMIT;
        }

        return $this->request("ListTables", array_merge([
            "Tag" => $this->instanceId,
        ], $options));
    }

    /**
     * 检查索引是否存在
     * @param string $tableName
     * @param string $indexName
     * @return object
     * @throws Exception, TCException
     */
    public function checkIndexExists(string $tableName, string $indexName)
    {
        $result = $this->describeTable($tableName);
        $exists = Utils::arraySearch(
            $result->Indexes,
            "Name",
            $indexName
        );
        return Utils::fromArrayToObject([
            "RequestId" => $result->RequestId,
            "Exists" => $exists
        ]);
    }

    /**
     * @param string $collectionName
     * @param array $file
     * @param array $options
     *
     * @return mixed
     * @throws Exception
     * @throws TCException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \TcbManager\Exceptions\EnvException
     */
    public function import(string $collectionName, array $file, array $options = [])
    {
        if (array_key_exists("FilePath", $file)) {
            $tmp = "tmp/db-imports/";
            if (array_key_exists("ObjectKeyPrefix", $options)) {
                $tmp = $options["ObjectKeyPrefix"];
                unset($options["ObjectKeyPrefix"]);
            }
            $filePath = Path::join($tmp, pathinfo($file["FilePath"], PATHINFO_BASENAME));
            $this->tcb->getEnvironmentManager()->getCurrent()
                ->getStorageManager()
                ->putObject($filePath, $file["FilePath"]);
            $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        } else if (array_key_exists("ObjectKey", $file)) {
            unset($options["ObjectKeyPrefix"]);
            $filePath = $file["ObjectKey"];
            $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        } else {
            throw new Exception("Miss file.filePath or file.objectKey");
        }

        if (array_key_exists("FileType", $file)) {
            $fileType = $file["FileType"];
        }

        return $this->tcb->getEnvironmentManager()->getCurrent()->requestWithEnv(
            "DatabaseMigrateImport",
            array_merge([
                "CollectionName" => $collectionName,
                "FilePath" => $filePath,
                "FileType" => $fileType,
            ], $options)
        );
    }

    /**
     *
     * @link http://10.198.144.46/document/product/705/17835?!document=1&!preview
     *
     * @param string $collectionName
     * @param array $file
     * @param array $options
     * @return mixed
     * @throws TCException
     * @throws Exception,\TcbManager\Exceptions\EnvException
     */
    public function export(string $collectionName, array $file, array $options)
    {
        if (array_key_exists("ObjectKey", $file)) {
            $filePath = $file["ObjectKey"];
            $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        } else {
            throw new Exception("Miss file.filePath or file.objectKey");
        }

        if (array_key_exists("FileType", $file)) {
            $fileType = $file["FileType"];
        }

        return $this->tcb->getEnvironmentManager()->getCurrent()->requestWithEnv(
            "DatabaseMigrateExport",
            array_merge([
                "CollectionName" => $collectionName,
                "FilePath" => $filePath,
                "FileType" => $fileType,
            ], $options)
        );
    }

    /**
     * @param int $jobId
     * @return mixed
     * @throws TCException
     * @throws Exception,\TcbManager\Exceptions\EnvException
     */
    public function migrateStatus(int $jobId)
    {
        return $this->tcb->getEnvironmentManager()->getCurrent()->requestWithEnv(
            "DatabaseMigrateQueryInfo",
            array_merge([
                "JobId" => $jobId,
            ])
        );
    }

    /**
     * 查询DB的数据存储分布
     *
     * @link http://10.198.144.46/document/product/705/31653
     *
     * @return mixed
     * @throws TCException
     * @throws Exception,\TcbManager\Exceptions\EnvException
     */
    public function distribution()
    {
        return $this->tcb->getEnvironmentManager()->getCurrent()->requestWithEnv(
            "DescribeDbDistribution"
        );
    }
}
