<?php

namespace Unisharp\Laravelfilemanager\traits;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Storage ;

trait LfmHelpers
{
    /*****************************
     ***       Path / Url      ***
     *****************************/

    private $ds = '/';

    public function getThumbPath($image_name = null)
    {
        return $this->getCurrentPath($image_name, 'thumb');
    }

    public function getCurrentPath($file_name = null, $is_thumb = null)
    {
        $path = $this->composeSegments('dir', $is_thumb, $file_name);

        $path = $this->translateToOsPath($path);

        return base_path($path);
    }

    public function getThumbUrl($image_name = null)
    {
        return $this->getFileUrl($image_name, 'thumb');
    }

    public function getFileUrl($image_name = null, $is_thumb = null)
    {
        return url($this->composeSegments('url', $is_thumb, $image_name));
    }

    private function composeSegments($type, $is_thumb, $file_name)
    {
        $full_path = implode($this->ds, [
            $this->getPathPrefix($type),
            $this->getFormatedWorkingDir(),
            $this->appendThumbFolderPath($is_thumb),
            $file_name
        ]);

        $full_path = $this->removeDuplicateSlash($full_path);
        $full_path = $this->translateToLfmPath($full_path);

        return $this->removeLastSlash($full_path);
    }

    public function getPathPrefix($type)
    {
        $default_folder_name = 'files';
        if ($this->isProcessingImages()) {
            $default_folder_name = 'photos';
        }

        $prefix = config('lfm.' . $this->currentLfmType() . 's_folder_name', $default_folder_name);

        if ($type === 'dir') {
            $prefix = config('lfm.base_directory', 'public') . '/' . $prefix;
        }

        return $prefix;
    }

    private function getFormatedWorkingDir()
    {
        $working_dir = request('working_dir');

        if (is_null($working_dir)) {
            $default_folder_type = 'share';
            if ($this->allowMultiUser()) {
                $default_folder_type = 'user';
            }

            $working_dir = $this->rootFolder($default_folder_type);
        }

        return $this->removeFirstSlash($working_dir);
    }

    private function appendThumbFolderPath($is_thumb)
    {
        if (!$is_thumb) {
            return;
        }

        $thumb_folder_name = config('lfm.thumb_folder_name');
        //if user is inside thumbs folder there is no need
        // to add thumbs substring to the end of $url
        $in_thumb_folder = preg_match('/'.$thumb_folder_name.'$/i', $this->getFormatedWorkingDir());

        if (!$in_thumb_folder) {
            return $thumb_folder_name . $this->ds;
        }
    }

    public function rootFolder($type)
    {
        if ($type === 'user') {
            $folder_name = $this->getUserSlug();
        } else {
            $folder_name = config('lfm.shared_folder_name');
        }

        return $this->ds . $folder_name;
    }

    public function getRootFolderPath($type)
    {
        return base_path($this->getPathPrefix('dir') . $this->rootFolder($type));
    }

    public function getName($file)
    {
        $lfm_file_path = $this->getInternalPath($file);
        $arr_dir = explode($this->ds, $lfm_file_path);
        $file_name = end($arr_dir);

        return $file_name;
    }

    public function getInternalPath($full_path)
    {
        $full_path = $this->translateToLfmPath($full_path);
        $full_path = $this->translateToUtf8($full_path);
        /*
        $lfm_dir_start = strpos($full_path, $this->getPathPrefix('dir'));
        $working_dir_start = $lfm_dir_start + strlen($this->getPathPrefix('dir'));
        $lfm_file_path = $this->ds . substr($full_path, $working_dir_start);
        */
        return $this->removeDuplicateSlash($full_path);
    }

    private function translateToOsPath($path)
    {
        if ($this->isRunningOnWindows()) {
            $path = str_replace($this->ds, '\\', $path);
        }
        return $path;
    }

    private function translateToLfmPath($path)
    {
        if ($this->isRunningOnWindows()) {
            $path = str_replace('\\', $this->ds, $path);
        }
        return $path;
    }

    private function removeDuplicateSlash($path)
    {
        return str_replace($this->ds . $this->ds, $this->ds, $path);
    }

    private function removeFirstSlash($path)
    {
        if (starts_with($path, $this->ds)) {
            $path = substr($path, 1);
        }

        return $path;
    }

