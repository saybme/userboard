<?php namespace Saybme\Ub\Classes\Auth;

use Saybme\Ub\Classes\Auth\SmsruClass;
use Saybme\Ub\Classes\App\AppClass;
use Saybme\Ub\Classes\App\AppformClass;
use Saybme\Ub\Models\User;
use Saybme\Ub\Models\Formvalue;
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
use Saybme\Ub\Models\EmailLoginToken;
use ValidationException;
use Request;
use Input;
use Log;
use Lang;
use Session;
use Cookie;
use Redirect;
use Mail;
use Carbon\Carbon;

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

    /**
     * Единое поле login → phone или email.
     */
    public function syncAuthIdentifier()
    {
        $login = trim((string) Input::get('login', ''));
        $phone = trim((string) Input::get('phone', ''));
        $email = trim((string) Input::get('email', ''));

        if ($login !== '') {
            if (str_contains($login, '@')) {
                $email = mb_strtolower($login);
                $phone = '';
            } else {
                $phone = $login;
                $email = '';
            }
        }

        if ($email !== '') {
            $email = mb_strtolower($email);
        }

        Input::merge([
            'phone' => $phone,
            'email' => $email,
            'login' => $login,
        ]);
    }

    // Шаг 1 проверка номере телефона и согласия на обработку данных
    public function authStepOne(){

        $qPhone = new AppClass;
        $username = Input::get('phone');
        $normalized = $qPhone->setPhone($username) ?: $username;

        $user = User::active()
            ->where(function ($query) use ($username, $normalized) {
                $query->where('login', $username);
                if ($normalized) {
                    $query->orWhere('login', $normalized);
                }
            })
            ->where('utype_id', 2)
            ->first();

        if($user) {
            Session::put('auth.utype', 2);
            Session::put('auth.phone', $user->login ?: $normalized);
            return;  
        }          

        // Валидация: после нормализации проверяем длину цифр
        if ($normalized) {
            Input::merge(['phone' => $normalized]);
        }

        $rules['phone'] = 'required|digits_between:11,15';
        $rules['check'] = 'accepted';
        $rules['verify_method'] = 'required|in:telegram,sms';
        Request::validate($rules, Lang::get('saybme.ub::validation'));   
        
        Session::put('auth.utype', 1);
        Session::put('auth.verify_method', Input::get('verify_method'));
    }  

    // Проверка, есть ли пользователь с таким телефоном
    public function authStepTwo() {  

        $q = new AppClass;
        $phone = $q->setPhone(Input::get('phone')); 

        $user = User::where('phone', $phone)->first();
        return $user;
    }

    // Создать Telegram Bot challenge и вернуть deep link
    public function startTelegramBotVerification(): string
    {
        $q = new AppClass;
        $phone = $q->setPhone(Input::get('phone'));

        if (!$phone) {
            throw new ValidationException([
                'phone' => 'Укажите корректный номер телефона.',
            ]);
        }

        $bot = new TelegramBot;
        return $bot->createChallengeAndDeepLink($phone);
    }

    // Сохраняем ожидаемый номер перед Telegram OAuth (старый OIDC, оставлен)
    public function prepareTelegramVerification()
    {
        $q = new AppClass;
        $phone = $q->setPhone(Input::get('phone'));

        Session::put('telegram_expected_phone', $phone);
        Session::put('auth.phone', $phone);
        Session::put('auth.utype', 1);
        Session::forget('phone_verification');
    }

    // Отправка SMS-кода (регистрация или вход)
    public function saveContactUser($isNew = false){

        $utype = Session::get('auth.utype');
        if($utype == 2) return;

        $q = new AppClass;
        $qSms = new SmsruClass;
        
        $code = rand(1000, 9999);
        $phone = $q->setPhone(Input::get('phone'));    

        $qSms->send($phone, 'Код для входа ' . $code);

        Session::put('auth.phone', $phone);
        Session::put('auth.sms', $code);
        Session::put('auth.is_new', (bool) $isNew);
        Session::put('auth.verify_method', 'sms');
    }

    /**
     * Получить актуальное подтверждение номера (telegram|sms).
     * Возвращает массив, 'expired' или null.
     */
    public function getPhoneVerification()
    {
        if (!Session::has('phone_verification')) {
            return null;
        }

        $verification = Session::get('phone_verification');
        $provider = $verification['provider'] ?? null;

        if (
            !is_array($verification) ||
            !in_array($provider, ['telegram', 'sms'], true) ||
            empty($verification['phone'])
        ) {
            Session::forget('phone_verification');
            return null;
        }

        if (($verification['expires_at'] ?? 0) < time()) {
            Session::forget('phone_verification');
            return 'expired';
        }

        return $verification;
    }

    // Проверка SMS-кода
    public function validateSmsCode()
    {
        $rules['sms'] = 'required|sms';
        Request::validate($rules, Lang::get('saybme.ub::validation'));
    }

    // После верного SMS для новой регистрации — сохраняем подтверждение
    public function markPhoneVerifiedBySms()
    {
        $phone = Session::get('auth.phone');
        if (!$phone) {
            throw new ValidationException([
                'phone' => 'Сессия подтверждения устарела. Начните сначала.',
            ]);
        }

        Session::put('phone_verification', [
            'provider' => 'sms',
            'phone' => $phone,
            'verified_at' => time(),
            'expires_at' => time() + 900,
        ]);

        Session::forget('auth.sms');
        Session::forget('auth.is_new');
    }

    // Регистрация после подтверждения телефона (Telegram или SMS)
    public function registerWithVerifiedPhone()
    {
        $verification = $this->getPhoneVerification();

        if ($verification === 'expired' || !$verification) {
            Session::forget('phone_verification');
            throw new ValidationException([
                'phone' => 'Срок подтверждения номера истёк. Подтвердите номер ещё раз.',
            ]);
        }

        $rules['password'] = 'required|min:8|confirmed';
        $rules['password_confirmation'] = 'required';
        Request::validate($rules, Lang::get('saybme.ub::validation'));

        $phone = $verification['phone'];

        if (User::where('phone', $phone)->exists()) {
            throw new ValidationException([
                'phone' => 'Пользователь с таким номером уже зарегистрирован. Войдите в аккаунт.',
            ]);
        }

        $data['is_active'] = true;
        $data['phone'] = $phone;
        $data['password'] = Input::get('password');
        $data['password_confirmation'] = Input::get('password_confirmation');

        if (($verification['provider'] ?? null) === 'telegram') {
            $data['profile'] = [
                'telegram_id' => $verification['telegram_id'] ?? null,
                'telegram_name' => $verification['name'] ?? null,
                'telegram_username' => $verification['username'] ?? null,
                'provider_user_id' => $verification['provider_user_id'] ?? null,
            ];
        }

        $user = new User;
        $user->fill($data);
        $user->utype_id = 1;
        $user->save();

        Session::forget('phone_verification');
        Session::forget('telegram_expected_phone');
        Session::put('auth.phone', $phone);
        Session::put('auth.utype', 1);

        $this->saveUserHash($user->hash);

        return $user;
    }

    // Вход существующего пользователя по подтверждённому телефону
    public function loginByPhone($phone)
    {
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return null;
        }

        Session::forget('phone_verification');
        Session::forget('telegram_expected_phone');
        Session::put('auth.phone', $phone);
        Session::put('auth.utype', 1);
        $this->saveUserHash($user->hash);

        return $user;
    }

    // Вход по email и паролю
    public function loginByEmail()
    {
        $email = mb_strtolower(trim((string) Input::get('email')));
        Input::merge(['email' => $email]);

        $rules['email'] = 'required|email';
        $rules['password'] = 'required|min:8';
        Request::validate($rules, Lang::get('saybme.ub::validation'));

        $password = Input::get('password');

        $user = User::active()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user || !$user->password || !$user->checkHashValue('password', $password)) {
            throw new ValidationException([
                'email' => 'Неверный email или пароль.',
            ]);
        }

        Session::put('auth.email', $email);
        Session::put('auth.utype', 1);
        $this->saveUserHash($user->hash);

        return $user;
    }

    // Регистрация по email и паролю
    public function registerByEmail()
    {
        $email = mb_strtolower(trim((string) Input::get('email')));
        Input::merge(['email' => $email]);

        $rules['email'] = 'required|email|unique:saybme_ub_users,email';
        $rules['password'] = 'required|min:8|confirmed';
        $rules['password_confirmation'] = 'required';
        $rules['check'] = 'accepted';
        Request::validate($rules, Lang::get('saybme.ub::validation'));

        $data['is_active'] = true;
        $data['email'] = $email;
        $data['password'] = Input::get('password');
        $data['password_confirmation'] = Input::get('password_confirmation');

        $user = new User;
        $user->fill($data);
        $user->utype_id = 1;
        $user->save();

        Session::put('auth.email', $email);
        Session::put('auth.utype', 1);
        $this->saveUserHash($user->hash);

        return $user;
    }

    /**
     * Отправка одноразовой ссылки для входа без пароля.
     */
    public function sendEmailLoginLink()
    {
        $email = mb_strtolower(trim((string) Input::get('email', Input::get('login', ''))));
        Input::merge(['email' => $email]);

        $rules['email'] = 'required|email';
        Request::validate($rules, Lang::get('saybme.ub::validation'));

        $user = User::active()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user) {
            throw new ValidationException([
                'email' => 'Пользователь с таким email не найден.',
            ]);
        }

        $recent = EmailLoginToken::where('email', $email)
            ->where('created_at', '>', Carbon::now()->subMinute())
            ->exists();

        if ($recent) {
            throw new ValidationException([
                'email' => 'Ссылка уже отправлена. Проверьте почту или подождите минуту.',
            ]);
        }

        // Старые неиспользованные ссылки больше не действуют
        EmailLoginToken::where('email', $email)
            ->where('status', EmailLoginToken::STATUS_PENDING)
            ->update(['status' => EmailLoginToken::STATUS_EXPIRED]);

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $record = new EmailLoginToken;
        $record->user_id = $user->id;
        $record->email = $email;
        $record->token_hash = hash('sha256', $token);
        $record->status = EmailLoginToken::STATUS_PENDING;
        $record->ip_address = Request::ip();
        $record->expires_at = Carbon::now()->addMinutes(15);
        $record->save();

        $link = url('/auth/email/login?token=' . rawurlencode($token));

        try {
            Mail::send('saybme.ub::mail.login_link', ['link' => $link], function ($message) use ($email) {
                $message->to($email);
            });
        } catch (\Throwable $e) {
            Log::error('Email login link send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new ValidationException([
                'email' => 'Не удалось отправить письмо. Попробуйте позже или войдите с паролем.',
            ]);
        }

        return $email;
    }

    /**
     * Вход по одноразовой ссылке из письма.
     */
    public function loginByEmailToken(string $token)
    {
        $token = trim($token);
        if ($token === '') {
            return 'invalid';
        }

        $record = EmailLoginToken::where('token_hash', hash('sha256', $token))->first();
        if (!$record) {
            return 'invalid';
        }

        if ($record->status === EmailLoginToken::STATUS_USED) {
            return 'used';
        }

        if ($record->status !== EmailLoginToken::STATUS_PENDING || $record->isExpired()) {
            $record->status = EmailLoginToken::STATUS_EXPIRED;
            $record->save();
            return 'expired';
        }

        $user = User::active()->find($record->user_id);
        if (!$user) {
            $user = User::active()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($record->email)])
                ->first();
        }

        if (!$user) {
            $record->status = EmailLoginToken::STATUS_EXPIRED;
            $record->save();
            return 'invalid';
        }

        $record->status = EmailLoginToken::STATUS_USED;
        $record->used_at = Carbon::now();
        $record->save();

        Session::regenerate();
        Session::put('auth.email', mb_strtolower((string) $user->email));
        Session::put('auth.utype', 1);
        $this->saveUserHash($user->hash);

        return $user;
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
       

        $this->validateSmsCode();

        $phone = Session::get('auth.phone');
        $user = User::where('phone', $phone)->first();

        $this->saveUserHash($user->hash);

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
        $pages = Prpage::active()->where('parent_id', 5)->whereNull('hide_breadcrumbs')->get();
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
            $obj = Forminput::where('code', $key)->first();
            if($obj){

                $arr['type'] = $obj->type ?: 'string';
                $arr['title'] = $obj->app_title ?: $obj->title;                
                $arr['group'] = $obj->group_title; 

                if(gettype($item) == 'array'){
                    $arr['value'] = json_encode($item); 
                } else {
                    $arr['value'] = $item;  
                }

                if($obj->value){
                    $arr['value'] = Formvalue::find($item)->title;    
                }

                // Тип адрес
                if($obj->type == 'address'){
                    $arr['value'] = $this->getAddressTitle($item);      
                }

                // Тип связь
                // if($obj->type == 'relation'){
                //     $arr['value'] = 121;  
                // }                

                $rows[$obj->group_title]['items'][] = $arr; 
                                 
            }            
        }  

        //dd($rows);
        
        return collect($rows);
    }

    // Адрес
    private function getAddressTitle($arr = array()){
        if(!count($arr)) return;

        $titles['region'] = 'Регион';
        $titles['city'] = 'Город';
        $titles['street'] = 'Улица';
        $titles['district'] = 'Район';
        $titles['house'] = 'Дом';
        $titles['build'] = 'Строение';
        $titles['flat'] = 'Квартира';
        $titles['noflat'] = 'Без квартиры';

        $rows = array();
        foreach($arr as $key => $item){
            if(trim($item)){
                $rows[$titles[$key]] = $item;
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