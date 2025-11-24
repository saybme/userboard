<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Carnumber;
use Log;

class Ubapps extends \Cms\Classes\ComponentBase {

    public function componentDetails() {
        return [
            'name' => 'Объявления',
            'description' => 'Вывод объявлений'
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
                'default' => 'getNumbers',
                'type' => 'dropdown',
                'options' => [
                    'getNumbers' => 'Вывод всех гос номеров',
                    'payNumbersJson' => 'Гос номера продажа',
                    'buyNumbersJson' => 'Гос номера покупка',
                    'buyNumbersLenta' => 'Лента гос номеров покупка',
                    'getNumber' => 'Страница гос номер',
                    'getTopNumbers' => 'Вывод топа номеров'
                ]               
            ],
            'max' => [
                'title' => 'Количество',
                'description' => 'Количество для выода',
                'default' => 30   
            ]
        ];
    }

    function onRun(){      
        $this->ubapps = $this->getContent();
        
    }

    private function getContent(){
        $type = $this->property('type');          
        return $this->$type();
    }

    private function getNumbers(){        
        $options['items'] = Carnumber::active()->orderBy('id', 'desc')->get();
        return $this->renderPartial('announcements/wrap', $options);
    }

    private function payNumbersJson(){     
        $max = $this->property('max') ?: 30; 
        return Carnumber::active()->where('type_pay', 1)->where('type', 1)->orderBy('id', 'desc')->get()->take($max);
    }

    private function buyNumbersJson(){     
        $max = $this->property('max') ?: 30; 
        return Carnumber::active()->where('type_pay', 2)->orderBy('id', 'desc')->get()->take($max);
    }

    private function buyNumbersLenta(){     
        $max = $this->property('max') ?: 30;         
        return Carnumber::active()->where('type_pay', 2)->where('description', '!=', '')->orderBy('id', 'desc')->get()->take($max);
    }

    // Топ номеров
    private function getTopNumbers(){
        $options['items'] = Carnumber::active()->orderBy('id', 'desc')->get();
        return $this->renderPartial('announcements/wrap-top', $options);
    }

    public function getNumber(){
        $id = $this->property('slug');
        $page = Carnumber::active()->find($id);    
        if(!$page) return $this->controller->run('404');

        $this->page->title = $page->name;

        $options['items'] = Carnumber::active()->orderBy('id', 'desc')->where('id', '!=', $page->id)->get();
        $options['page'] = $page;
        $options['breadcrumbs'] = $this->breadcrumbs($page);
        return $this->renderPartial('announcements/page', $options);
    } 

    private function breadcrumbs($page){
        if(!$page) return;

        $items[0]['title'] = 'Гос номера';
        $items[0]['url'] = url('servises/kupit-prodat-avto-nomera');

        $items[1]['title'] = $page->num;
        $items[1]['isActive'] = true;

        return $items;
    }

    public $ubapps;
}
