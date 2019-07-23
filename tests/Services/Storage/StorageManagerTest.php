<?php

namespace TcbManager\Tests\Services\Storage;

use Exception;
use GuzzleHttp\Client;
use TcbManager\Services\Storage\StorageManager;
use PHPUnit\Framework\TestCase;
use TcbManager\Tests\TestBase;
use TcbManager\Utils;
use TencentCloudClient\Exception\TCException;
use Webmozart\PathUtil\Path;

const DS = DIRECTORY_SEPARATOR;

class StorageManagerTest extends TestCase
{
    /**
     * @var StorageManager
     */
    private $storageManager;

    public function assertHasRequestId($result)
    {
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();

        $this->storageManager = TestBase::$tcb->getStorageManager();
        $this->storageManager->putObject("data/.gitkeep", __DIR__);
    }

    public function testGetTempObjectUrl()
    {
        $url = $this->storageManager->getTemporaryObjectUrl("data/data/data.txt");
        $this->assertEquals(200, (new Client())->get($url)->getStatusCode());
    }

    public function testGetTempObjectUrlWithNotExistsKey()
    {
        $this->expectException(Exception::class);
        $this->storageManager->getTemporaryObjectUrl("not-exists-key");
    }

    public function testPutObject()
    {
        $result = $this->storageManager->putObject("data/data/data.txt", __DIR__);
        // $result = $this->storageManager->putObject("data/data/data.txt", "");

        $this->assertHasRequestId($result);
       // $this->assertArrayHasKey("ETag", $result->Headers);

       $url = $this->storageManager->getTemporaryObjectUrl("data/data/data.txt");
       $this->assertTrue(!empty($url));

       $result = $this->storageManager->putObject("data/data/X|Y|Z.txt", __DIR__);
       $url = $this->storageManager->getTemporaryObjectUrl("data/data/X|Y|Z.txt");
       $this->assertTrue(!empty($url));
    }

    public function testDeleteObject()
    {
        $this->storageManager->putObject("data/trash/A.txt", __DIR__);

        $url = $this->storageManager->getTemporaryObjectUrl("data/trash/A.txt");

        $this->assertTrue(!empty($url));

        $result = $this->storageManager->deleteObject("data/trash/A.txt");
        $this->expectException(Exception::class);
        $url = $this->storageManager->getTemporaryObjectUrl("data/trash/A.txt");
    }

    public function testGetObject()
    {
        $key = "data/.gitkeep";
        $target = __DIR__ . "/data/download/data/.gitkeep";

        $result = $this->storageManager->getObject($key, $target);
        $this->assertTrue(true);
    }

    public function testGetObjectNotExists()
    {
        $key = "not-exists-key";
        $target = __DIR__ . "/data/download/data/not-exists-key";
        $this->expectException(Exception::class);
        $result = $this->storageManager->getObject($key, $target);
        $this->assertTrue(true);
    }

    public function testListObjects()
    {
        $prefix = "";
        $delimiter = "";
        $marker = 0;
        $maxKeys = 100;
        $result = $this->storageManager->listObjects([
            "prefix" => $prefix,
            "delimiter" => $delimiter,
            "marker" => $marker,
            "max-keys" => $maxKeys
        ]);
        $this->assertHasRequestId($result);
    }

    public function testUploadNotExistsDir()
    {
        $this->expectException(Exception::class);
        $this->storageManager->upload(__DIR__."/not_exists", ["prefix" => "not_exists/"]);
    }

    public function testUpload()
    {
        $this->storageManager->remove(["prefix" => "upload/"]);
        $keys = $this->storageManager->keys(["prefix" => "upload/"]);
        $this->assertEmpty(array_diff([], $keys));

        $dir = Path::join(__DIR__, "data/tmp");
        $files = [
            "upload/index.js",
            "upload/.gitignore",
            "upload/文档.doc",
            "upload/lib/index.js",
            "upload/src/a.js",
            "upload/src/b.js",
            "upload/src/b.js",
            "upload/src/转义%20%26%3D%40.js",
        ];
        $this->mkEmptyFiles($dir, $files);

        $this->storageManager->upload(Path::join($dir, "upload"), [
            "prefix" => "upload/"
        ]);

        // 相对路径版本
        // $this->storageManager->upload("data/tmp/upload", [
        //     "prefix" => "upload/"
        // ]);

        $keys = $this->storageManager->keys(["prefix" => "upload"]);

        $this->assertEmpty(array_diff($files, $keys));

        $this->storageManager->download(Path::join($dir, "download"), [
            "delimiter" => "",
            "prefix" => "upload/",
        ]);

        // 相对路径版本
        // $this->storageManager->download("data/tmp/download", [
        //     "delimiter" => "",
        //     "prefix" => "upload/",
        // ]);

        $this->storageManager->remove(["prefix" => "upload/src/"]);
        $keys = $this->storageManager->keys(["prefix" => "upload/src/"]);
        $this->assertEmpty(array_diff([], $keys));
        $this->storageManager->remove(["prefix" => "upload/"]);
        $keys = $this->storageManager->keys(["prefix" => "upload/"]);
        $this->assertEmpty(array_diff([], $keys));
    }

    public function testDownload()
    {
        $this->assertTrue(true);
    }

    public function testRemove()
    {
        $this->assertTrue(true);
    }

    public function testKeys()
    {
        $this->assertTrue(true);
    }

    public function mkEmptyFiles(string $dir, array $files)
    {
        foreach ($files as $file) {
            Utils::tryMkdir(pathinfo(Path::join($dir, $file), PATHINFO_DIRNAME));
            file_put_contents(Path::join($dir, $file), "$file");
        }
    }
}
