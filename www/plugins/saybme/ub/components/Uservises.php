<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Servise;
use View;

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
            'servise' => [
                'title' => 'ID услуги',
                'description' => 'Укажите услугу',                
                'type' => 'dropdown',
            ],
            'type' => [
                'title' => 'Тип вывода',
                'description' => 'Укажите тип вывода',
                'default' => 'getServises',
                'type' => 'dropdown',
                'options' => [
                    'getServises' => 'Все услуги',
                    'getServise' => 'Услуга',
                    'getServiseId' => 'Услуга по ID'
                ]               
            ]
        ];
    }

    public function getServiseOptions() {
        return Servise::lists('name','id');
    }
    
    function onRun(){      
        $this->uservises = $this->getContent();
    }

    private function getContent(){
        $type = $this->property('type');       
        
        return $this->$type();
    }

    // Услуга по ID
    private function getServiseId(){
        $id = $this->property('servise');

        $obj = Servise::find($id);
        $obj->items = $obj->children()->active()->get();

        return $obj;
    }

    // Все услуги сервиса
    private function getServises(){
        $options['items'] = $this->getAllServises();
        return View::make('saybme.ub::servises.all', $options);
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
        $options['servises'] = $this->getAllServises();

        

        return $this->renderPartial($tmp, $options);
    }

    // Все услуги сервиса
    private function getAllServises(){
        $items = Servise::with('children')->select('id','name','description','parent_id','slug')->active()->getNested();
        return $items;
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
