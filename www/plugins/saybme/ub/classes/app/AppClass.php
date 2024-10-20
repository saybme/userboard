<?php namespace Saybme\Ub\Classes\App;

use Saybme\Ub\Models\Banner;
use Saybme\Ub\Models\Form;
use Saybme\Ub\Models\Application;
use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\Formrow;
use Saybme\Ub\Models\Carnumber;
use Lang;
use Request;
use Input;
use Session;
use Log;

class AppClass {

    // Создаем заявку
    public function create(){

        // Сохраняем данные формы в сессию
        $this->saveFormSession();

        // Валидация формы
        $this->validForm();        

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser();

        // Данные формы
        $data['data'] = Input::get();
        $data['user'] = $user->id;

        $obj = new Application;
        $obj->fill($data);
        $obj->save();

        return $obj;
    }

    // формируем номер телефона
    public function setPhone($phone = ''){
        if(!trim($phone)) return;

        $phone = trim($phone);
        $phone = preg_replace("/[^0-9]/", '', $phone);
        $phone = 7 . substr($phone, 1);

        return $phone;
    }    

    // Проверка формы заявки
    public function formSend(){

        $id = Input::get('form');
        $form = Form::active()->find($id);

        // Валидация формы
        Request::validate($form->input_validations);

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser(); 

        // Заявка
        $app = new Application;
        $app->user = $user->id;
        $app->data = Input::get();
        $app->save();

        return $app;
    }   

    // Сохраняем поля в сессию
    private function saveFormSession(){
        $id = Input::get('form');
        $vars = Input::get();
        Session::put('ubform.' . $id, $vars);
        return;
    }

    // Валидация формы
    private function validForm(){
        // Данные с формы
        $vars = Input::get();
        unset($vars['form']);
        
        $items = array();
        foreach($vars as $key => $item){
            $obj = Formrow::where('value->hash', $key)->first();
            if($obj){
                if($obj->is_required){
                    $items[$key] = 'required';
                }                
            }            
        };

        Request::validate($items, Lang::get('saybme.ub::validation'));        
    }

    // Выводим время реботы
    public function getTimes(){
        
        $starTime = date('d.m.Y 08:00', time());
        $endTime = date('d.m.Y 23:45', time());

        $star = strtotime($starTime);
        $end = strtotime($endTime);

        $output = [];

        for ($i = $star; $i <= $end; $i += 900){
            $output[] = date('H:i', $i);
        }       

        return collect($output);
    }

    // Создаем объявление о гос номере
    public function createGosNum(){
        $data = Input::get();

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser(); 

        Request::validate([
            'photo' => 'image|max:5024'
        ]);

        $data['user'] = $user->id;
        $data['is_active'] = true;
        $data['num'] = implode('|', $data['nums']);

        $app = new Carnumber;
        $app->fill($data);
        $app->save();

        // Прикрепляем фото к обэявлению
        $app->photo = files('photo');
        $app->save();

        return $app;
    }

}