<?php namespace Saybme\Ub\Models;

use Model;
use Log;

class Forminput extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    public $table = 'saybme_ub_forminputs';

    protected $jsonable = ['prices'];

    public $rules = [
        'title' => 'required'
    ];

    public function beforeValidate()
    {
        //Log::error($this->form);
    }    

    public function beforeCreate() {
        $this->code = 'ub' . md5(time());
    }

    public $belongsTo = [
        'value' => [
            \Saybme\Ub\Models\Formvalue::class,
            'scope' => 'isDepth'
        ],
        'form' => \Saybme\Ub\Models\Ubform::class,
    ];

    public function getValuesAttribute(){
        if(!$this->value) return;
        
        return $this->value->children;
    }

    // Тип поля
    public function getTypeOptions(){

        $options['text'] = 'Строка';
        $options['file'] = 'Файл';
        $options['relation'] = 'Связь';
        $options['date'] = 'Дата';
        $options['datetime-local'] = 'Дата / Время';
        $options['address'] = 'Адрес';
        $options['tel'] = 'Телефон';
        $options['email'] = 'E-mail';

        return $options;
    }

}
