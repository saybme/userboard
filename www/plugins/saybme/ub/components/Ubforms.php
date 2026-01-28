<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Ubform;
use View;
use Twig;
use Input;
use Log;

class Ubforms extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Формы',
            'description' => 'Обработка форм сайта'
        ];
    }   

    public function defineProperties() {
        return [
            'forms' => [
                'title' => 'Форма',
                'description' => 'Выберите форму для обработки',
                'type' => 'dropdown'            
            ]
        ];
    }   

    public function getFormsOptions()
    {
        $forms = Ubform::lists('name', 'id');
        return $forms;
    }

    function onRun(){
        $this->ubforms = $this->getForm();        
    }
    
    public function getForm() {
        $formId = $this->property('forms');

        if(!$formId) {
            Log::error('UB: Не указана форма для обработки');
            return false;
        }

        // Получаем форму
        $form = Ubform::find($formId);

        if(!$form) {
            Log::error('UB: Форма с ID ' . $formId . ' не найдена');
            return false;
        }

        $optins['form'] = $form;
        $tmp = '@forms/form_' . $form->id;        

        $content = $this->renderPartial($tmp, $optins);
        return $content;
    }

    // Создаем модель из данных формы
    public function onFormCreate(){

        $mode = Input::get('mode');
        if(!$mode) {
            throw new \ApplicationException('UB: Не указан режим обработки формы');
        }

        return;
    }

    public $ubforms;
}