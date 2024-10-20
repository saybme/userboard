<?php namespace Saybme\Ub\Models;

use Model;

class Application extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $jsonable = ['data'];

    protected $fillable = ['user','data'];
    
    public $table = 'saybme_ub_applications';
    
    public $rules = [
        'user' => 'required',
        'data' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class
    ];

    public function getLinkAttribute(){

        $arr[] = 'cabinet';
        $arr[] = 'applications';
        $arr[] = $this->hash;

        $url = implode('/', $arr);
        return url($url);
    }

    public function getNameAttribute(){
        $name = 'Заявка №:' . $this->num;
        return $name;
    }

    public function getStatusOptions(){
        $options[1] = 'Новая';
        $options[2] = 'Заявка обработана';
        return $options;
    }

    public function getStatusNameAttribute(){
        if(!$this->status) return 'Статус не присвоен';

        $options = $this->getStatusOptions();

        return $options[$this->status];
    }

    // public function getAppBodyAttribute(){
    //     $body = $this->data;
    //     unset($body['form']);

    //     $rows = array();

    //     foreach($body as $key => $item){
    //         $obj = Formrow::where('value->hash', $key)->first();
    //         if($obj){
    //             $data['form'] = $obj;
    //             $data['value'] = $this->getValue($item, $obj);
    //             $rows[$key] = $data;
    //         }
    //     }

    //     return $rows;
    // }

    private function getValue($value = null, $obj){
        if(!$value) return;

        if($obj->_group == 'radio'){
            $obj = Formrow::find($value);
            $value = $obj->title;
        } elseif ($obj->_group == 'text'){
            if($obj->type == 'date'){
                $value = date('d.m.Y год', strtotime($value));
            } else {
                $value = $value;
            }
        }

        if(gettype($value) == 'array'){
            $items = Formrow::whereIn('id', $value)->get();
            return $items->pluck('title')->implode('<br>');
        }

        return $value;
    }

    public function beforeCreate() {
        $hash = md5(time());
        $this->num = $this->createNum();
        $this->hash = $hash;
        $this->url = 'applications/' . $hash;
    }    

    // Номер заявки
    private function createNum(){
        // Создаем номер заказа
        $num = date('ymj') .'-'. str_pad(mt_rand(1, 99999), 4, '0', STR_PAD_LEFT); // Создаем номер заказа
        return $num;
    }

}
