<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Classes\App\AppClass;
use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Classes\Document\DocumentClass;
use Saybme\Ub\Classes\Cabinet\CabinetClass;
use Saybme\Ub\Models\User;
use Saybme\Ub\Models\Formrow;
use Saybme\Ub\Models\Formvalue;
use Saybme\Ub\Models\Ubform;
use Saybme\Ub\Models\Forminput;
use Saybme\Ub\Models\Prpage;
use Saybme\Ub\Models\Document;
use Saybme\Ub\Models\Payment;
use Saybme\Ub\Classes\Payment\PaymentClass;
use Request;
use ValidationException;
use Response;
use Input;
use Redirect;
use Flash;
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
            'mode' => [
                'title' => 'MODE',
                'description' => 'MODE страницы',
                'default' => '{{ :mode }}'
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
        //var_dump($type);
        return $this->$type();
    }

    private function getCabinet(){

        // SLUG страницы
        $slug = $this->property('slug');

        //dd($slug);

        $options = array();
        $tpl = 'cabinet/profile';

        $q = new AuthClass;
        $user = $q->getActiveUser();


        if(!$user) {
            $tpl = 'cabinet/404';
            return $this->renderPartial($tpl, $options);
        }

        if($user->isManager()){
            return $this->renderManagerCabinet($user, $q, 'applications');
        }

        $options['user'] = $user;
        $options['servises'] = $q->getTypeServises();
        $options['pages'] = $q->getUserPages();
        $options['pservises'] = $q->getCabinetServises();



        // Страница документ
        $document = Document::where('hash', $slug)->first();
        if($document){
            $type = Input::get('type');
            // PDF
            if($type == 'pdf'){
                return DocumentClass::pdf($document);
            }
            $options['type'] = $type;
            $options['document'] = $document;
            $tpl = 'cabinet/profile-document';

            if($document->form->tpl){
                // Шаблон формы
                $tpl = $document->form->tpl;
            }

        }

        //dd($tpl);

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

        if($user->isManager()){
            $carnumberId = $this->managerCarnumberIdFromSlug($slug);
            if ($carnumberId) {
                return $this->renderManagerCarnumber($user, $carnumberId);
            }

            $section = $this->managerSectionFromSlug($slug);
            if ($section) {
                return $this->renderManagerCabinet($user, $q, $section);
            }
        }

        if(!$page) return $this->controller->run('404');

        $this->page->title = $page->menutitle ?: $page->name;

        if($page->tmp) $tpl = $page->tmp;

        if($user->isManager()){
            if($page->ptype != 'app'){
                return $this->controller->run('404');
            }
        }

        //var_dump($tpl);

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


        //var_dump($tpl);

        $options['content'] = $this->renderPartial($tpl, $options);



        return $this->renderPartial('cabinet/wrap', $options);
    }

    // Оплата услуги
    public function onPay(){

        $data = Input::get();

        $messages = [
            'required' => 'Обязательное поле.',
            'description.required' => 'Заполните поле описание.',
            'sum.min' => 'Минимальная сумма оплаты :min руб.'
        ];

        // Валидация
        Request::validate([
            'user' => 'required',
            'description' => 'required',
            'sum' => 'required'
        ], $messages);

        // Проверка суммы
        if($data['sum'] < 100){
            throw new ValidationException(['sum' => 'Минимальная сумма оплаты 100 руб.']);
        }

        // Создаем платеж
        $payment = PaymentClass::add($data);

        // Ссылка на нлатеж
        $qpay = new PaymentClass;
        $SessionId = $qpay->initPay($payment);

        // Редирект по ссылке
        $link = 'https://secure.payture.com/apim/Pay?SessionId=' . $SessionId;

        // Сохраняем ссылку
        $payment->link = $link;
        $payment->save();

        return redirect($link);
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

    public function onUpdateCarnumber()
    {
        $q = new AuthClass;
        $user = $q->getActiveUser();
        if (!$user || !$user->isManager()) {
            throw new ValidationException([
                'id' => 'Редактировать объявление может только менеджер.',
            ]);
        }

        $app = new AppClass;
        $item = $app->updateGosNum();

        Flash::success('Объявление сохранено.');
        return Redirect::to($item->cabinet_link);
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

    // Создаем заявку
    public function onDocument(){
        $q = new DocumentClass();
        $document = $q->create();
        // Ссылка
        $link = $document->link . '/open';
        return Redirect::to($link);
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

    // Обновление блоков
    function onRefreshPartial(){
        $data = Input::get();
        return $data;
    }


    private function renderManagerCabinet($user, $q, $section = 'applications')
    {
        if (!in_array($section, ['applications', 'ads'], true)) {
            $section = 'applications';
        }

        $this->page->title = $section === 'ads' ? 'Объявления' : 'Заявки';

        return $this->renderPartial('cabinet/manager', [
            'user' => $user,
            'section' => $section,
            'applications' => $q->getUserApplicationsManager($user->id),
            'announcements' => [
                [
                    'code' => 'carnumbers',
                    'title' => 'Гос. номера',
                    'empty' => 'Объявлений с гос. номерами пока нет',
                    'items' => $q->getGosNumbersManager(),
                ],
            ],
        ]);
    }

    private function managerSectionFromSlug($slug)
    {
        $value = $this->normalizeCabinetSlug($slug);

        $map = [
            'zayavki' => 'applications',
            'obyavleniya' => 'ads',
            'ads' => 'ads',
        ];

        return $map[$value] ?? null;
    }

    private function managerCarnumberIdFromSlug($slug)
    {
        $value = $this->normalizeCabinetSlug($slug);

        if (preg_match('#^obyavleniya/nomer/(\d+)$#', $value, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function normalizeCabinetSlug($slug)
    {
        if (is_array($slug)) {
            return trim(implode('/', $slug), '/');
        }

        return trim((string) $slug, '/');
    }

    private function renderManagerCarnumber($user, $id)
    {
        $page = \Saybme\Ub\Models\Carnumber::with('user')->find($id);
        if (!$page) {
            return $this->controller->run('404');
        }

        $title = $page->car_number ?: ($page->num ?: ('Объявление #' . $page->id));
        $this->page->title = $title;

        return $this->renderPartial('cabinet/manager-ad-carnumber', [
            'user' => $user,
            'page' => $page,
            'section' => 'ads',
            'breadcrumbs' => [
                [
                    'title' => 'Объявления',
                    'url' => url('/cabinet/obyavleniya'),
                ],
                [
                    'title' => $title,
                    'isActive' => 'active',
                ],
            ],
        ]);
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
