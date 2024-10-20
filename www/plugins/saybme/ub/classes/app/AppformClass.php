<?php namespace Saybme\Ub\Classes\App;

use Saybme\Ub\Models\Formrow;
use System\Models\File;

class AppformClass {
   
    // Внешние значение для формы
    public static function getInValueForm($id = null, $obj){
        if(!$id) return;
        if(!$obj) return;
        
        $arr['title'] = $obj->title;
        $arr['value'] = $id;
        return collect($arr);
    }

    // Чекбоксы
    public static function getValueCheckboxs($value, $obj){
        //if(!$obj) return;

        $value = Formrow::whereIn('id', $value)->get();
        if(!$value) return;
        
        $items = array();
        foreach($value->pluck('title') as $item){
            $items[] = $item;
        }
        $arr['title'] = $obj->title;
        $arr['value'] = implode(', ', $items);
        return collect($arr);
    }

    // Радиокнопки
    public static function getInValueRadio($value, $obj){        
        if(!$obj) return;

        $value = Formrow::find($value);
        if(!$value) return;

        $arr['title'] = $obj->ptitle ?: $obj->title;
        $arr['value'] = $value->title;
        return collect($arr);
    }

    // Скрытое поле
    public static function getValueHide(){
        return false;
    }

    // Dropzone
    public static function getValueDropzone($value, $obj){

        $arr['title'] = $obj->ptitle ?: $obj->title;
        $arr['value'] = File::whereIn('id', $value)->get();        

        return collect($arr);       
    }

    // Массив
    public static function getValueArray($value, $obj){
        if(!$obj) return;

        $keys['index'] = 'Индекс';
        $keys['region'] = 'Регион';
        $keys['city'] = 'Населенный пункт';
        $keys['street'] = 'Улица';
        $keys['house'] = 'Дом';
        $keys['building'] = 'Строение';
        $keys['room'] = 'Квартира / офис';
        $keys['no_address'] = 'Без квартиры / офиса';

        $keys['document'] = 'Документ, удостоверяющий личность собственника';
        $keys['series'] = 'Серия';
        $keys['num'] = 'Номер';
        $keys['date'] = 'Дата выдачи';
        $keys['issuedby'] = 'Кем выдан';
        $keys['code'] = 'Код подразделения';
        $keys['power_attorney'] = 'Тип доверенности';
        $keys['validuntil'] = 'Срок действия до';
    
        

        // Удаляем полный массив
        unset($value['full']);

        $items = array();

        foreach($value as $key => $item){
            if(key_exists($key, $keys)){
                if(trim($item)){
                    $items[] = $keys[$key] . ': '. $item; 
                }                
            }                          
        }

        $arr['title'] = $obj->ptitle ?: $obj->title;
        $arr['value'] = implode('<br>', $items);
        return collect($arr);
    }

    // Текст
    public static function getValueText($value = null, $obj){
        if(!$obj) return;

        if(gettype($value) == 'array'){
            $arr['value'] = json_encode($value);
        } else {
            $arr['value'] = $value;
        }

        $arr['title'] = $obj->title;        
        return collect($arr);
    }

    // Дата
    public static function getValueDate($value = null, $obj){
        if(!$obj) return;

        $value = date('d.m.Y год', strtotime($value));

        $arr['title'] = $obj->title;
        $arr['value'] = $value;
        return collect($arr);
    }

    // Дата и время
    public static function getValueDateTime($value = null, $obj){

        $value = implode(' ', $value);
        $value = date('d.m.Y H:i', strtotime($value));

        $arr['title'] = $obj->ptitle ?: $obj->title;
        $arr['value'] = $value;
        return collect($arr);
    }

}