<?php namespace Saybme\Ub\Models;

use Model;

class Document extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $jsonable = ['data'];
    protected $fillable = ['data','user','form'];
   
    public $table = 'saybme_ub_documents';
    
    public $rules = [
        'user' => 'required',
        'data' => 'required',
        'form' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class,
        'form' => \Saybme\Ub\Models\Ubform::class
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

    // Данные продавца
    public function getVendorDataAttribute(){

        // Фамилия - Имя - Отчество 
        $arr[0][0] = $this->data['ub53c98b6d38610f07ae012064315a5c32'];
        $arr[0][1] = $this->data['uba3e105ee913b56cfec000a35499dcd47'];
        $arr[0][2] = $this->data['ube92cba9ee44475afb1ecfa249f25ab7c'];

        // Дата рождения
        $arr[1][0] = $this->data['ubf16e6c5c4067950c35b18c5645829f55'];

        // Серия - Номер - Когда 
        $arr[2][0] = 'паспорт ' . $this->data['ub2e8f10973549ad175c51917c10790b58'];
        $arr[2][1] = $this->data['ub8dc0718b9d1bd93084142623660bb8f4'];

        // Кем
        $arr[3][0] = $this->data['ube4de3eb186f6fd7e109d4308b0523973'];

        // Адрес регистрации (прописки)
        $arr[4][0] = $this->data['ub78feef39b85cad81ba69a839e713ee69'];

        $rows = array();
        foreach($arr as $key => $item){
            $rows[] = implode(' ', $arr[$key]); 
        }

        return implode(', ', $rows);
    }

    // Данные покупателя
    public function getBuyerDataAttribute(){

        // Фамилия - Имя - Отчество 
        $arr[0][0] = $this->data['ubd8c93995c91aecf357822790f5d09bf5'];
        $arr[0][1] = $this->data['ubde07983e5acb7dd65c2b14abdd713cc9'];
        $arr[0][2] = $this->data['ub972a351971665aeaa8a63028ec75b743'];

        // Дата рождения
        $arr[1][0] = $this->data['ub3bdeabb3914e5b73a2039d798048d8c0'];

        // Серия - Номер - Когда 
        $arr[2][0] = 'паспорт ' . $this->data['ube1a7f4811c3f981dbef5b624a6d18156'];
        $arr[2][1] = $this->data['ubd459848c5cecdcb91cc3421cc0212d17'];

        // Кем
        $arr[3][0] = $this->data['ub303a961a2e74a61a4b5027d75fb35649'];

        // Адрес регистрации (прописки)
        $arr[4][0] = $this->data['ub623433b7eea7ecf45a000c3315c56f5d'];

        $rows = array();
        foreach($arr as $key => $item){
            $rows[] = implode(' ', $arr[$key]); 
        }

        return implode(', ', $rows);
    }

}
