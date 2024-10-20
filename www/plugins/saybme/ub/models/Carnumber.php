<?php namespace Saybme\Ub\Models;

use Model;

class Carnumber extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $fillable = [
        'num',
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
        'is_active'
    ];

    protected $jsonable = ['address','props'];
   
    public $table = 'saybme_ub_carnumbers';
    
    public $rules = [
        'num' => 'required',
        'user' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }  

    public function getLinkAttribute(){
        $arr[] = 'gossnumbers';
        $arr[] = $this->id;
        $url = implode('/', $arr);
        return url($url);
    }

    public function getCarNumberAttribute(){
        if(!$this->num) return;
        $arr = explode('|', $this->num);
        if(count($arr) <= 2) return;
        
        $data[0] = $arr[0] .' '. $arr[1] .' '. $arr[2];
        $data[1] = $arr[3];
        
        return collect($data);
    }

    public $attachOne = [
        'photo' => \System\Models\File::class
    ];
    

}
