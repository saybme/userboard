<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Servise;

class Uservises extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Услуги',
            'description' => 'Вывод услуг'
        ];
    }    
    
    public function defineProperties() {
        return [
            'slug' => [
                'title' => 'SLUG',
                'description' => 'SLUG страницы',
                'default' => '{{ :slug }}'
            ],
            'type' => [
                'title' => 'Тип вывода',
                'description' => 'Укажите тип вывода',
                'default' => 'getServises',
                'type' => 'dropdown',
                'options' => [
                    'getServises' => 'Все услуги',
                    'getServise' => 'Услуга'
                ]               
            ]
        ];
    }
    
    function onRun(){      
        $this->uservises = $this->getContent();
    }

    private function getContent(){
        $type = $this->property('type');       
        return $this->$type();
    }

    private function getServise(){

        $tmp = 'servises/page';
        $slug = $this->property('slug');
        $servise = Servise::active()->where('url', $slug)->first();

        if(!$servise) return;

        $this->page->title = $servise->name;

        if($servise->tmp) $tmp = $servise->tmp;        

        $options['menu'] = Servise::select('id','name','slug')->active()->get();
        $options['page'] = $servise;
        $options['breadcrumbs'] = $this->getBreadcrumbs($servise);
        return $this->renderPartial($tmp, $options);
    }

    private function getBreadcrumbs($servise){

        $arr[0]['title'] = 'Услуги';
        $arr[0]['url'] = url('uslugi');

        $arr[1]['title'] = $servise->name;
        $arr[1]['isActive'] = true;

        return $arr;
    }

    public $uservises;

}
