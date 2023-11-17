<?php

namespace App\Service;

use Tinify;

class ImageManager
{
    function __construct($tinifyKey)
    {
        Tinify\setKey($tinifyKey);
    }

    public function compress($image)
    {
        try {
            return Tinify\fromBuffer($image);
        } catch(\Tinify\AccountException $e) {
            // Verify your API key and account limit.
            return false;
        } catch(\Tinify\ClientException $e) {
            // Check your source image and request options.
            return false;
        } catch(\Tinify\ServerException $e) {
            // Temporary issue with the Tinify API.
            return false;
        } catch(\Tinify\ConnectionException $e) {
            // A network connection error occurred.
            return false;
        } catch(\Exception $e) {
            // Something else went wrong, unrelated to the Tinify API.
            return false;
        }
    }
}