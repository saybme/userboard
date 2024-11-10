<?php namespace Saybme\Ub\Models;

use October\Rain\Database\ExpandoModel;

class Forminput extends ExpandoModel
{
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'saybme_ub_form_inputs';

    protected $expandoPassthru = ['parent_id', 'sort_order'];

    public $attachMany = [
        'photos' => \System\Models\File::class,
    ];

    public function beforeCreate(){
        $this->hash = 'in_' . md5(time());
    }

    // public $hasMany = [
    //     'values' => [
    //         Formvalues::class,
    //         'key' => 'parent_id',
    //         'delete' => true
    //     ],
    // ];

}