<?php namespace Saybme\Ub\Classes\Cabinet;

use Saybme\Ub\Models\Prpage;
use Log;

class CabinetClass {    

    // Вложенные ресурсы
    public static function getPageChildrens($id = null){
        if(!$id) return $id;

        $page = Prpage::find($id);
        if(!$page) return null;

        return $page->children;
    }

}