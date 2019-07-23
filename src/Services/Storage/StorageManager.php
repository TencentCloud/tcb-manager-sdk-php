<?php


namespace TcbManager\Services\Storage;

use Exception;
use stdClass;
use GuzzleHttp\Exception\GuzzleException;
use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;
use TcbManager\Utils;
use TencentCloudClient\Utils as TCUtils;
use TencentCloudClient\TCCosClient;
use TencentCloudClient\Exception\TCException;
use Webmozart\PathUtil\Path;

class StorageManager extends AbstractService
{
    /**
     * @var string API endpoint
     */
    protected $endpoint = Endpoint::COS;

    /**
     * @var string API version
     */
    protected $version = "2018-11-27";

    /**
     * @var string 实例Id
     */
    public $bucket;

    /**
     * @var string CDN加速域名
     */
    public $cdnDomain;

    /**
     * @var string AppId
     */
    public $appId;
    /**
     * @var string 实例状态
     */
    public $status;

    protected $region = "ap-shanghai";
    protected $secretId;
    protected $secretKey;
    protected $token;

    protected $api;

    public function __construct(TcbManager $tcb, stdClass $instanceInfo)
    {
        parent::__construct($tcb);

        $this->region = $instanceInfo->Region;
        $this->bucket = $instanceInfo->Bucket;
        $this->cdnDomain = $instanceInfo->CdnDomain;
        $this->appId = $instanceInfo->AppId;

        $this->api = new TCCosClient(
            $this->region,
            $this->bucket,
            $this->appId,
            $tcb->getApi()->getCredential()
        );
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws \TcbManager\Exceptions\EnvException
     */
    private function calcFileId(string $key)
    {
        $envId = $this->tcb->currentEnvironment()->getId();
        return "cloud://$envId.$this->bucket/$key";
    }

    /**
     * @param string $key
     * @param array $options
     *
     * @return string
     * @throws Exception
     */
    public function getTemporaryObjectUrl(string $key, array $options = [])
    {
        $expires = isset($options["expires"])
            ? $options["expires"]
            : "10 minutes";

        // 直接计算COS签名的URL
        // $url = $this->api->calcObjectUrl($key, $this->cdnDomain, strtotime($expires) - strtotime("now"));
        $result = $this->tcb->currentEnvironment()->getTcb()->getStorage()->getTempFileURL([
            "fileList" => [
                ["fileID" => $this->calcFileId($key), "maxAge" => strtotime($expires) - strtotime("now")]
            ]
        ]);
        $file = $result["fileList"][0];
        if (isset($file["code"]) && $file["code"] === "STORAGE_FILE_NONEXIST") {
            throw new Exception("ObjectNotExists");
        }

        return $file["tempFileURL"];
    }

    /**
     * PUT Object
     *
     * 接口请求可以将本地的对象（Object）上传至指定存储桶中。该操作需要请求者对存储桶有写入权限。
     *
     * @link https://cloud.tencent.com/document/api/436/7749
     *
     * @param string $key
     * @param string $path
     * @param array $options
     *
     * @return object
     * @throws GuzzleException
     * @throws Exception
     * @throws TCException
     */
    public function putObject(string $key, string $path, array $options = [])
    {
        $path = $path === "" ? getcwd() : $path;

        if (!file_exists($path)) {
            throw new Exception("PathNotExists: $path");
        }

        if (is_dir($path)) {
            $filePath = Path::join($path, $key);
        } else {
            $filePath = $path;
        }

        if (!Path::isAbsolute($filePath)) {
            $filePath = Path::join(getcwd(), $filePath);
        };

        $prefix = isset($options["prefix"]) ? $options["prefix"] : "";

        if (!TCUtils::prefix_valid($prefix)) {
            throw new Exception("ValidPrefix: $prefix");
        }

        $result = $this->tcb->currentEnvironment()->getTcb()->getStorage()->uploadFile([
            "cloudPath" => TCUtils::key_join($prefix, $key),
            "fileContent" => fopen($filePath, "r")
        ]);

        return (object)[
            "RequestId" => $result["requestId"]
        ];
    }

    /**
     * @param string $key
     *
     * @return object
     * @throws \TcbManager\Exceptions\EnvException
     * @throws \TencentCloudBase\Utils\TcbException
     */
    public function deleteObject(string $key)
    {
        $result = $this->tcb->currentEnvironment()->getTcb()->getStorage()->deleteFile([
            "fileList" => [
                $this->calcFileId($key)
            ],
        ]);

        return (object)[
            "RequestId" => $result["requestId"]
        ];
    }

    /**
     * GET Object
     *
     * 接口请求可以在 COS 的存储桶中将一个文件（对象）下载至本地。该操作需要请求者对目标对象具有读权限或目标对象对所有人都开放了读权限（公有读）。
     *
     * @link https://cloud.tencent.com/document/api/436/7753
     *
     * @param string $key
     * @param string $target
     *
     * @return object
     * @throws GuzzleException
     * @throws Exception
     */
    public function getObject(string $key, string $target)
    {
        if (!Path::isAbsolute($target)) {
            $target = Path::join(getcwd(), $target);
        };
        Utils::tryMkdir(pathinfo($target, PATHINFO_DIRNAME));

        $result = $this->tcb->currentEnvironment()->getTcb()->getStorage()->downloadFile([
            "fileID" => $this->calcFileId($key),
            "tempFilePath" => $target
        ]);
        if (isset($result["code"]) && $result["code"] === "STORAGE_FILE_NONEXIST") {
            throw new Exception("ObjectNotExists");
        }
    }

    /**
     * 查询对象列表
     *
     * @link https://cloud.tencent.com/document/product/436/7734
     *
     * @param array $query
     *        string $query.prefix 对象键匹配前缀，限定响应中只包含指定前缀的对象键
     *        string $query.delimiter 一个字符的分隔符，用于对对象键进行分组
     *        string $query.encoding-type 规定返回值的编码方式，可选值：url
     *        string $query.marker 所有列出条目从 marker 开始
     *        string $query.max-keys 单次返回最大的条目数量，默认值为1000，最大为1000
     * @return object
     * @throws GuzzleException
     * @throws TCException
     */
    public function listObjects(array $query = [])
    {
        $query["max-keys"] = isset($query["max-keys"]) ? $query["max-keys"] : 1000;
        $result = $this->api->request("GET", "/", [
            "query" => $query,
            "headers" => [
            ]
        ]);

        $result->Body->Prefix = is_string($result->Body->Prefix)
            ? $result->Body->Prefix : "";

        $result->Body->Marker = is_string($result->Body->Marker)
            ? $result->Body->Marker : "";

        $result->Body->IsTruncated = $result->Body->IsTruncated === "true"
            ? true : false;

        if (property_exists($result->Body, "Contents")) {
            if (is_object($result->Body->Contents)) {
                $result->Body->Contents = [$result->Body->Contents];
            }
        }

         if (property_exists($result->Body, "CommonPrefixes")) {
             if (is_object($result->Body->CommonPrefixes)) {
                 $result->Body->CommonPrefixes = [$result->Body->CommonPrefixes];
             }
         }

        return $result;
    }

    /**
     * 上传文件
     *
     * @param string $src
     * @param array $options
     *
     * @throws Exception
     * @throws GuzzleException
     * @throws TCException
     */
    public function upload(string $src, array $options = [])
    {
        $prefix = isset($options["prefix"]) ? $options["prefix"] : "";

        if (!TCUtils::prefix_valid($prefix)) {
            throw new Exception("ValidPrefix: $prefix");
        }
        if (!Path::isAbsolute($src)) {
            $src = Path::join(getcwd(), $src);
        };

        $files = Utils::listFiles($src);

        foreach ($files as $file) {
            $key = Path::makeRelative($file, $src);
            $this->putObject($key, $src, $options);
        }
    }

    /**
     * 下载文件
     *
     * @param string $dst
     * @param array $options
     *
     * @throws GuzzleException
     * @throws TCException
     * @throws Exception
     */
    public function download(string $dst, array $options = [])
    {
        $prefix = isset($options["prefix"]) ? $options["prefix"] : "";

        if (!TCUtils::prefix_valid($prefix)) {
            throw new Exception("ValidPrefix: $prefix");
        }

        if (!Path::isAbsolute($dst)) {
            $dst = Path::join(getcwd(), $dst);
        };

        $this->walk($prefix, function ($content) use ($prefix, $dst) {
            if (TCUtils::key_is_real($content->Key)) {
                $path = Path::join($dst, str_replace($prefix, "", $content->Key));
                $this->getObject($content->Key, $path);
            }
        }, $options);
    }

    /**
     * 删除文件
     *
     * @param array $options
     *
     * @throws GuzzleException
     * @throws TCException
     * @throws Exception
     */
    public function remove(array $options = [])
    {
        $prefix = isset($options["prefix"]) ? $options["prefix"] : "";

        if (!TCUtils::prefix_valid($prefix)) {
            throw new Exception("ValidPrefix: $prefix");
        }

        $this->walk($prefix, function ($content) {
            $this->deleteObject($content->Key);
        }, []);
    }

    /**
     * 文件列表
     *
     * @param array $options
     *
     * @return array
     * @throws GuzzleException
     * @throws TCException
     * @throws Exception
     */
    public function keys(array $options = [])
    {
        $prefix = isset($options["prefix"]) ? $options["prefix"] : "";

        if (!TCUtils::prefix_valid($prefix)) {
            throw new Exception("ValidPrefix: $prefix");
        }

        $keys = [];
        $this->walk($prefix, function ($content) use (&$keys) {
            array_push($keys, $content->Key);
        }, []);
        return $keys;
    }

    /**
     * 遍历文件
     *
     * @param string $prefix
     * @param callable $callback
     * @param array $options
     *
     * @throws GuzzleException
     * @throws TCException
     */
    private function walk(
        string $prefix,
        callable $callback,
        array $options = []
    )
    {
        $delimiter = isset($options['delimiter']) ? $options['delimiter'] : '';
        $marker = isset($options['marker']) ? $options['marker'] : '';
        $maxKeys = isset($options['maxKeys']) ? $options['maxKeys'] : 5;

        $result = $this->listObjects([
            "prefix" => $prefix,
            "delimiter" => $delimiter,
            "marker" => $marker,
            "max-keys" => $maxKeys
        ]);

        if (property_exists($result->Body, "Contents")) {
            $contents = $result->Body->Contents;
            array_map(function ($content) use ($callback) {
                $callback($content);
            }, $contents);
        }

        if ($result->Body->IsTruncated) {
            $this->walk($prefix, $callback, [
                'marker' => $result->Body->NextMarker,
                'maxKeys' => $maxKeys
            ]);
        }
    }
}
