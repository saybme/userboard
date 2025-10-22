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
        // Инициализация cURL сессии
        $ch = curl_init();

        // Настройка параметров cURL
        curl_setopt($ch, CURLOPT_URL, 'https://sandbox3.payture.com/apim/Init');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);       

        // Подготовка данных для отправки
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

        // Установка POST данных
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // Выполнение запроса
        $response = curl_exec($ch);

        // Проверка на ошибки
        if(curl_errno($ch)) {
            echo 'Ошибка cURL: ' . curl_error($ch);
        } else {

            Log::error($response);
            
            // Декодирование JSON ответа
            $arr = $this->xmlStringToArray($response);
            $SessionId = $arr['@attributes']['SessionId'];                  
            
            // Вывод результата
            return $SessionId;
        }

        // Закрытие cURL сессии
        curl_close($ch);

    }
    

    function xmlStringToArray($xmlString) {
        // Загружаем XML строку
        $xml = simplexml_load_string($xmlString);
        
        // Преобразуем в JSON и обратно в массив
        return json_decode(json_encode($xml), true);
    }


}