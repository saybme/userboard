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
        'region',
        'type_pay',
        'description'
    ];

    protected $jsonable = ['address','props'];
   
    public $table = 'saybme_ub_carnumbers';
    
    public $rules = [
        'user' => 'required',
        'type_pay' => 'required'
    ];

    public $belongsTo = [
        'user' => [
            \Saybme\Ub\Models\User::class,
            'key' => 'user_id',
        ],
    ];

    public $belongsToMany = [
        'managers' => [
            \Saybme\Ub\Models\User::class,
            'table' => 'saybme_ub_carnumber_managers',
            'key' => 'carnumber_id',
            'otherKey' => 'user_id',
        ],
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }  

    public function getClientLabelAttribute()
    {
        if (!$this->user) {
            return 'Без клиента';
        }

        return $this->user->display_name;
    }

    public function afterCreate()
    {
        $this->attachManagers();
    }

    public function attachManagers()
    {
        $managerIds = User::active()
            ->where('utype_id', 2)
            ->pluck('id')
            ->all();

        if ($managerIds) {
            $this->managers()->syncWithoutDetaching($managerIds);
        }
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

    public function getCabinetLinkAttribute()
    {
        return url('cabinet/obyavleniya/nomer/' . $this->id);
    }

    public function getCarNumberAttribute()
    {
        $props = is_array($this->props) ? $this->props : [];
        $series = '';

        if (!empty($props['series'])) {
            $series = is_array($props['series']) ? implode('', $props['series']) : (string) $props['series'];
        } elseif ($this->series) {
            $series = (string) $this->series;
        }

        $number = '';
        if (!empty($props['number'])) {
            $number = is_array($props['number']) ? implode('', $props['number']) : (string) $props['number'];
        } elseif ($this->number) {
            $number = (string) $this->number;
        }

        $region = '';
        if (!empty($props['region'])) {
            $region = is_array($props['region']) ? implode('', $props['region']) : (string) $props['region'];
        } elseif ($this->region) {
            $region = (string) $this->region;
        }

        $parts = array_filter([
            mb_substr($series, 0, 1),
            $number,
            mb_substr($series, 1, 2),
            $region,
        ], function ($part) {
            return trim((string) $part) !== '';
        });

        return $parts ? implode(' ', $parts) : ($this->num ?: '');
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

    // Действие
    public function getTypePayOptions(){
        $options[1] = 'Продам';
        $options[2] = 'Куплю';
        return $options;    
    }

    // Фиксированная цена
    public function getFixPriceAttribute(){

        $types['sum_start'] = 'от';
        $types['sum_end'] = 'до';

        $prices['sum_start'] = $this->sum_start;
        $prices['sum_end'] = $this->sum_end;

        $rows = array();
        foreach($prices as $key => $price){
            if(trim($price)){
                $rows[$key] = $types[$key] .' '. $price . 'руб.';
            }    
        }

        return implode(' ', $rows);
    }

    // Адрес собственника
    public function getFullAddressAttribute(){

        $types['index'] = '';
        $types['region'] = '';
        $types['city'] = 'г. ';
        $types['street'] = 'ул. ';
        $types['house'] = 'дом ';
        $types['building'] = 'строение ';
        $types['room'] = 'кв. ';

        $arr = array();
        foreach($this->address as $key => $row){
            if(trim($row)){
                $arr[] = $types[$key] . $row;    
            }
        }

        return collect($arr);
    }

}
