<?php


namespace TcbManager\Services\Database;


use Exception;
use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;
use TcbManager\Utils;
use TencentCloudBase\Database\Db;
use TencentCloudClient\Exception\TCException;
use TcbManager\Exceptions\EnvException;
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
     * @return Db
     * @throws EnvException
     */
    public function db()
    {
        return $this->tcb->currentEnvironment()->getTcb()->getDatabase();
    }

    /**
     * 检查表是否存在
     * @param string $collectionName
     * @return object
     */
    public function checkCollectionExists(string $collectionName)
    {
        try {
            $result = $this->request("DescribeTable", [
                "Tag" => $this->instanceId,
                "TableName" => $collectionName
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
     * @param string $collectionName - 表/集合名
     * @return mixed
     * @throws TCException
     */
    public function createCollection(string $collectionName)
    {
        return $this->request("CreateTable", [
            "Tag" => $this->instanceId,
            "TableName" => $collectionName
        ]);
    }

    /**
     * 创建表/集合 - 如果表存在，则会报异常
     *
     * @param string $collectionName - 表/集合名
     * @return mixed
     * @throws TCException
     */
    public function createCollectionIfNotExists(string $collectionName)
    {
        $existsResult = $this->checkCollectionExists($collectionName);
        if (!$existsResult->Exists) {
            $result = $this->createCollection($collectionName);
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
     * @param string $collectionName - 表/集合名
     * @return mixed
     * @throws Exception
     */
    public function deleteCollection(string $collectionName)
    {
        return $this->request("DeleteTable", [
            "Tag" => $this->instanceId,
            "TableName" => $collectionName
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
     * @param string $collectionName
     * @param array $options
     * @return mixed
     * @throws TCException
     */
    public function updateCollection(string $collectionName, array $options)
    {
        return $this->request("updateTable", array_merge([
            "Tag" => $this->instanceId,
            "TableName" => $collectionName,
        ], $options));
    }

    /**
     * 查询表详细信息
     * @param string $collectionName
     * @return mixed
     * @throws TCException
     */
    public function describeCollection(string $collectionName)
    {
        return $this->request("DescribeTable", [
            "Tag" => $this->instanceId,
            "TableName" => $collectionName
        ]);
    }

    /**
     * 查询所有表信息
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public function listCollections(array $options = [])
    {
        if (!array_key_exists("MgoOffset", $options)) {
            $options["MgoOffset"] = self::DEFAULT_MGO_OFFSET;
        }
        if (!array_key_exists("MgoLimit", $options)) {
            $options["MgoLimit"] = self::DEFAULT_MGO_LIMIT;
        }

        $result = $this->request("ListTables", array_merge([
            "Tag" => $this->instanceId,
        ], $options));

        // 和TCB一致
        $result->Collections = $result->Tables;
        unset($result->Tables);
        foreach ($result->Collections as $collection) {
            $collection->CollectionName = $collection->TableName;
            unset($collection->TableName);
        }

        return $result;
    }

    /**
     * 检查索引是否存在
     * @param string $collectionName
     * @param string $indexName
     * @return object
     * @throws Exception, TCException
     */
    public function checkIndexExists(string $collectionName, string $indexName)
    {
        $result = $this->describeCollection($collectionName);
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
