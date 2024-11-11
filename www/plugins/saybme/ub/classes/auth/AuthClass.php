<?php namespace Saybme\Ub\Classes\Auth;

use Saybme\Ub\Classes\App\SmsClass;
use Saybme\Ub\Classes\App\AppClass;
use Saybme\Ub\Classes\App\AppformClass;
use Saybme\Ub\Models\User;
use Saybme\Ub\Models\Ubform;
use Saybme\Ub\Models\Servise;
use Saybme\Ub\Models\Prpage;
use Saybme\Ub\Models\Suptheme;
use Saybme\Ub\Models\Message;
use Saybme\Ub\Models\Srvcategory;
use Saybme\Ub\Models\Form;
use Saybme\Ub\Models\Application;
use Saybme\Ub\Models\Formrow;
use Saybme\Ub\Models\Carnumber;
use Saybme\Ub\Models\Forminput;
use ValidationException;
use Request;
use Input;
use Log;
use Lang;
use Session;
use Cookie;
use Redirect;

class AuthClass {

    // Сохраянем HASH пользователя в куки
    private function saveUserHash($value = ''){
        if(!$value) return;
        $name = 'auth';
        $minutes = 43200;
        Cookie::queue($name, $value, $minutes);
    }

    // Проверка авторизации
    public function getAuth(){
        if(Cookie::has('auth')){            
            return Cookie::get('auth');
        }        
        return false;
    }

    // Получаем данные пользователя
    public function getActiveUser(){
        $hash = $this->getAuth();
        if(!$hash){
            return;
        }
        $user = User::active()->where('hash', $hash)->first();
        return $user;    
    }

    // Получаем сессию для авторизации
    public function getAuthSession(){
        if (!Session::has('auth')) return;        
        return Session::get('auth');    
    }

    // Шаг 1 проверка номере телефона и согласия на обработку данных
    public function authStepOne(){

        $username = Input::get('phone');
        $user = User::active()->where('login', $username)->where('utype_id', 2)->first();

        if($user) {
            Session::put('auth.utype', 2);
            Session::put('auth.phone', $username);
            return;  
        }          

        // Валидация пользователя
        $rules['phone'] = 'required|min:11';
        $rules['check'] = 'accepted';
        Request::validate($rules, Lang::get('saybme.ub::validation'));   
        
        Session::put('auth.utype', 1);
    }  

    // Шаг 3 проверка пользователя
    public function authStepTwo() {  

        $q = new AppClass;
        $phone = $q->setPhone(Input::get('phone')); 

        $user = User::where('phone', $phone)->first();
        return $user;
    }

    // Сохраняем смс и телефон в сессию
    public function saveContactUser(){

        $utype = Session::get('auth.utype');
        if($utype == 2) return;

        $q = new AppClass;
        $qSms = new SmsClass;
        
        $code = rand(1000, 9999);
        $phone = $q->setPhone(Input::get('phone'));    

        Session::put('auth.phone', $phone);
        Session::put('auth.sms', $code);     
        
        $qSms->sendSms($phone, $code);

    }

    // Проверка смс кода и автоизация пользователя
    public function authStepThree(){       
        
        // if($utype == 2){
        //     $phone = Session::get('auth.phone');
        //     $user = User::where('login', $phone)->first();
        //     $this->saveUserHash($user->hash);
        //     return;
        // };

        $rules['sms'] = 'required|sms';
        Request::validate($rules, Lang::get('saybme.ub::validation')); 

        $this->createUser();
        
    }

    // Авторизация пользователя
    public function authStepFour(){     
        
        $utype = Session::get('auth.utype');

        if($utype == 2){
            $phone = Session::get('auth.phone');
            $user = User::where('login', $phone)->first();
            // Сохраняем пользователя
            $this->saveUserHash($user->hash);
            return;
        };       
       

        $rules['sms'] = 'required|sms';
        Request::validate($rules, Lang::get('saybme.ub::validation')); 

        $phone = Session::get('auth.phone');
        $user = User::where('phone', $phone)->first();

        $this->saveUserHash($user->hash);

    }

    // Создаем пользователя
    public function createUser(){

        $auth = $this->getAuthSession();    
        
        $password = 123456789;
        
        $data['is_active'] = true;
        $data['phone'] = $auth['phone'];
        $data['password'] = $password;
        $data['password_confirmation'] = $password;

        $user = new User;
        $user->fill($data);
        $user->save();

    }

    // Возвращаем услуги пользователя
    public function getUserServises($user = ''){
        if(!$user) return;
        $servises = Servise::active()->get();
        return $servises;
    }

    // Возвращаем типы услуг
    public function getTypeServises(){
        // $items = Srvcategory::get();
        // return $items;
        return;
    }

    // Услуги кабинета
    public function getCabinetServises(){
        $pages = Prpage::active()->whereIn('id', [6,7,68])->get();
        return $pages;
    }

