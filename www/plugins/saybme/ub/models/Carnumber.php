<?php namespace Saybme\Ub\Models;

use Model;
use Input;
use Log;

class Carnumber extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $fillable = [
        'number',
        'user',
        'type',
        'pay',
        'urgently',        
        'username',
        'contacts',
        'type_price',
        'sum_start',
        'sum_end',
        'props',
        'price',
        'address',
        'is_active',
        'series',
        'region'
    ];

    protected $jsonable = ['address','props'];
   
    public $table = 'saybme_ub_carnumbers';
    
    public $rules = [
        'user' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }  

    public function beforeCreate(){
        
        // Серия
        $series = Input::get('props.series');
        $this->series = implode('', $series);

        // Номер
        $number = Input::get('props.number');
        $this->number = implode('', $number);

        // Регион
        $region = Input::get('props.region');
        $this->region = $region;

    }


    public function getLinkAttribute(){
        $arr[] = 'gossnumbers';
        $arr[] = $this->id;
        $url = implode('/', $arr);
        return url($url);
    }

    public function getCarNumberAttribute(){    

        $series = null;

        if(key_exists('series', $this->props)){
            // Серия        
            $series = implode('', $this->props['series']);
            $arr['series'] = substr($series, 0, 1);    
        }

        if(key_exists('number', $this->props)){
            // Номер
            $number = implode('', $this->props['number']);
            $arr['number'] = $number;    
        }

          
        
        // Серия
        $arr['series_2'] = substr($series, 1, 2);
        

        if(key_exists('region', $this->props)){
            // Регион
            $arr['region'] = $this->props['region'];   
        }
        
        return implode('-', $arr);
    }

    public $attachOne = [
        'photo' => \System\Models\File::class
    ];
    
    public function getTypeOptions(){
        $options[1] = 'Авто';
        $options[2] = 'Мото';
        $options[3] = 'Прицеп';
        return $options;
    }

}
