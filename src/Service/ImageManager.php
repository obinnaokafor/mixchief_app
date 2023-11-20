<?php

namespace App\Service;

use Tinify;

class ImageManager
{
    private $awsClient;
    private $awsSecret;
    private $awsRegion;

    function __construct($tinifyKey, $awsClient, $awsSecret, $awsRegion)
    {
        Tinify\setKey($tinifyKey);
        $this->awsClient = $awsClient;
        $this->awsSecret = $awsSecret;
        $this->awsRegion = $awsRegion;
    }

    public function compressAndStore($bucket, $image, $ext, $itemName, $width)
    {
        $imageurl = sprintf("https://s3.%s.amazonaws.com/%s/items/%s.%s", $this->awsRegion, $bucket, $this->slugify($itemName), $ext);
        try {
            $source = Tinify\fromFile($image);
            $resized = $source->resize(array(
		    	"method" => "scale",
		    	"width" => $width
		    ));
            $resized->store(array(
                "service" => "s3",
                "aws_access_key_id" => $this->awsClient,
                "aws_secret_access_key" => $this->awsSecret,
                "region" => $this->awsRegion,
                "headers" => array("Cache-Control" => "max-age=31536000, public"),
                "path" => sprintf("%s/items/%s.jpg", $bucket, $this->slugify($itemName))
            ));

            return $imageurl;
        } catch(\Exception $e) {
            // Verify your API key and account limit.
            echo $e->getMessage();
            return false;
        }
    }

    protected function slugify($name)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
}