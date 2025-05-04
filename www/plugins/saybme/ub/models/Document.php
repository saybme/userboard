<?php namespace Saybme\Ub\Models;

use Model;

class Document extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $jsonable = ['data'];
    protected $fillable = ['data','user'];
   
    public $table = 'saybme_ub_documents';
    
    public $rules = [
        'user' => 'required',
        //'data' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class
    ];

    public function getLinkAttribute(){

        $arr[] = 'cabinet';
        $arr[] = 'documents';
        $arr[] = $this->hash;

        $url = implode('/', $arr);
        return url($url);
    }

    public function beforeCreate() {
        $hash = md5(time());
        $this->num = $this->createNum();
        $this->hash = $hash;
        $this->url = 'documents/' . $hash;
    }

    // Номер заявки
    private function createNum(){
        $num = time();
        return $num;
    }

    // Данные формы

}
