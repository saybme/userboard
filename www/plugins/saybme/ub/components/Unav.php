<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Navigation;
use Log;

class Unav extends \Cms\Classes\ComponentBase {

    public function componentDetails() {
        return [
            'name' => 'Навигация',
            'description' => 'Навигация сайта'
        ];
    }
 
    public function defineProperties() {
        return [
            'navigation' => [
                'title' => 'Навигация',
                'description' => 'Выберите навигацию',
                'type' => 'dropdown'             
            ],
            'template' => [
                'title' => 'Шаблон',
                'description' => 'Шаблон навигации',
                'type' => 'string',
                'default' => 'navigation/default'
            ]
        ];
    }

    public function getNavigationOptions(){
        $navigations = Navigation::where('nest_depth', 0)->lists('title', 'id');
        return $navigations;
    }

    function onRun() {
        $this->unav = $this->getContent();
    }

    protected function getContent() {
        $navigation_id = $this->property('navigation');
        $template = $this->property('template');

        $items = Navigation::where('parent_id', $navigation_id)
            ->with('children','image')
            ->get();
        
        $data = [
            'items' => $items
        ];

        return $this->renderPartial($template, $data);
    }

    public $unav;
}