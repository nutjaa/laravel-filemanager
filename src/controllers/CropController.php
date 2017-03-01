<?php namespace Unisharp\Laravelfilemanager\controllers;

use Unisharp\Laravelfilemanager\controllers\Controller;
use Intervention\Image\Facades\Image;
use Unisharp\Laravelfilemanager\Events\ImageWasUploaded;
use Storage ;
/**
 * Class CropController
 * @package Unisharp\Laravelfilemanager\controllers
 */
class CropController extends LfmController
{
    /**
     * Show crop page
     *
     * @return mixed
     */
    public function getCrop()
    {
        $working_dir = $this->getWorkingDir();
        $img_name = $this->getRequest('img');
        $img_url = config('filesystems.disks.s3.public_url') . $working_dir . '/' . $img_name ;

        return view('laravel-filemanager::crop')
            ->with(compact('working_dir', 'img_name' , 'img_url'));
    }


    /**
     * Crop the image (called via ajax)
     */
    public function getCropimage()
    {
        $image      =  $this->getRequest('img');
        $dataX      =  $this->getRequest('dataX');
        $dataY      =  $this->getRequest('dataY');
        $dataHeight =  $this->getRequest('dataHeight');
        $dataWidth  =  $this->getRequest('dataWidth');
        $image_path =  $image;
        $working_dir = $this->getWorkingDir();

        $new_file_path = tempnam( sys_get_temp_dir(), 'Tux');
        $image_list = explode('/', $image) ;
        $image_name = end($image_list);

        // crop image
        Image::make($image_path)
            ->crop($dataWidth, $dataHeight, $dataX, $dataY)
            ->save($new_file_path);

        $storePath = $this->getWorkingDir() . '/' . $image_name ;
        Storage::disk('s3')->put(
          $storePath,
          file_get_contents($new_file_path)
        );
        Storage::disk('s3')->setVisibility($storePath, 'public');

        // make new thumbnail
        //$this->createFolderByPath(parent::getThumbPath());

        $storePath = $this->getWorkingDir() . '/' . config('lfm.thumb_folder_name') . '/' . $image_name ;

        // create thumb image
        Image::make($new_file_path)
            ->fit(200, 200)
            ->save($new_file_path);

        Storage::disk('s3')->put(
          $storePath,
          file_get_contents($new_file_path)
        );
        Storage::disk('s3')->setVisibility($storePath, 'public');

        event(new ImageWasUploaded($this->getWorkingDir(),$image_name));


    }
}
