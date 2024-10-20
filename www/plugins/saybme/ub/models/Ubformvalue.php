<?php namespace Saybme\Ub\Models;

use Model;

class Ubformvalue extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    public $timestamps = false;
    
    public $table = 'saybme_ub_ubformvalues';
    
    public $rules = [
        'name' => 'required'
    ];

    public $hasMany = [
        'values' => [
            Formvalues::class,
            'key' => 'parent_id',
            'delete' => true
        ],
    ];
    

}
