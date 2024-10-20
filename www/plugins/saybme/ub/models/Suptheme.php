<?php namespace Saybme\Ub\Models;

use Model;

class Suptheme extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $fillable = ['user','is_active'];
   
    public $table = 'saybme_ub_sup_themes';
    
    public $rules = [
        'user' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class
    ];

    public $hasMany = [
        'comments' => \Saybme\Ub\Models\Message::class,
    ];

    public function beforeCreate() {

        $num = time();
        $hash = md5(time());
        
        $this->num = $num;
        $this->hash = $hash;
        $this->url = $this->createUrl($hash);

    }

    public function beforeSave(){
        $hash = $this->hash;
        $this->url = $this->createUrl($hash);
    }

    private function createUrl($hash = ''){
        $arr[] = 'tehnicheskaya-podderzhka';
        $arr[] = $this->hash;
        return implode('/', $arr);
    }

    public function getLinkAttribute(){
        $arr[] = 'cabinet/tehnicheskaya-podderzhka';
        $arr[] = $this->hash;
        $url = implode('/', $arr);
        return url($url);
    }

    public function getNameAttribute(){
        $name = 'Обращение №' . $this->num;
        return $name;
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function scopeUserType($query, $id) {
        return $query->where('user_id', $id);
    }


}
