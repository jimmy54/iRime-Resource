<?php
require_once __DIR__ . '/vendor/autoload.php';
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use OSS\OssClient;
use OSS\Core\OssException;


class Package
{
    const SCHEMA_PATH = __DIR__ . "/../../iRime方案/iRime云方案";
    const ZIP_PATH = __DIR__ . "/../../iRime方案/iRime云方案/tmp";
    const LAST_UPDATE_SCHEMA = __DIR__."/last_update_schema.json";

    // 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录RAM控制台创建RAM账号。
    private $accessKeyId = "LTAIO71m45cSCjAG";
    private $accessKeySecret = "vkYDBDZRZTkmbpuvBalWKb77zLZSAp";
// Endpoint以杭州为例，其它Region请按实际情况填写。
    private $endpoint = "oss-cn-hangzhou.aliyuncs.com";
// 设置存储空间名称。
    private $bucket = "irime-test";

    private $uploadSchemaUrl = "http://api.5koon.com/web/index.php?r=schema/upload-schemas-detail";

    public function run()
    {
        if (!file_exists(self::ZIP_PATH)) {
            mkdir(self::ZIP_PATH, 0700);
        }

        //get schema files
        $schemaArr = $this->directory_map(self::SCHEMA_PATH, 2);
        //filter tmp dir
        unset($schemaArr['tmp']);

        if (!is_array($schemaArr)) {
            echo "\nshcema direstor error:" . print_r($schemaArr, true);
            return;
        }

        $lastUpdateSchemas = $this->lastUpdateSchema($schemaArr);

        //need update schema
        $updateSchemaArr = $this->needUpdateSchema($lastUpdateSchemas, $schemaArr);
        foreach ($updateSchemaArr as $key => $value) {

            //schema to json
            $pos = strpos($key, "@");
            $schemaName = substr($key, 0, $pos);
            $schemaJson = $this->schemaToJson(self::SCHEMA_PATH . "/" . $key . "/" . $schemaName . ".schema.yaml");

            //update to mysql
            if ($this->uploadSchema($schemaJson)){
                //zip schema file
                $this->packageZip($value, $key);

                //upload to oss
                $this->uploadToOSS($key.".zip", self::ZIP_PATH."/".$key.".zip");
            }else{
                echo $schemaName."upload fail";
            }
        }
    }

    private function lastUpdateSchema($schemaArr)
    {
        if (!file_exists(self::LAST_UPDATE_SCHEMA)){
            $lasUpdateSchema = array();
            foreach ($schemaArr as $key => $value){
                $pos = strpos($key, "@");
                $schemaName = substr($key, 0, $pos);

                try {
                    $schemaDetail = Yaml::parseFile(self::SCHEMA_PATH . "/" . $key . "/" . $schemaName . ".schema.yaml");
                    $schemaHead = $schemaDetail['schema'];
                    $lasUpdateSchema[$key] = $schemaHead;
                } catch (ParseException $e) {
                    echo $schemaName.":".$e->getMessage()."\n"; //
                }
            }
            file_put_contents(self::LAST_UPDATE_SCHEMA, json_encode($lasUpdateSchema));
            return $lasUpdateSchema;
        }
        return json_decode(file_get_contents(self::LAST_UPDATE_SCHEMA), true);
    }


    private function needUpdateSchema($schemas, $schemaDir)
    {
        $ret = array();
        $filter = array("tmp");
        foreach ($schemaDir as $key => $value){
            if (!isset($schemas[$key]) && !in_array($key, $filter)){
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    private function directory_map($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ($fp = @opendir($source_dir)) {
            $filedata = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            while (FALSE !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if ($file === '.' OR $file === '..' OR ($hidden === FALSE && $file[0] === '.')) {
                    continue;
                }
                is_dir($source_dir . $file) && $file .= DIRECTORY_SEPARATOR;
                if (($directory_depth < 1 OR $new_depth > 0) && is_dir($source_dir . $file)) {
                    $key = str_replace("/", "", $file);
                    $filedata[$key] = $this->directory_map($source_dir . $file, $new_depth, $hidden);
                } else {
                    $filedata[] = $file;
                }
            }
            closedir($fp);
            return $filedata;
        }
        return FALSE;
    }

    private function packageZip($files, $dir)
    {
        echo "flies:" . print_r($files, true) . "\ntoPath:" . self::ZIP_PATH . "/" . $dir . ".zip\n";
        $toPath = self::ZIP_PATH . "/" . $dir . ".zip";
        // create new archive
        $zipFile = new \PhpZip\ZipFile();
        try {

            foreach ($files as $file) {
                $zipFile->addFile(self::SCHEMA_PATH . "/" . $dir . "/" . $file);
            }

            $zipFile->saveAsFile($toPath)->close(); // close archive
        } catch (\PhpZip\Exception\ZipException $e) {
            // handle exception
        } finally {
            $zipFile->close();
        }
    }


    private function uploadToOSS($name, $filePath)
    {
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);

            $ossClient->uploadFile($this->bucket, $name, $filePath);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }


    private function uploadSchema($schemaJson)
    {
        return self::post($this->uploadSchemaUrl, array("schemas" => $schemaJson), 30);
    }

    private function schemaToJson($schemaFile)
    {
        try {
            $value = Yaml::parseFile($schemaFile);
        } catch (ParseException $e) {
            echo $e->getMessage(); //
        }
        return json_encode($value['schema']);
    }


    private function saveSchemaJson($schemaJson)
    {

    }


    public static function post($url, $postArr = '', $timeout = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($postArr != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postArr));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if(!$file_contents = curl_exec($ch)){
            echo "post error:".print_r(curl_error($ch), true);
        }
        echo $file_contents;
        curl_close($ch);
        return $file_contents;
    }
}

$p = new Package();
$p->run();


?>
