<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\ImageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Persistence\ManagerRegistry;
use Tinify;

/**
 * BaseController.
 */
class Controller extends AbstractController
{
    protected $imageManager;

    protected $em;

    public function __construct(ImageManager $imageManager, ManagerRegistry $doctrine)
    {
        $this->imageManager = $imageManager;
        $this->em = $doctrine->getManager();

    }

    public function user()
    {
        return $this->em->getRepository(Users::class)->findOneBy(['email' => 'hello@themixchief.com']);
    }

    /**
     * File Upload.
     *
     * @param string $file  $_FILES key
     * @param int    $width
     *
     * @return string Uploaded image path
     */
    public function upload($file, $width)
    {
        // if ($_FILES[$file]["name"]) {
        //    	$filename = basename($_FILES[$file]["name"]);
        // 	// var_dump(getenv('TINIFY_KEY'));die;
        //     // $this->imageManager->compress($sourceData);
        //     $resultData = $this->imageManager->compressAndStore('mixchief', $filename, $name);

        // 	return $resultData;
        // }

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
     * Decode JSON API requests.
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
