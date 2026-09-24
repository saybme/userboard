<?php namespace Saybme\Ub\Models;

use Saybme\Ub\Classes\App\AppClass;
use ValidationException;
use Model;

class User extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Hashable;
    use \Tailor\Traits\BlueprintRelationModel;

    protected $purgeable = ['password_confirmation'];

    protected $hashable = ['password'];

    protected $fillable = ['login','phone','password','password_confirmation','is_active','email','profile'];
    protected $jsonable = ['profile'];
    
    public $table = 'saybme_ub_users';
    
    public $rules = [
        'phone' => 'nullable|min:11|phone|unique:saybme_ub_users',
        'email' => 'nullable|email|unique:saybme_ub_users',
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
        if (trim((string) $this->phone)) {
            $q = new AppClass;
            $this->phone = $q->setPhone($this->phone);
        } else {
            $this->phone = null;
        }

        if (!trim((string) $this->email)) {
            $this->email = null;
        }

        // Нужен телефон или email
        if (!$this->phone && !$this->email) {
            throw new ValidationException([
                'email' => 'Укажите email или номер телефона.',
            ]);
        }
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
