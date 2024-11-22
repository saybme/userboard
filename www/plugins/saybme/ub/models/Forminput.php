<?php namespace Saybme\Ub\Models;

use Model;

class Forminput extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    public $table = 'saybme_ub_forminputs';

    public $rules = [
        'title' => 'required'
    ];

    public function beforeCreate() {
        $this->code = 'ub' . md5(time());
    }

    public $belongsTo = [
        'value' => [
            \Saybme\Ub\Models\Formvalue::class,
            'scope' => 'isDepth'
        ]
    ];

}
