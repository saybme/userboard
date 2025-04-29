<?php namespace Saybme\Ub\Classes\Cabinet;

use Saybme\Ub\Models\Prpage;
use Log;

class CabinetClass {    

    // Вложенные ресурсы
    public static function getPageChildrens($id = null, $depth = null){
        if(!$id) return $id;

        $page = Prpage::find($id);
        if(!$page) return null;        

        return $page->children;
    }

    // Перебор опций
    static public function getFormOptions($data = null){
        if(!$data) return;
        
        $rows = array();
        foreach($data as $item){
            $rows[$item->code] = $item;
        }

        return $rows;
    }

}