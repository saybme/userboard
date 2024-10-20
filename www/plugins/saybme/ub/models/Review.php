<?php namespace Saybme\Ub\Models;

use Model;

class Review extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
   
    public $table = 'saybme_ub_reviews';
    
    public $rules = [
        'name' => 'required'
    ];

    public $attachOne = [
        'photo' => \System\Models\File::class
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }
    

}
