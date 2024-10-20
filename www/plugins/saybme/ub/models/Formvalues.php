<?php namespace Saybme\Ub\Models;

use October\Rain\Database\ExpandoModel;

class FormValues extends ExpandoModel
{
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'saybme_ub_form_values';

    protected $expandoPassthru = ['parent_id', 'sort_order'];
    
}
