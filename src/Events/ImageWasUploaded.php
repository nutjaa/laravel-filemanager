<?php

namespace Unisharp\Laravelfilemanager\Events;

class ImageWasUploaded
{
    private $path;
    private $filename ;

    public function __construct($path,$filename)
    {
        $this->path = $path;
        $this->filename = $filename ;
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    public function filename(){
        return $this->filename ;
    }
}
