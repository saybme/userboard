<?php namespace Saybme\Ub\Classes\Auth;

use Saybme\Ub\Models\Ubform;
use System\Models\File;
use Input;
use Log;

class FilesClass {

    public function add(){

        $id = Input::get('form');
        $obj = Ubform::find($id);    
        
        if(!$obj) return;

        $file = new File;
        $file->data = files('file');
        $file->is_public = true;
        $file->save();
        
        $obj->photos()->add($file);
       
        return $file;
    }

}