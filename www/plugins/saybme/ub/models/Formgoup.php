<?php namespace Saybme\Ub\Models;

use Model;

class Formgoup extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    public $timestamps = false;
    
    public $table = 'saybme_ub_form_groups';
    
    public $rules = [
        'title' => 'required'
    ];   
    
    public $hasMany = [
        'inputs' => [
            Forminput::class,
            'key' => 'parent_id',
            'delete' => true
        ],
    ];
    

}
