<?php namespace Unisharp\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Unisharp\Laravelfilemanager\Events\ImageIsRenaming;
use Unisharp\Laravelfilemanager\Events\ImageWasRenamed;
use Unisharp\Laravelfilemanager\Events\FolderIsRenaming;
use Unisharp\Laravelfilemanager\Events\FolderWasRenamed;
use Storage ;

/**
 * Class RenameController
 * @package Unisharp\Laravelfilemanager\controllers
 */
class RenameController extends LfmController
{
    /**
     * @return string
     */
    public function getRename()
    {
        $old_name = $this->translateFromUtf8($this->getRequest('file'));
        $new_name = $this->translateFromUtf8(trim($this->getRequest('new_name')));

        $working_dir = $this->getWorkingDir();

        if (empty($new_name)) {
            return $this->error('file-name');
        }

        $old_path = $working_dir . '/' . $old_name ;
        $new_path = $working_dir . '/' . $new_name ;
        if (!Storage::disk('s3')->exists($old_path)) {
            return $this->error('rename');
        }
        Storage::move($old_path,$new_path);

        $old_thumb_path = $working_dir . '/' . config('lfm.thumb_folder_name') . '/' . $old_name;
        $new_thumb_path = $working_dir . '/' . config('lfm.thumb_folder_name') . '/' . $new_name;
        if (Storage::disk('s3')->exists($old_thumb_path)) {
            Storage::move($old_thumb_path,$new_thumb_path);
        }

        foreach(config('lfm.resize_folder') as $width){
            $old_resize_path = $working_dir . '/' . $width . '/' . $old_name;
            $new_resize_path = $working_dir . '/' .  $width . '/' . $new_name;
            if (Storage::disk('s3')->exists($old_resize_path)) {
                Storage::move($old_resize_path,$new_resize_path);
            }
        }




        event(new ImageWasRenamed($old_path, $new_path));

        return $this->success_response;
    }
}
