<?php namespace Saybme\Ub\Models;

use October\Rain\Database\ExpandoModel;
use Log;

class Formrow extends ExpandoModel
{  

    use \October\Rain\Database\Traits\Sortable;
   
    public $table = 'saybme_ub_formrows';

    protected $expandoPassthru = ['parent_id', 'sort_order'];

    public $attachMany = [
        'photos' => \System\Models\File::class,
    ];

    public function beforeCreate() {    
        $hash = 'ub_' . md5(time());     
        $this->hash = $hash;
    }

    public function beforeSave() { 
        //Log::error($this);
        //$this->hash = 'ub_' . md5($this->id);   
    }

    function getClasses(){
        $options['ub-flex'] = 'ub-flex';
        $options['ub-grid'] = 'ub-grid';
        $options['ub-grid-2'] = 'ub-grid-2';
        $options['ub-grid-3'] = 'ub-grid-3';
        $options['ub-grid-4'] = 'ub-grid-4';
        return $options;
    }

    function getTypeOptions(){
        $options['text'] = 'Текст';
        $options['number'] = 'Число';
        $options['date'] = 'Дата';
        $options['email'] = 'E-mail';
        $options['tel'] = 'Телефон';
        $options['address'] = 'Адрес';

        return $options;
    }

    // Типы вывода
    function getUbtypeOptions(){
        $options['text'] = 'Текст';
        $options['formvalue'] = 'Значние формы';
        $options['array'] = 'Массив';
        $options['radio'] = 'Радиокнопки';
        $options['checkbox'] = 'Чекбоксы';
        $options['invalue'] = 'Внешние значения';
        $options['date'] = 'Дата';
        $options['date_time'] = 'Дата и время';
        $options['hide'] = 'Скрытое поле';
        $options['dropzone'] = 'Dropzone';

        return $options;
    }

    // Мультиполе
    function getMultyTypesOptions(){
        $options['id_document'] = 'Документ, удостоверяющий личность';
        $options['power_attorney'] = 'Сведения о доверенности';
        $options['data_time'] = 'Дата и время';
        return $options;
    }
    
    public $hasMany = [
        'childrens' => [
            Formrow::class,
            'key' => 'parent_id',
            'delete' => true
        ],
        'rows' => [
            Formrow::class,
            'key' => 'parent_id',
            'delete' => true
        ]
    ];  

}
