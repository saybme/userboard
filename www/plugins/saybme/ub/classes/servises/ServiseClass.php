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

    // Популярные услуги
    public function getPupularServises(){
        return Servise::active()->where('is_popular', true)->get();
    }

    // Гос номера
    public function getCarnumber(){

        // Тип номера
        $type = Input::get('type') ?: 1;

        $items = Carnumber::active()->where('type', $type);        

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

}