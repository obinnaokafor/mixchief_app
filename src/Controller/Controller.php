<?php

namespace App\Controller;

use App\Service\ImageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Tinify;


/**
 * BaseController
 */
class Controller extends AbstractController
{
	protected $imageManager;

	function __construct(ImageManager $imageManager)
	{
		$this->imageManager = $imageManager;	
	}

	public function user()
	{
		return $this->getDoctrine()->getRepository(Users::class)->findOneBy(['email' => 'mike.okafor88@gmail.com']);
	}
	
	/**
	 * File Upload
	 * @param  string $file  $_FILES key
	 * @param  int $width
	 * @return string        Uploaded image path
	 */
	public function upload($file, $width)
	{

	    if ($_FILES[$file]["name"]) {
	    	var_dump($_FILES[$file]);die;
		   	$filename = basename($_FILES[$file]["name"]);
		    $sourceData = file_get_contents($_FILES[$file]["tmp_name"]);
			// var_dump(getenv('TINIFY_KEY'));die;
		    // $this->imageManager->compress($sourceData);
		    $resultData = $this->imageManager->compress($sourceData);
		    $resized = $resultData->resize(array(
		    	"method" => "scale",
		    	"width" => $width
		    ));

		    $file = sprintf('/amply/public/images/items/%s-%s', uniqid(), $filename);

		    // $saved = getenv('UPLOAD_PATH') . $file;

		    $res = $resized->toFile($file);

		    // var_dump($saved);die;

		    // return $saved;
	    }

	   return null;
	    // var_dump($res);die;
	    // var_dump($uploadedFile->move(
	    // 	$this->getParameter('kernel.project_dir') . '/public/images/items',
	    // 	$uploadedFile->getClientOriginalName()
	    // ));
	    // die;
	    // $target_file = $target_dir . $filename;
	    // $uploadOk = 1;
	    // if (!move_uploaded_file($_FILES[$file]["tmp_name"], $target_file)) {
	    //     echo "Sorry, there was an error uploading your file.";
	    //     return;
	    // }


	}

	/**
	 * Decode JSON API requests
	 *
	 * @return array
	 **/
	protected function decode()
	{
		$content = file_get_contents('php://input');

		try {
			$decoded = json_decode($content, true);
			// var_dump($decoded);die;

			return $decoded;
		} catch (\Exception $e) {
			var_dump($e->getMessage());
		}
	}
}

?>