    private function removeLastSlash($path)
    {
        // remove last slash
        if (ends_with($path, $this->ds)) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    public function translateFromUtf8($input)
    {
        if ($this->isRunningOnWindows()) {
            $input = iconv('UTF-8', 'BIG5', $input);
        }

        return $input;
    }

    public function translateToUtf8($input)
    {
        if ($this->isRunningOnWindows()) {
            $input = iconv('BIG5', 'UTF-8', $input);
        }

        return $input;
    }


    /****************************
     ***   Config / Settings  ***
     ****************************/

    public function isProcessingImages()
    {
        return $this->currentLfmType() === 'image';
    }

    public function isProcessingFiles()
    {
        return $this->currentLfmType() === 'file';
    }

    public function currentLfmType($is_for_url = false)
    {
        $file_type = request('type', 'Images');

        if ($is_for_url) {
            return ucfirst($file_type);
        } else {
            return lcfirst(str_singular($file_type));
        }
    }

    public function allowMultiUser()
    {
        return config('lfm.allow_multi_user') === true;
    }


    /****************************
     ***     File System      ***
     ****************************/

    public function getDirectories($path)
    {
        $thumb_folder_name = config('lfm.thumb_folder_name');
        $all_directories = Storage::directories($path);
        $arr_dir = [] ;

        foreach ($all_directories as $directory) {
            $directory_name = $this->getName($directory);

            if ($directory_name !== $thumb_folder_name && ! in_array($directory_name,config('lfm.resize_folder'))) {
                $arr_dir[] = (object)[
                    'name' => $directory_name,
                    'path' => $directory
                ];
            }
        }

        return $arr_dir;
    }

    public function getFilesWithInfo($path)
    {
        $arr_files = [];
        $working_dir = $this->getWorkingDir()  ;
        $files = Storage::files($path);
        foreach ($files as $key => $file) {
            $file_name = $this->getName($file);
            $file_url = config('filesystems.disks.s3.public_url').$file;

            $icon = 'fa-image';

            $thumb = $working_dir . '/' . config('lfm.thumb_folder_name') . '/' . $file_name ;

            if (!Storage::disk('s3')->exists($thumb)) {
                $thumb = $file ;
            }
            $thumb_url = config('filesystems.disks.s3.public_url') . $this->removeDuplicateSlash( $thumb);

            $arr_files[$key] = [
                'name'      => $file_name,
                'url'       => $file_url,
                'size'      => $this->humanFilesize( Storage::size($file)),
                'updated'   => Storage::lastModified($file),
                'type'      => 'images',
                'icon'      => $icon,
                'thumb'     => $thumb_url
            ];
        }

        return $arr_files;
    }

    public function createFolderByPath($path)
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
    }

    public function directoryIsEmpty($directory_path)
    {
        return count(File::allFiles($directory_path)) == 0;
    }

    public function fileIsImage($file)
    {
        if ($file instanceof UploadedFile) {
            $mime_type = $file->getMimeType();
        } else {
            $mime_type = exif_imagetype($file);
             if(in_array($mime_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP)))
            {
                return true;
            }
        }

        return starts_with($mime_type, 'image');
    }


    /****************************
     ***    Miscellaneouses   ***
     ****************************/

    public function getUserSlug()
    {
        $slug_of_user = config('lfm.user_field');

        return empty(auth()->user()) ? '' : auth()->user()->$slug_of_user;
    }

    public function error($error_type, $variables = [])
    {
        return trans('laravel-filemanager::lfm.error-' . $error_type, $variables);
    }

    public function humanFilesize($bytes, $decimals = 2)
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }

    public function isRunningOnWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function getWorkingDir(){
        $slug_of_user = config('lfm.user_field');
        $base_path = 'assets' . '/' . auth()->user()->$slug_of_user;
        $path = $base_path ;
        if(isset($_REQUEST['working_dir']) && $_REQUEST['working_dir']){
            $path = $_REQUEST['working_dir'];
        }

        $path = $this->removeDuplicateSlash($path);
        $path = $this->removeFirstSlash($path);
        $paths = explode('/', $path);

        if(!isset($paths[1]) && $paths[1] != auth()->user()->$slug_of_user ){
            $path = $base_path ;
        }
        return $path ;
    }

    public function getRequest($index){
        return isset($_GET[$index])?$_GET[$index]:'' ;
    }
}
