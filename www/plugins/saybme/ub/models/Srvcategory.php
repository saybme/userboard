<?php namespace Saybme\Ub\Models;

use Model;

class Srvcategory extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    
    public $table = 'saybme_ub_srv_categories';
  
    public $rules = [
        'name' => 'required'
    ];

    public $hasMany = [
        'servises' => \Saybme\Ub\Models\Servise::class,
    ];

}
