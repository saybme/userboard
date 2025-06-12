<?php namespace Saybme\Ub;

use System\Classes\PluginBase;
use Saybme\Ub\Classes\Rules\SmsRule;
use Saybme\Ub\Classes\Rules\PhoneRule;
use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\Formvalue;
use System\Models\File;

/**
 * Plugin class
 */
class Plugin extends PluginBase
{
    
    public function register() {
        $this->registerValidationRule('sms', SmsRule::class); // Проверка СМС кода
        $this->registerValidationRule('phone', PhoneRule::class); // Проверка номере телефона
    }

    public function registerMarkupTags() {
        return [
            'filters' => [
                'phone' => [$this, 'formatPhone'],
                'formvalues' => [$this, 'getFormValues'],
                'formvalue' => [$this, 'getFormValue'],
                'ubtitle' => [$this, 'ubTitle'],
                'getPhoto' => [$this, 'getPhoto'],
                'dob' => [$this, 'getDob'],
            ]
        ];
    }

    // Дата рождения
    public function getDob($value = ''){
        if(!$value) return;

        $arr = json_decode($value, true);

        return implode(' ', $arr) . ' год.';
    }

    // Фото заявки
    public function getPhoto($value){
        $file = File::where('title', $value)->first();
        return $file;
    }

    // Заголовок USERBOARD
    public function ubTitle($value){
        if(!$value) return;
        $value = str_ireplace("board", "<span>BOARD</span>", $value);
        return $value;
    }

    // Значения полей
    public function getFormValues($id){
        if(!$id) return;
        $obj = Formvalue::find($id);
        if(!$obj) return false;
        return $obj->values;
    }

    // Значение поля
    public function getFormValue($id){
        if(!$id) return;
        $obj = Formvalue::find($id);
        if(!$obj) return false;
        return $obj->title;
    }

    // ФОрмат телефона
    public function formatPhone($phone = ''){
        if(!$phone) return;
        $phone = preg_replace("/[^0-9]/", '', $phone);
        return $phone;
    }
   
    public function boot() {

        \Event::listen('cms.page.init', function($controller) {
            $q = new AuthClass;
            $controller->vars['auth'] = $q->getAuth();
        });


        \Event::listen('cms.pageLookup.listTypes', function() {
            return [              
                'ub-page-cabinet' => 'UB страница кабинета',
                'ub-form' => 'UB форма'
            ];
        });

        \Event::listen('pages.menuitem.listTypes', function() {
            return [              
                'ub-page-cabinet' => 'UB страница кабинета',
                'ub-form' => 'UB форма'
            ];
        });
        
    
        \Event::listen(['cms.pageLookup.getTypeInfo', 'pages.menuitem.getTypeInfo'], function($type) {
            if ($type == 'ub-form') {
                return Models\Ubform::getMenuTypeInfo($type);
            }
            if ($type == 'ub-page-cabinet') {
                return Models\Prpage::getMenuTypeInfo($type);
            }
        });
    
        \Event::listen(['cms.pageLookup.resolveItem', 'pages.menuitem.resolveItem'], function($type, $item, $url, $theme) {
            if ($type == 'ub-form') {
                return Models\Ubform::resolveMenuItem($item, $url, $theme);
            }
            if ($type == 'ub-page-cabinet') {
                return Models\Prpage::resolveMenuItem($item, $url, $theme);
            }
        });

    }
    
    public function registerComponents() {
        return [
            \Saybme\Ub\Components\Ubreview::class   => 'ubreview',
            \Saybme\Ub\Components\App::class        => 'app',
            \Saybme\Ub\Components\Cabinet::class    => 'cabinet',
            \Saybme\Ub\Components\Support::class    => 'support',
            \Saybme\Ub\Components\Uservises::class    => 'uservises',
            \Saybme\Ub\Components\Ubapps::class    => 'ubapps'
        ];
    }


    /**
     * registerSettings used by the backend.
     */
    public function registerSettings()
    {
    }
}
