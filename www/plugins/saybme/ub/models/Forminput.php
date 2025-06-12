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
        $options['year'] = 'Год';
        $options['dob'] = 'Год рождения';

        return $options;
    }

    public function getDobAttribute(){

        $arr = array();
        $dob = date('Y', time()) - 18;

        for ($n = 1940; $n <= $dob; $n++) {
            $arr[$n] = $n;
        }
        krsort($arr);

        // Месяца
        $months[1] = 'Января';
        $months[2] = 'Февраля';
        $months[3] = 'Марта';
        $months[4] = 'Апреля';
        $months[5] = 'Мая';
        $months[6] = 'Июня';
        $months[7] = 'Июля';
        $months[8] = 'Августа';
        $months[9] = 'Сентября';
        $months[11] = 'Октября';
        $months[12] = 'Декабря';

        $result['months'] = $months;
        $result['years'] = $arr;

        return collect($result);
    }

    public function getYearsAttribute(){

        $arr = array();
        $dob = date('Y', time());

        for ($n = 1940; $n <= $dob; $n++) {
            $arr[$n] = $n;
        }
        krsort($arr);

        return collect($arr);
    }

}
