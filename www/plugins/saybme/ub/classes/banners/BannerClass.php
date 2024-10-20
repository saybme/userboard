<?php namespace Saybme\Ub\Classes\Banners;

use Saybme\Ub\Models\Banner;

class BannerClass {

    // Получаем все баннеры
    public function getAllBaners(){
        return Banner::active()->get();
    }    

}