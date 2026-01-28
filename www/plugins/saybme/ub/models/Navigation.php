<?php namespace Saybme\Ub\Models;

use Model;

/**
 * Model
 */
class Navigation extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\NestedTree;

    /**
     * @var array dates to cast from the database.
     */
    protected $dates = ['deleted_at'];

    protected $jsonable = ['props'];

    /**
     * @var string table in the database used by the model.
     */
    public $table = 'saybme_ub_navigations';

    /**
     * @var array rules for validation.
     */
    public $rules = [
        'title' => 'required',
    ];    

    // scope isActive
    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1); 
    }

    // attach one image
    public $attachOne = [
        'image' => 'System\Models\File',
    ];

}
