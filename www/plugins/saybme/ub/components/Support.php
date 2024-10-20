<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\Suptheme;
use Input;
use Log;

class Support extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Техническая поддержка',
            'description' => 'Техническая поддержка'
        ];
    }  

    public function defineProperties() {
        return [
            'hash' => [
                'title' => 'HASH',
                'description' => 'HASH темы сообщения',
                'default' => '{{ :hash }}'
            ],
            'type' => [
                'title' => 'Что выводить',
                'description' => 'Тип вывода',
                'type' => 'dropdown',
                'options' => [
                    'getForm' => 'Вывод формы создания',
                    'getMessage' => 'Вывод сообщения'
                ],
                'default' => 'getForm'
            ]
        ];
    }

    function onRun(){
        $this->support = $this->getContent();
    }
    
    private function getContent(){
        $type = $this->property('type');
        return $this->$type();
    }

    private function getForm(){
        $tpl = 'support/form';
        $options = array();

        $q = new AuthClass;
        $user = $q->getActiveUser();    
        
        if(!$user) return;

        $options['themes'] = Suptheme::active()->userType($user->id)->get();

        return $this->renderPartial($tpl, $options);
    }

    private function getMessage(){
        $hash = $this->property('hash');
        return $hash;
    }    

    public $support;

}
