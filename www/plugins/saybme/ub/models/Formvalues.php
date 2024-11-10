<?php namespace Saybme\Ub\Models;

use October\Rain\Database\ExpandoModel;

class Formvalues extends ExpandoModel
{
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'saybme_ub_form_values';

    protected $expandoPassthru = ['parent_id', 'sort_order'];

    public function beforeCreate(){
        $this->hash = 'in_' . md5(time());
    }
    
}
