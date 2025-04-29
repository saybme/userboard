<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Classes\App\SmsClass;
use Saybme\Ub\Classes\App\AppClass;
use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Classes\Cabinet\CabinetClass;
use Saybme\Ub\Models\User;
use Saybme\Ub\Models\Formrow;
use Saybme\Ub\Models\Formvalue;
use Saybme\Ub\Models\Ubform;
use Saybme\Ub\Models\Forminput;
use Saybme\Ub\Models\Prpage;
use Response;
use Input;
use Redirect;
use System\Classes\CombineAssets;
use Log;

class Cabinet extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Кабинет',
            'description' => 'Кабинет пользователя'
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
                'description' => 'Укажите тип вывода отзывов',
                'default' => 'getCabinet',
                'type' => 'dropdown',
                'options' => [
                    'getCabinet' => 'Профиль',
                    'getPage' => 'Страницы'
                ]               
            ]
        ];
    }
    
    function onRun(){
        $this->addScripts(); // Скрипты
        $this->addStyles(); // Стили
        $this->cabinet = $this->getContent();        
    }

    private function getContent(){
        $type = $this->property('type');                  
        return $this->$type();
    }

    private function getCabinet(){

        $sms = new SmsClass;

        $options = array();
        $tpl = 'cabinet/profile';

        $q = new AuthClass;
        $user = $q->getActiveUser();        

        

        if(!$user) {
            $tpl = 'cabinet/404';
            return $this->renderPartial($tpl, $options);
        }       

        if($user->utype->id == 2){
            $options['user'] = $user;
            $options['applications'] = $q->getUserApplicationsManager($user->id);  
            return $this->renderPartial('cabinet/manager', $options);
        }

        $options['user'] = $user;
        $options['servises'] = $q->getTypeServises();
        $options['pages'] = $q->getUserPages();    
        $options['pservises'] = $q->getCabinetServises();

        return $this->renderPartial($tpl, $options);
    }

    private function getPage(){

        $q = new AuthClass;
        $user = $q->getActiveUser();   
        
        //dd($user->toArray());        

        $options = array();
        $tpl = 'cabinet/default-text';
        $slug = $this->property('slug');
        
        $page = $q->getPage($slug);            
        
        
        if(!$user) {
            $tpl = 'cabinet/404';
            return $this->renderPartial($tpl, $options);
        }         

        if(!$page) return $this->controller->run('404');

        $this->page->title = $page->menutitle ?: $page->name;

        if($page->tmp) $tpl = $page->tmp;     
        
        // if($user->utype->id == 2){
        //     $tpl = 'applications/page-manager';
        // }

       
        // Страница форма
        if($page->form){
            $tpl = 'cabinetforms/form_' . $page->form->id;       
            $inputs = $page->form->inputs;        
            $options['form'] = $page->form;
            $arr = CabinetClass::getFormOptions($inputs);            
            $options = array_merge($options, $arr);
        }         
        
        $options['user'] = $user;
        $options['page'] = $page;
        $options['pages'] = $q->getUserPages();
        $options['numbers'] = $q->getGosNumbers($user->id);
        $options['applications'] = $q->getUserApplications($user->id);          

        if($page->ptype == 'app'){
            $options['breadcrumbs'] = $this->cabinetBreadcrumbsApp($page);; 
        } else {
            $options['breadcrumbs'] = $this->cabinetBreadcrumbs($page); 
        }           
                
        $options['content'] = $this->renderPartial($tpl, $options); 

        return $this->renderPartial('cabinet/wrap', $options);
    }

    // Расчет стоимости
    public function onCalcFormSum(){

        $items = Input::get();
        $gde = Input::get('gde');       

        $options['sum'] = array_sum(array_column($items, 'price'));
        $options['items'] = $items;       

        $result['#resultSum'] = $this->renderPartial('cabinetforms/form_total', $options);
        return $result;       

        // $items = array();  
        // $formId = Input::get('form');   

        // // Форма 9
        // if(Input::get('ub444ad693f15f068221c47be0381e92f5') == 27){
        //     // ГИБДД
        //     if(Input::get('ube442f58019d82168c37b39726df8a975') == 5){
        //         $items[0]['name'] = 'госпошлина за выдачу регистрационных знаков на автомобиль или автобус 2000 рублей';
        //         $items[0]['price'] = 2000;  
        //     }
        //     if(Input::get('ube442f58019d82168c37b39726df8a975') == 6){
        //         $items[0]['name'] = 'госпошлина за выдачу регистрационных знаков мотоцикл или прицеп 1500 рублей';
        //         $items[0]['price'] = 1500;  
        //     }
        //     if(Input::get('ube442f58019d82168c37b39726df8a975') == 7){
        //         $items[0]['name'] = 'госпошлина за выдачу регистрационных знаков мотоцикл или прицеп 1500 рублей';
        //         $items[0]['price'] = 1500;  
        //     }
        // }      

        // // Поля формы
        // $rows = Input::get();              

        // $rows_1['name'] = 'госпошлина за выдачу свидетельства о регистрации ТС 500 рублей';
        // $rows_1['price'] = 500;

        // array_push($items, $rows_1);       

        // $options['sum'] = array_sum(array_column($items, 'price'));
        // $options['items'] = $items;

        // $result['#resultSum'] = $this->renderPartial('cabinetforms/form_total', $options);
        // return $result;
    }

    // Поиск в массиве
    private function in_array_r($prices, $value) {
        
        $rows = array();
        foreach($prices as $price){
            if(key_exists('srv_id',$price)){
                $rows[$price['srv_id']] = $price; 
            }            
        }

        if(!key_exists($value, $rows)) return;
        return $rows[$value];
    }

    // Хлебные крошки кабинета
    private function cabinetBreadcrumbs($page){        
        if(!$page) return;
        if(!$page->parent) return;

        $items = array();
        
        if($page->ptype == 'page'){
            $items = $page->getParentsAndSelf()->where('hide_breadcrumbs', null);
        }        

        if(count($items)){
            $items->each(function ($item, $key) use ($page) {
                $item->title = $item->name;
                $item->url = $item->link;
                $item->isActive = $page->id === $item->id;
            });
        }        

        return $items;
    }

    // Хлебные крошки объявления
    private function cabinetBreadcrumbsApp($page){
        $data = $page->data;
        if(!key_exists('page_id', $data)) return;

        // ID страницы
        $page_id = $data['page_id'];
        $obj = Prpage::active()->find($page_id);

        $items = $obj->getParentsAndSelf()->where('hide_breadcrumbs', null);

        if(count($items)){
            $items->each(function ($item, $key) use ($page) {
                $item->title = $item->name;
                $item->url = $item->link;
                $item->isActive = $page->id === $item->id;
            });
        } 

        return $items;
    }

    // Получаем контент странтицы
    public function onGetCabinetContent(){
        $id = Input::get('tab');

        $q = new AuthClass;
        $options['content'] = $q->getContentPage($id);

        // Контент
        $result['#content-result'] = $this->renderPartial('pagecabinet/content', $options);
        return $result;
    }

    // Сохраняем данные профиля
    function onSaveProfile(){
        $data = Input::get();

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser(); 
        $user->fill($data);
        $user->save();

        return $user;
    }

    // Создаем заявку
    function onCreateApp(){
        $data = Input::get();
        return $data;
    }

    // Меняем статус заявки
    function onStatusApp(){

        $q = new AuthClass;
        $obj = $q->setStatusApp();

        $options['page'] = $obj;

        $result['#statusApp'] = $this->renderPartial('app-page/status', $options);
        return $result;
    }

    public function onModalSupport(){   
        $result['alert'] = 'success';
        $result['modal'] = $this->renderPartial('support/modal');
        return $result;
    }

    public function onCreateSupMessage(){
        $q = new AuthClass;      
        return $q->addSubTheme();
    }

    public function onCreateMessage(){
        $q = new AuthClass;    
        return $q->addMessage();
    }

    // Создаем оббъявление
    function onAddApp(){
        $mode = Input::get('mode');
        $result = null;

        if($mode == 'carnumber'){
            $q = new AppClass;
            $options['data'] = $q->createGosNum();
            $result['#result-form'] = $this->renderPartial('appmessages/success', $options);
            return $result;
        }
        
        return;
    }

    // onTypeGosNumber выбираем тип гос номера
    function onTypeGosNumber(){
        $options['type'] = Input::get('type');
        $result['#typeGosNumber'] = $this->renderPartial('formtemplates/gos-number-type', $options);
        return $result;
    }

    // Вкладки формы
    public function onGetTab(){

        $tab = Input::get('tab');
        $formId = Input::get('form');

        $q = new AuthClass;
        $form = $q->getFormId($formId);

        $content = $form->tabs[$tab];

        $options['text'] = $content['text'];
        $result['#tab-result'] = $this->renderPartial('contents/tab-content', $options);
        return $result;
    }

    // Отправляем форму
    public function onOrderForm(){       

        $q = new AppClass;
        $data = $q->formSend();

        $result['data'] = $data;
        $result['#form_result'] = $this->renderPartial('form/order-success');
        return $result;
    }

    // Получаем ajax блоки формы
    public function onGetAjaxFormWrap(){
        $id = Input::get('id');
        $wrap = Input::get('wrap');        

        $obj = Formrow::where('id', $id)->first();
        if(!$obj) return;

        $options['box'] = $obj;

        $box = '#' . $wrap;
        $result[$box] = $this->renderPartial('form-rows/ajaxwrap', $options);

        return $result;
    }

    // Создаем заявку
    public function onApp(){
        $q = new AppClass();
        $app = $q->create();            
        return Redirect::to($app->link);
    }

    // Замена блоки формы
    function onGetPartial(){

        $data = Input::get();
        $formId = Input::get('form');
        $partial = Input::get('partial');

        $form = Ubform::find($formId);
        if(!$form) return;

        foreach($form->inputs as $row){
            $options[$row->code] = $row;   
        }

        $options['form'] = $form;
        $options['value'] = Input::get('value');

        $result[$data['container']] = $this->renderPartial($partial, $options);
        return $result;
    }


    private function addScripts(){
        // $scripts[] = 'saybme/ub/assets/javascript/support.js';
        // $scripts[] = 'saybme/ub/assets/javascript/dropzone/dropzone-min.js';
        // $this->addJs(CombineAssets::combine($scripts, plugins_path())); 
    }

    private function addStyles(){
        // $styles[] = 'saybme/ub/assets/styles/dropzone/dropzone.css';
        // $this->addCss(CombineAssets::combine($styles, plugins_path())); 
    }

    public $cabinet;

}
