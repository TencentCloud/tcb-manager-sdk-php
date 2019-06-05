<?php


namespace TcbManager\Services\Functions;

use Exception;
use stdClass;

use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;
use TcbManager\Utils;

class FunctionManager extends AbstractService
{
    /**
     * @var string API endpoint
     */
    protected $endpoint = Endpoint::SCF;

    /**
     * @var string API version
     */
    protected $version = "2018-04-16";

    /**
     * @var string 实例Id
     */
    public $namespace;

    /**
     * @var string
     */
    protected $region = "ap-shanghai";

    /**
     * @var string 实例状态
     */
    public $status;

    public function __construct(TcbManager $tcb, \stdClass $instanceInfo)
    {
        parent::__construct($tcb);

        $this->namespace = $instanceInfo->Namespace;
        $this->region = $instanceInfo->Region;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param array &$code
     * @return string
     * @throws Exception
     */
    static function makeZipFile(array &$code): string {
        $zipFile = "";
        if (array_key_exists("ZipFile", $code)) {
            if (!is_string($code["ZipFile"])) {
                throw new Exception("Argument ZipFile must be a string");
            }
            $zipFile = $code["ZipFile"];
        } else if (array_key_exists("ZipFilePath", $code)) {
            $zipFile = Utils::makeZipCodeFromZipFile($code["ZipFilePath"]);
            unset($code["ZipFilePath"]);
        // } else if (array_key_exists("ZipFileStream", $code)) {
        //     $zipFile = Utils::makeZipCodeFromZipFileStream($code["ZipFileStream"]);
        //     unset($code["ZipFileStream"]);
        } else if (array_key_exists("SourceFilePath", $code)) {
            $zipFile = Utils::makeZipCodeBySourceFile($code["SourceFilePath"]);
            unset($code["SourceFilePath"]);
        }
        return $zipFile;
    }

    /**
     * 创建函数
     *
     * 注意：该函数可能调用成功，但是创建函数失败
     *
     * @link https://cloud.tencent.com/document/api/583/18586
     *
     * @param string $functionName
     * @param array $code
     * @param string $handler
     * @param string $runtime
     * @param array  $options
     * @return stdClass
     * @throws Exception
     */
    public function createFunction(
        string $functionName,
        array $code,
        string $handler,
        string $runtime,
        array $options = []
    ): stdClass {
        if (empty($code)) {
            throw new Exception("Argument must has some invalid field!");
        }

        return $this->request("createFunction", array_merge(
            [
                "Namespace" => $this->namespace,
                "Role" => "TCB_QcsRole",
                "Stamp" => "MINI_QCBASE",
                "UseGpu" => "FALSE",
                "InstallDependency" => "TRUE",
                "Runtime" => $runtime,
                "MemorySize" => 256,
                "Timeout" => 3,
                "FunctionName" => $functionName,
                "Code" => array_merge([
                    // 注释掉的部分通过 $code 透传
                    // "CosBucketRegion" => $CosBucketRegion,
                    // "CosBucketName" => $CosBucketName,
                    // "CosObjectName" => $CosObjectName,
                    // "TempCosObjectName" => $TempCosObjectName,
                    // "DemoId" => $DemoId,
                    "ZipFile" => self::makeZipFile($code)
                ], $code),
                "Handler" => $handler,
            ],
            $options
        ));
    }

    /**
     * 删除函数
     *
     * 该接口根据传入参数删除函数。
     *
     * @link https://cloud.tencent.com/document/api/583/18585
     *
     * @param string $functionName
     * @return stdClass
     * @throws Exception
     */
    public function deleteFunction(string $functionName): stdClass
    {
        return $this->request("DeleteFunction", [
            "Namespace" => $this->namespace,
            "FunctionName" => $functionName
        ]);
    }

    /**
     * 更新函数代码
     *
     * 注意：该函数可能调用成功，但是更新函数失败
     *
     * @link https://cloud.tencent.com/document/api/583/18581
     *
     * @param string $functionName
     * @param array $code
     * @param string $handler
     * @param array $options
     * @return stdClass
     * @throws Exception
     */
    public function updateFunctionCode(
        string $functionName,
        array $code,
        string $handler,
        array $options = []
    ): stdClass {
        return $this->request("UpdateFunctionCode", array_merge(
            [
                "Namespace" => $this->namespace,
                "FunctionName" => $functionName,
                "InlineZipFile" => self::makeZipFile($code),
                "Handler" => $handler
            ],
            $options
        ));
    }

    /**
     * 更新函数配置
     *
     * 该接口根据传入参数更新函数配置。
     *
     * @link https://cloud.tencent.com/document/api/583/18580
     *
     * @param string $functionName
     * @param array $options
     * @return stdClass
     * @throws Exception
     */
    public function updateFunctionConfiguration(
        string $functionName,
        array $options = []
    ): stdClass {
        return $this->request("UpdateFunctionConfiguration", array_merge(
            [
                "Namespace" => $this->namespace,
                "FunctionName" => $functionName
            ],
            $options
        ));
    }

    /**
     * 获取函数列表
     *
     * 该接口根据传入的查询参数返回相关函数信息。
     *
     * @link https://cloud.tencent.com/document/api/583/18582
     *
     * @return stdClass
     * @throws Exception
     */
    public function listFunctions(): stdClass
    {
        return $this->request("ListFunctions", [
            "Namespace" => $this->namespace
        ]);
    }

    /**
     * 获取函数详细信息
     *
     * 该接口获取某个函数的详细信息，包括名称、代码、处理方法、关联触发器和超时时间等字段。
     *
     * @link https://cloud.tencent.com/document/api/583/18584
     *
     * @param $functionName
     * @return stdClass
     * @throws Exception
     */
    public function getFunction(string $functionName): stdClass
    {
        return $this->request("GetFunction", [
            "Namespace" => $this->namespace,
            "FunctionName" => $functionName
        ]);
    }

    /**
     * 运行函数
     *
     * @link https://cloud.tencent.com/document/api/583/17243
     *
     * @param string $functionName
     * @param array $options
     * @return stdClass
     * @throws Exception
     */
    public function invoke(string $functionName, array $options = []): stdClass
    {
        return $this->request("Invoke", array_merge(
            [
                "Namespace" => $this->namespace,
                "FunctionName" => $functionName
            ],
            $options
        ));
    }

    /**
     * 获取函数运行日志
     *
     * @link https://cloud.tencent.com/document/api/583/18583
     *
     * @param string $functionName
     * @param array $options
     * @return stdClass
     * @throws Exception
     */
    public function getFunctionLogs(string $functionName, array $options = []): stdClass
    {
        return $this->request("GetFunctionLogs", array_merge(
            [
                "Namespace" => $this->namespace,
                "FunctionName" => $functionName
            ],
            $options
        ));
    }
}