    // Меняем статус заявки
    public function setStatusApp(){
        $id = Input::get('id');
        $obj = Application::find($id);

        // Меняем статус
        $obj->status = Input::get('status');
        $obj->save();

        return $obj;
    }

    // Получаем заявки пользователя
    public function getUserApplications($id = ''){       
        if(!$id) return;
        return Application::where('user_id', $id)->get()->sortDesc();
    }   

    // Получаем заявки менеджера
    public function getUserApplicationsManager($id = ''){       
        if(!$id) return;
        return Application::get()->sortDesc();
    }  

    // Странцы профиля
    public function getUserPages(){
        $pages = Prpage::active()->where('parent_id', 5)->get();
        return $pages;
    }

    // Гос номера
    public function getGosNumbers($id = null){
        if(!$id) return;
        $items = Carnumber::active();
        return $items->orderBy('id', 'desc')->get();
    }

    // Контент страницы профиля
    public function getContentPage($id = null){
        if(!$id) return;
        $page = Prpage::active()->find($id);
        return $page->content;
    }

    // Страница профиля
    public function getPage($slug = ''){
        if(!$slug) return;

        //Log::error($slug);

        $page = Prpage::active()->where('slug', $slug)->first();        

        if($page){
            $page->ptype = 'page';
            return $page;
        } 

        $page = Suptheme::active()->where('url', $slug)->first();

        if($page) {
            $page->tmp = 'support/message';
            $page->ptype = 'theme';
            return $page;
        } 

        // Заявки
        $page = Application::where('url', $slug)->first();
        if($page){
            $page->tmp = 'applications/page';
            $page->ptype = 'app';
            $page->appcontent = $this->getAppContent($page);
            return $page;    
        }

        // Формы
        // $page = Form::active()->where('url', $slug)->first();
        // if($page) {
        //     $page->tmp = $page->tmp ?: 'forms/cabinet-form';
        //     return $page;
        // } 

        // Форма новая Ubform      
        $page = Ubform::active()->where('url', $slug)->first();
        if($page) {
            $page->tmp = 'forms/wrap-form';
            $page->ptype = 'form';
            // if(Session::has('ubform.' . $page->id)){
            //     $page->formdata = Session::get('ubform.'.$page->id);
            // }            
        } 

        return $page;
    }

    // Контент заявок
    private function getAppContent($page){
        if(!$page) return;

        // Данын формы
        $data = $page->data;
        unset($data['form']);

        $rows = array();

        foreach($data as $key => $item){
            $obj = Forminput::where('value->hash', $key)->first();
            if($obj){
                $arr['title'] = $obj->title;
                $arr['value'] = $item;                
                
                //$rows[$obj->parent_id]['group'] = $arr;
                $rows[$obj->parent_id]['items'][$key] = $arr;
            }            
        }        
        
        return collect($rows);
    }

    // Получаем раздел формы
    private function getFormGroup($id = null){
        if(!$id) return;
        $obj = Formrow::find($id);
        if(!$obj) return;
        return $obj->ptitle ?: $obj->title;
    }

    

    // Значение формы УДМЛИТЬ
    private function getValueForm($item, $type, $group = null){
        if(!$item) return;

        $objGroup = Formrow::find($group);
        
        $data = array();

        if($objGroup)
            $data['title'] = $objGroup->ptitle ?: $objGroup->title;

        if($type == 'formvalue'){
            $obj = Formrow::find($item);
            if(!$obj) return;           
            $data['value'] = $obj->title;              
        }

        if($type == 'radio'){
            $obj = Formrow::find($item);
            if(!$obj) return; 
            return $obj->title;             
        }       

        return collect($data);
    }

    // Получаем форму по ID
    public function getFormId($id = ''){
        if(!$id) return;
        $form = Form::active()->find($id);
        return $form;
    }

    // Получаем форму по ID
    public function getUbFormId($id = ''){
        if(!$id) return;
        $form = Ubform::active()->find($id);
        return $form;
    }

    // Создаем тему обращения и сообщение
    public function addSubTheme(){   
        
        if(!Input::get('comment')) throw new ValidationException(['comment' => 'Введите текст вопроса.']);

        $user = $this->getActiveUser();        

        $themeArr['user'] = $user->id;
        $themeArr['is_active'] = true;

        $theme = new Suptheme;
        $theme->fill($themeArr);
        $theme->save();

        $data = Input::get();
        $data['user'] = $user->id;
        $data['suptheme'] = $theme->id;      
        
        $this->createMessage($data);

        $url = $theme->link;
        return Redirect::to($url);  
    }

    // Создаем сообщение в теме
    public function createMessage($data = array()) {  
        $message = new Message;
        $message->fill($data);
        $message->save();
    }

    // Создаем сообщение в теме
    public function addMessage() {  
        $user = $this->getActiveUser(); 

        $data = Input::get();
        $data['user'] = $user->id;

        $message = new Message;
        $message->fill($data);
        $message->save();
    }

}