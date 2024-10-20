<?php namespace Saybme\Ub\Classes\Servises;

use Saybme\Ub\Models\Servise;

class ServiseClass {

    // Получаем все услуги
    public function getAllServises(){
        return Servise::active()->get();
    }

    // Популярные услуги
    public function getPupularServises(){
        return Servise::active()->where('is_popular', true)->get();
    }

}