<?php namespace Saybme\Ub\Models;

use Model;

class Formvalue extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;
   
    public $table = 'saybme_ub_formvalues';
    
    public $rules = [
        'title' => 'required'
    ];

    public function scopeIsDepth($query) {
        return $query->where('is_active', true)->where('nest_depth', 0);
    }

}
