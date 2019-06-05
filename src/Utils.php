<?php


namespace TcbManager;


use Exception;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;

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

    /**
     * @param $sourceFilePath
     * @param $targetZipFilePath
     * @return string
     * @throws Exception
     */
    public static function makeZipFile(string $sourceFilePath, string $targetZipFilePath)
    {
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
     */
    public static function makeZipCodeBySourceFile(string $sourceFilePath)
    {
        $zipFile = new ZipFile();

        try {
            if (is_dir($sourceFilePath)) {
                $zipFile->addDirRecursive($sourceFilePath);
            }
            else if (is_file($sourceFilePath)) {
                $zipFile->addFile($sourceFilePath);
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
     */
    public static function makeZipCodeFromZipFile(string $zipFilePath)
    {
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
}
