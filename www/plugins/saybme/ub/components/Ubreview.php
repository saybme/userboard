<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Review;

class Ubreview extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Отзывы',
            'description' => 'Вывод отзывов на странице'
        ];
    }

    public function defineProperties() {
        return [
            'type' => [
                'title' => 'Тип вывода',
                'description' => 'Укажите тип вывода отзывов',
                'default' => 'getScroll',
                'type' => 'dropdown',
                'options' => [
                    'getScroll' => 'Все отзывы'
                ]               
            ]
        ];
    }

    function onRun(){
        $this->ubreview = $this->getContent();
        
    }

    private function getContent(){
        $type = $this->property('type');
        return $this->$type();
    }

    private function getScroll(){
        $options['items'] = Review::active()->get();
        return $this->renderPartial('reviews/scroll-items', $options);
    }

    public $ubreview;

}
