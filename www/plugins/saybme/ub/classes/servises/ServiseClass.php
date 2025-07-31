<?php namespace Saybme\Ub\Classes\Servises;

use Saybme\Ub\Models\Servise;
use Saybme\Ub\Models\Carnumber;
use Input;
use Log;

class ServiseClass {

    // Получаем все услуги
    public function getAllServises(){
        return Servise::active()->get();
    }

    // Получаем меню исходя из услуг
    public function getAllServisesMenu(){

        $data = Servise::active()->get();        

        $c = collect($data)->keyBy('id');
        $result = $c->map(function ($item) use ($c) {
            if (!($parent = $c->get($item->parent_id))) {
                return $item;
            }
            $parent->children[] = $item;
        })->filter();

        return $result;
    }

    // Популярные услуги
    public function getPupularServises(){
        return Servise::active()->where('is_popular', true)->get();
    }

    // Гос номера
    public function getCarnumber($typePay = 1, $mode = ''){

        // Лента
        if($typePay == 3) {
            $items = Carnumber::active()->where('type', 1)->where('type_pay', 2)->orderBy('id','desc')->where('description','!=','');  
            return $items->get();       
        }       
        

        // Тип номера
        $type = Input::get('type') ?: 1;

        $items = Carnumber::active()->where('type', $type)->where('type_pay', $typePay)->orderBy('id','desc');       
        
        if($mode == 'top'){
            return $items->where('number', '!=', '')->get();     
        }

        // Поиск по номеру
        $number = Input::get('number');
        if($number){
            $items = $items->where('number', $number);    
        }

        // Поиск по региону
        $region = Input::get('region');
        if($region){
            $items = $items->where('region', $region);    
        }

        // Поиск по серии
        $series = Input::get('series');        
        if($series){
            $series = implode('', $series);
            $items = $items->where('series', 'like', '%' .$series. '%');    
        }        

        return $items->get(); 
    }

    // Получаем услуги по их ID
    public static function getServisesIdx($value = ''){
        if(!$value) return;

        $idx = explode(',', $value);
        $items = Servise::active()->whereIn('id', $idx)->get();
        if(!$items) return;

        return $items;
    }

    // Получаем услуги по их ID
    public static function getServisesId($value = ''){
        if(!$value) return;       
        $page = Servise::active()->find($value);
        if(!$page) return;
        return $page;
    }

    // Получаем гос номера
    public static function carnumber($value = ''){
        $items = Carnumber::active()->where('type', 1)->get();
        return $items;
    }

}