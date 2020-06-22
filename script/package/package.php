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
    private $accessKeyId;
    private $accessKeySecret;
// Endpoint以杭州为例，其它Region请按实际情况填写。
    private $endpoint;
// 设置存储空间名称。
    private $bucket;

    private $uploadSchemaUrl;

    function __construct()
    {
        if (file_exists(__DIR__."/env.php")){
            require_once 'env.php';
        }
        $this->accessKeyId = getenv("ACCESSKEYID");
        $this->accessKeySecret = getenv("ACCESSKEYSECRET");
        $this->endpoint = getenv("ENDPOINT");
        $this->bucket = getenv("BUCKET");
        echo "buck:".$this->bucket."\n";
        $this->uploadSchemaUrl = getenv("UPLOADSCHEMAURL");
    }


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
            $this->echo("\nshcema direstor error:" . print_r($schemaArr, true));
            return;
        }

        $lastUpdateSchemas = $this->lastUpdateSchema($schemaArr);
//        echo "last update schema:".print_r($lastUpdateSchemas, true)."\n";

        //need update schema
        $updateSchemaArr = $this->needUpdateSchema($lastUpdateSchemas, $schemaArr);
//        echo "neet update schema:".print_r($updateSchemaArr, true)."\n";

        $uploadSchema = array();
        foreach ($updateSchemaArr as $key => $value) {

            //schema to json

            $schemaDirName =  explode("@", $key);
            $schemaName = $schemaDirName[0];
            $schemaVersion = $schemaDirName[1];
            if (empty($schemaName) || empty($schemaVersion)){
                echo "schema dir error:".$key;
                continue;
            }

            //update to mysql
                //zip schema file
                $this->packageZip($value, $key);

                //upload to oss
                $this->uploadToOSS($schemaName."_".$schemaVersion.".zip", self::ZIP_PATH."/".$key.".zip");

                $uploadSchema[$key] = $this->schemaToJson(self::SCHEMA_PATH."/".$key."/".$schemaName.".schema.yaml");
        }
        if(!empty($uploadSchema)) {
            echo "upload schema:".print_r($uploadSchema, true);
            if($this->uploadSchema(json_encode(array_values($uploadSchema)))){
                foreach ($uploadSchema as $uploadSchemaKey => $uploadSchemaValue){
                    $lastUpdateSchemas[$uploadSchemaKey] = $uploadSchemaValue;
                }
                file_put_contents(self::LAST_UPDATE_SCHEMA, json_encode($lastUpdateSchemas));
            }else{
                echo $key."upload fail";
            }
        }
    }

    private function lastUpdateSchema($schemaArr)
    {
        $lasUpdateSchema = array();

        foreach ($schemaArr as $key => $value){
                $pos = strpos($key, "@");
                $schemaName = substr($key, 0, $pos);
                try {
                    $schemaDetail = Yaml::parseFile(self::SCHEMA_PATH . "/" . $key . "/" . $schemaName . ".schema.yaml");
                    echo "read schema :".$key." detail:".print_r($schemaDetail['schema'], true)."\n";
                    $schemaHead = $schemaDetail['schema'];
                    $lasUpdateSchema[$key] = $schemaHead;
                } catch (ParseException $e) {
                    echo $schemaName.":".$e->getMessage()."\n"; //
                }
        }

        if (!file_exists(self::LAST_UPDATE_SCHEMA)){
            return $lasUpdateSchema;
        }
        return json_decode(file_get_contents(self::LAST_UPDATE_SCHEMA), true);
    }


    private function needUpdateSchema($schemas, $schemaDir)
    {
        if (!file_exists(self::LAST_UPDATE_SCHEMA)){
            echo "need update all schema \n";
            return $schemaDir;
        }

        $ret = array();
        foreach ($schemaDir as $key => $value){
            if (!isset($schemas[$key])){
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
//        echo "flies:" . print_r($files, true) . "\ntoPath:" . self::ZIP_PATH . "/" . $dir . ".zip\n";
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
            return false;
        }
        return true;
    }


    private function uploadSchema($schemaJson)
    {
        echo "upload ".$schemaJson."\n";
        if (empty($schemaJson)){
            echo "upload schema empty";
            return false;
        }
        $ret = self::post($this->uploadSchemaUrl, array("schemas" => $schemaJson), 30);
        $ret = json_decode($ret, true);
        if (empty($ret) || $ret['errorCode'] != 0){
            return false;
        }
        return true;
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
        echo "post response:".$file_contents."\n";
        curl_close($ch);
        return $file_contents;
    }


    private function echo($log)
    {
        echo __FUNCTION__."\n".print_r($log, true)."\n";
    }
}

$p = new Package();
$p->run();


?>
