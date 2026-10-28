<?php namespace Saybme\Ub\Classes\App;

use Saybme\Ub\Models\Banner;
use Saybme\Ub\Models\Form;
use Saybme\Ub\Models\Application;
use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\Formrow;
use Saybme\Ub\Models\Forminput;
use Saybme\Ub\Models\Carnumber;
use System\Models\File;
use ValidationException;
use Lang;
use Request;
use Input;
use Session;
use Log;

class AppClass {

    // Создаем заявку
    public function create(){

        // Сохраняем данные формы в сессию
        //$this->saveFormSession();        

        // Валидация формы
        $this->validForm();        

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser();

        if (!$user) {
            throw new ValidationException([
                'check_politic' => 'Чтобы отправить заявку, войдите в аккаунт.',
            ]);
        }

        // Данные формы
        $data['data'] = Input::get();
        $data['price'] = Input::get('price');
        $data['user'] = $user->id;  

        $fileKey = $user->id . time();
        
        // Сохраняем в форму даннеы полей файла
        if(count($_FILES)){            
            foreach($_FILES as $key => $file){
                $data['data'][$key] = md5($fileKey . $key);
            }
        }   
        

        $obj = new Application;
        $obj->fill($data);
        $obj->save();

        $this->getInputFiles($obj, $fileKey);       

        return $obj;
    }

    // Перебор полей и поиск файлов
    private function getInputFiles($model, $fileKey = null){

        $files = $_FILES;           

        if(!count($_FILES)) return;

        foreach($files as $key => $item){             

            if(Input::has($key)){                

                $fileData = Input::file($key);

                if(gettype($fileData) == 'object'){

                    $file = new \System\Models\File;
                    $file->data = $fileData;
                    $file->is_public = true;
                    $file->title = md5($fileKey . $key);
                    $file->save();

                    $model->files()->add($file); 

                } elseif(gettype($fileData) == 'array'){

                    foreach($fileData as $fileDataRow){

                        $file = new \System\Models\File;
                        $file->data = $fileDataRow;
                        $file->is_public = true;
                        $file->title = md5($fileKey . $key);
                        $file->save();

                        $model->files()->add($file); 

                        Log::error($fileDataRow);
                    }
                    

                }                

                
            }
                                 
        }

        return;
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

        if (!$user) {
            throw new ValidationException([
                'check_politic' => 'Чтобы отправить заявку, войдите в аккаунт.',
            ]);
        }

        // Заявка
        $app = new Application;
        $app->user = $user->id;
        $app->data = Input::get();
        $app->save();

        return $app;
    }   

    // Сохраняем поля в сессию
    // private function saveFormSession(){
    //     $id = Input::get('form');
    //     $vars = Input::get();
    //     Session::put('ubform.' . $id, $vars);
    //     return;
    // }

    // Валидация формы
    private function validForm(){

        $alertMessate['check_politic.accepted'] = 'Поле должно быть принято.';

        Request::validate([
            'check_politic' => 'accepted'
        ], $alertMessate);

        // Данные с формы
        $vars = Input::get();        
        unset($vars['form']);           

        $items = array();
        foreach($vars as $key => $item){
            $input = Forminput::where('is_required', true)->where('code', $key)->first();           
            if($input){                
                $items[$key] = 'required';                
            }            
        }; 

        // Дополнительная валидация
        foreach($vars as $key => $item){
            $input = Forminput::where('code', $key)->first();            
            if($input){   
                if($input->app_rules){
                    $items[$key] = $input->app_rules; 
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

        $rules['photo'] = 'image|max:5024';
        $rules['username'] = 'required';
        $rules['contacts'] = 'required';  
        
        if(Input::get('type_pay') == 2){
            $number = count(array_diff(Input::get('props.number'), array('')));
            $description = trim(Input::get('description'));
            if($number == 0 AND empty($description)){
                throw new ValidationException(['number' => 'Заполните гос номер или поле лента объявлений.']);  
            }        
        }         

        $rules['sum_start'] = 'required_if:type_price,Договорная';
        $rules['sum_end'] = 'required_if:type_price,Договорная';
        $rules['price'] = 'required_if:type_price,Фиксированная цена';

        $data = Input::get();

        $data['sum_start'] = Input::get('sum_start') ?: null;
        $data['sum_end'] = Input::get('sum_end') ?: null;

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser();

        if (!$user) {
            throw new ValidationException([
                'username' => 'Чтобы разместить объявление, войдите в аккаунт.',
            ]);
        }

        // Валидиция
        Request::validate($rules, Lang::get('saybme.ub::validation'));

        $data['user'] = $user->id;
        $data['is_active'] = false;
        //$data['num'] = implode('|', $data['nums']);        
        

        $app = new Carnumber;
        $app->fill($data);
        $app->save();

        // Прикрепляем фото к обэявлению
        $app->photo = files('photo');
        $app->save();

        return $data;
    }

    public function updateGosNum()
    {
        $q = new AuthClass;
        $user = $q->getActiveUser();

        if (!$user || !$user->isManager()) {
            throw new ValidationException([
                'id' => 'Редактировать объявление может только менеджер.',
            ]);
        }

        $item = Carnumber::find((int) Input::get('id'));
        if (!$item) {
            throw new ValidationException([
                'id' => 'Объявление не найдено.',
            ]);
        }

        Request::validate([
            'username' => 'required',
            'type' => 'required',
            'type_pay' => 'required',
        ], Lang::get('saybme.ub::validation'));

        $item->username = Input::get('username');
        $item->contacts = Input::get('contacts');
        $item->price = Input::get('price') !== null && Input::get('price') !== '' ? Input::get('price') : null;
        $item->type = Input::get('type');
        $item->type_pay = Input::get('type_pay');
        $item->is_active = (string) Input::get('is_active') === '1';

        $payload = Input::all();
        if (array_key_exists('description', $payload)) {
            $item->description = Input::get('description');
        }
        if (array_key_exists('urgently', $payload)) {
            $item->urgently = (string) Input::get('urgently') === '1';
        }

        $address = Input::get('address');
        if (is_array($address)) {
            $item->address = $address;
        }

        $props = is_array($item->props) ? $item->props : [];
        $incoming = Input::get('props');
        if (is_array($incoming)) {
            $props = array_replace($props, $incoming);
            if (isset($incoming['series']) && is_array($incoming['series'])) {
                $props['series'] = $incoming['series'];
                $item->series = implode('', $incoming['series']);
            }
            if (isset($incoming['number']) && is_array($incoming['number'])) {
                $props['number'] = $incoming['number'];
                $item->number = implode('', $incoming['number']);
            }
            if (array_key_exists('region', $incoming)) {
                $item->region = $incoming['region'];
            }
        }
        if ($item->contacts) {
            $props['phone'] = $item->contacts;
        }
        $item->props = $props;
        $item->save();

        if (files('photo')) {
            $item->photo = files('photo');
            $item->save();
        }

        return $item;
    }

}