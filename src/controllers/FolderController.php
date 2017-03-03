<?php namespace Unisharp\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Storage ;
/**
 * Class FolderController
 * @package Unisharp\Laravelfilemanager\controllers
 */
class FolderController extends LfmController
{
    /**
     * Get list of folders as json to populate treeview
     *
     * @return mixed
     */
    public function getFolders()
    {
        $folder_types = [];
        $root_folders = [];


        if (parent::allowMultiUser()) {
            $folder_types['user'] = 'root';
        }

        foreach ($folder_types as $folder_type => $lang_key) {
            $folder_name = $this->getUserSlug() ;
            $s3_root_directory = 'assets/' . $folder_name . '/'  ;
            $directories = Storage::allDirectories($s3_root_directory);

            $resutl_folders = [] ;
            foreach($directories as $directorie){
                $temp = explode('/', $directorie);
                if(end($temp) === config('lfm.thumb_folder_name') || in_array(end($temp),config('lfm.resize_folder')) ){
                    continue;
                }
                $resutl_folders[] = [
                    'name'  => $temp[count($temp)-1] ,
                    'path'  => $directorie ,
                    'margin' => ( count($temp) - 2 ) * 10
                ];
            }


            array_push($root_folders, (object)[
                'name' => trans('laravel-filemanager::lfm.title-' . $lang_key),
                'path' => $s3_root_directory,
                'children' => $resutl_folders,
                'has_next' => false
            ]);
        }

        return view('laravel-filemanager::tree')
            ->with(compact('root_folders'));
    }


    /**
     * Add a new folder
     *
     * @return mixed
     */
    public function getAddfolder()
    {
        $folder_name = $this->getRequest('name');
        $working_dir = $this->getWorkingDir();

        $folder_name = $this->translateFromUtf8($folder_name);

        $path = $working_dir . '/' . $folder_name ;

        if (empty($folder_name)) {
            return $this->error('folder-name');
        } elseif (File::exists($path)) {
            return $this->error('folder-exist');
        } elseif (config('lfm.alphanumeric_directory') && preg_match('/[^\w-]/i', $folder_name)) {
            return $this->error('folder-alnum');
        } else {
            Storage::makeDirectory($path);
            return $this->success_response;
        }
    }
}
