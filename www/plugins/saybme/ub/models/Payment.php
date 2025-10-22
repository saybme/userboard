<?php namespace Saybme\Ub\Models;

use Model;

/**
 * Model
 */
class Payment extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'saybme_ub_payments';

    protected $fillable = [
        'user',
        'description',
        'sum'
    ];

    /**
     * @var array rules for validation.
     */
    public $rules = [
        'user' => 'required',
        'description' => 'required',
        'sum' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class
    ];

    public function beforeCreate()
    {
        $this->uuid = $this->generateSimpleUUID();
    }


    private function generateSimpleUUID() {
        return md5(time());
    }

}
