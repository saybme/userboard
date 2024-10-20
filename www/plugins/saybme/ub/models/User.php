<?php namespace Saybme\Ub\Models;

use Saybme\Ub\Classes\App\AppClass;
use Model;

class User extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    use \October\Rain\Database\Traits\Purgeable;
    use \Tailor\Traits\BlueprintRelationModel;

    protected $purgeable = ['password_confirmation'];

    protected $fillable = ['login','phone','password','password_confirmation','is_active','email','profile'];
    protected $jsonable = ['profile'];
    
    public $table = 'saybme_ub_users';
    
    public $rules = [
        'phone' => 'required|min:11|phone|unique:saybme_ub_users',
        'password' => 'required:create|between:8,255|confirmed',
        'password_confirmation' => 'required_with:password|between:8,255',
    ];

    public $hasMany = [
        'documents' => \Saybme\Ub\Models\Document::class,
    ];

    public $attachMany = [
        'files' => \System\Models\File::class
    ];

    public $belongsTo = [
        'utype' => [
            \Tailor\Models\EntryRecord::class,
            'blueprint' => 'd9eeebf9-9335-4913-9e42-a73316d57e03'
        ]
    ];
    

    public function beforeValidate() {
        $q = new AppClass;
        $phone = $q->setPhone($this->phone);
        $this->phone = $phone;        
    }

    // Событие перед созания модели
    public function beforeUpdate(){
        if(!$this->password){
            unset($this->password);
        }
    }

    public function beforeCreate() {
        $this->hash = md5(time());
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }


}
