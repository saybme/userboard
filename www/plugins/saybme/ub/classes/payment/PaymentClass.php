<?php namespace Saybme\Ub\Classes\Payment;

use Saybme\Ub\Models\Payment;
use Input;
use Log;

class PaymentClass {

    // Создаем платеж
    public static function add($data = array()){

        $pay = new Payment;
        $pay->fill($data);
        $pay->save();

    

        return $pay;
    }


    // Платежная ссылка
    public function initPay($data = null){        
        
        $ch = curl_init();       
        
        curl_setopt($ch, CURLOPT_URL, 'https://secure.payture.com/apim/Init');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $data = [
            'Key' => 'UserBoardPSBEGW3DS',
            'Data' => http_build_query([
                'SessionType' => 'Pay',
                'OrderId' => $data->uuid,
                'Amount' => 1,
                'Product' => 'Оплата услуги',
                'Total' => $data->sum,
                'Phone' => $data->user->phone,
                'Description' => $data->description,
                'Url' => 'https://payture.com/result?orderid={orderid}&result={success}'
            ], '', ';')
        ];        

        
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));        
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            echo 'Ошибка cURL: ' . curl_error($ch);
        } else {               
            $arr = $this->xmlStringToArray($response);
            $SessionId = $arr['@attributes']['SessionId'];              
            return $SessionId;
        }       

        curl_close($ch);
    }
    

    function xmlStringToArray($xmlString) {
        // Загружаем XML строку
        $xml = simplexml_load_string($xmlString);
        
        // Преобразуем в JSON и обратно в массив
        return json_decode(json_encode($xml), true);
    }


}