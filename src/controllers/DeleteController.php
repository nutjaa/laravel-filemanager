<?php namespace Unisharp\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Unisharp\Laravelfilemanager\Events\ImageIsDeleting;
use Unisharp\Laravelfilemanager\Events\ImageWasDeleted;
use Storage ;
/**
 * Class CropController
 * @package Unisharp\Laravelfilemanager\controllers
 */
class DeleteController extends LfmController
{
    /**
     * Delete image and associated thumbnail
     *
     * @return mixed
     */
    public function getDelete()
    {
        $name_to_delete = $this->getRequest('items');

        $file_to_delete = $this->getWorkingDir() . '/' . $name_to_delete ;
        $thumb_to_delete = $this->getWorkingDir() . '/'.config('lfm.thumb_folder_name').'/' . $name_to_delete ;


        event(new ImageIsDeleting($file_to_delete));

        if (is_null($name_to_delete)) {
            return $this->error('folder-name');
        }

        if (!Storage::disk('s3')->exists($file_to_delete)) {
            return $this->error('folder-not-found', ['folder' => $file_to_delete]);
        }

        $is_directoty = File::isDirectory($file_to_delete);
        Storage::deleteDirectory($file_to_delete);

        if (!Storage::disk('s3')->exists($file_to_delete)) {
            return $this->success_response;
        }

        Storage::delete($file_to_delete);
        if (Storage::disk('s3')->exists($thumb_to_delete)) {
            Storage::delete($thumb_to_delete);
        }

        foreach(config('lfm.resize_folder') as $width){
            $resize_file = $this->getWorkingDir() . '/' . $width .'/' . $name_to_delete ;
            if (Storage::disk('s3')->exists($resize_file)) {
                Storage::delete($resize_file);
            }
        }

        event(new ImageWasDeleted($file_to_delete));

        return $this->success_response;
    }
}
