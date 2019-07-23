<?php


namespace TcbManager;


use Exception;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Webmozart\PathUtil\Path;

/**
 * Class Utils
 * @package TcbManager
 */
class Utils
{
    public static function fromObjectToArray(\stdClass $object)
    {
        return json_decode(json_encode($object), true);
    }

    public static function fromArrayToObject(array $array)
    {
        return (object) $array;
    }

    public static function fromJSONString($jsonString)
    {
        return json_decode($jsonString, false);
    }

    public static function toJSONString($object)
    {
        return json_encode($object, JSON_UNESCAPED_UNICODE);
    }

    public static function generateRandomString($length = 10)
    {
        $characters = [
            "a", "b", "c", "d", "e", "f", "g", "h",
            "i", "j", "k", "l","m", "n", "o", "p", "q", "r", "s",
            "t", "u", "v", "w", "x", "y","z", "A", "B", "C", "D",
            "E", "F", "G", "H", "I", "J", "K", "L","M", "N", "O",
            "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y","Z",
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
        ];
        $characterCount = count($characters);
        $randomString = "";
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $characterCount - 1)];
        }
        return $randomString;
    }

    public static function fromArrayToXml($array, &$xml_user_info) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml_user_info->addChild("$key");
                    static::fromArrayToXml($value, $subnode);
                }else{
                    $subnode = $xml_user_info->addChild("item$key");
                    static::fromArrayToXml($value, $subnode);
                }
            }else {
                $xml_user_info->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }

    public static function encode_url(string $url){
        $cs = unpack('C*', $url);
        $len = count($cs);
        $uri = '';
        for ($i = 1; $i <= $len; $i++) {
            $uri .= $cs[$i] > 127 ? '%'.strtoupper(dechex($cs[$i])) : $url{$i-1};
        }
        return $uri;
    }

    /**
     * @param $sourceFilePath
     * @param $targetZipFilePath
     * @return string
     * @throws Exception
     */
    public static function makeZipFile(string $sourceFilePath, string $targetZipFilePath)
    {
        if (!file_exists($sourceFilePath)) {
            throw new Exception("FILE_NOT_EXISTS: $sourceFilePath");
        }
        if (file_exists($targetZipFilePath)) {
            throw new Exception("Target file already exists!");
        }

        $zipFile = new ZipFile();

        try {
            if (is_dir($sourceFilePath)) {
                $zipFile->addDirRecursive($sourceFilePath);
            }
            else if (is_file($sourceFilePath)) {
                $zipFile->addFile($sourceFilePath);
            }
            $zipFile->saveAsFile($targetZipFilePath);
        }
        catch (ZipException $e) {
        }
        finally{
            $zipFile->close();
        }
    }

    /**
     * @param $sourceFilePath
     * @return string
     * @throws Exception
     */
    public static function makeZipCodeBySourceFile(string $sourceFilePath)
    {
        if (!Path::isAbsolute($sourceFilePath)) {
            $sourceFilePath = Path::join(getcwd(), $sourceFilePath);
        }
        if (!file_exists($sourceFilePath)) {
            throw new Exception("FILE_NOT_EXISTS: $sourceFilePath");
        }
        $zipFile = new ZipFile();

        try {
            if (is_dir($sourceFilePath)) {
                $zipFile->addDirRecursive($sourceFilePath);
            }
            else if (is_file($sourceFilePath)) {
                $zipFile->addFile($sourceFilePath);
            }
            else {
                throw new Exception("FILE_NOT_EXISTS: $sourceFilePath");
            }
            $rawZipArchiveBytes = $zipFile->outputAsString();
            return base64_encode($rawZipArchiveBytes);
        }
        catch (ZipException $e) {
        }
        finally{
            $zipFile->close();
        }
    }

    /**
     * @param $zipFilePath
     * @return string
     * @throws Exception
     */
    public static function makeZipCodeFromZipFile(string $zipFilePath)
    {
        if (!Path::isAbsolute($zipFilePath)) {
            $zipFilePath = Path::join(getcwd(), $zipFilePath);
        }
        if (!file_exists($zipFilePath)) {
            throw new Exception("FILE_NOT_EXISTS: $zipFilePath");
        }
        if (!is_file($zipFilePath)) {
            throw new Exception("NOT_FILE: $zipFilePath");
        }
        $zipFile = new ZipFile();

        try {
            $zipFile->openFile($zipFilePath);
            $rawZipArchiveBytes = $zipFile->outputAsString();
            return base64_encode($rawZipArchiveBytes);
        }
        catch (ZipException $e) {
        }
        finally{
            $zipFile->close();
        }
    }

    /**
     * @param array $a
     * @param string $key
     * @param $exceptValue
     *
     * @return bool
     * @throws Exception
     */
    public static function arraySearch(array $a, string $key, $exceptValue)
    {
        $exists = false;
        foreach ($a as $item) {
            if (is_array($item)) {
                if (array_key_exists($key, $item)&& $item[$key] == $exceptValue) {
                    $exists = true;
                }
            } else if (is_object($item)) {
                if (property_exists($item, $key)&& $item->$key == $exceptValue) {
                    $exists = true;
                }
            } else {
                throw new Exception("Item of array must be array of object");
            }
        }
        return $exists;
    }

    /**
     * @param string $path
     * @param array $files
     *
     * @throws Exception
     */
    private static function doListFiles(string $path, array &$files)
    {
        if (!file_exists($path)) {
            throw new Exception("PATH_NOT_EXISTS: $path");
        }

        if(is_dir($path))
        {
            $dp = dir($path);
            while ($file = $dp ->read()) {
                if($file !== "." && $file !== "..") {
                    static::doListFiles($path."/".$file, $files);
                }
            }
            $dp ->close();
        }

        if(is_file($path)) {
            $files[] = $path;
        }
    }

    /**
     * @param string $dir
     *
     * @return array
     * @throws Exception
     */
    public static function listFiles(string $dir)
    {
        $files = [];
        static::doListFiles($dir,$files);
        return $files;
    }

    /**
     * @param $dir
     */
    public static function tryMkdir(string $dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777,true);
        }
    }
}
