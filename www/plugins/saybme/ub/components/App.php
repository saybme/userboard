<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Classes\App\AppClass;
use Lang;
use Request;
use Input;
use Log;
use Redirect;

class App extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Скрипты',
            'description' => 'Все скрипты сайта'
        ];
    }    

    // Отправляем форму
    public function onSendForm(){
        $data = Input::all();

        $result = $this->renderPartial('form/success');
        return $result;
    }

    // Модальное окно входа
    function onModalOpen(){
        $data = Input::all();
        $result['modal'] = $this->renderPartial('modal/open-form');
        return $result;
    }

    // Авторизация
    function onAuth(){

        $step = Input::get('step');
        $data = Input::all();

        $q = new AuthClass;

        $options = array();
        $tpl = 'modal/form-inputs';

        // Шаг №1
        if($step == 2) {
            
            $q->authStepOne(); // Проверка данные для входа
            $q->saveContactUser();
            
            $user = $q->authStepTwo();
            $options['auth'] = $q->getAuthSession();
            $options['step'] = 3;   
            if(!$user) $options['step'] = 2;          
        }

        // Шаг регистрации
        if($step == 3) {
            $q->authStepThree();
            $q->authStepFour();
            return Redirect::to('/cabinet');  
        }     
        
        // Шаг входа
        if($step == 4){
            $q->authStepFour();
            return Redirect::refresh();  
        }

        // Вход менеджера
        if($step == 'manager'){          
            $q->authStepFour();
            return Redirect::refresh();     
        }

        $result['#open-result'] = $this->renderPartial($tpl, $options);
        return $result;
    }

}
