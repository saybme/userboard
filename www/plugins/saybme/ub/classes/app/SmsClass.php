<?php namespace Saybme\Ub\Classes\App;

class SmsClass {

    // Отправляем СМС сообщение
    public function sendSms($phone = '79147114656', $code){  
      
        $sender = 'UserBoardRu';
        $text = 'Код для входа ' . $code;
       
        $apikey = '7YOVS5W20N6GSCU1XM3330YSS389O39D3L309X4YDQMS4X1057DF4EWV3AUO72Q6';

        $urlArr['send'] = $text;
        $urlArr['to'] = urlencode($phone);
        $urlArr['from'] = $sender;
        $urlArr['apikey'] = $apikey;
        $urlArr['format'] = 'json';

        $url = 'https://smspilot.ru/api.php?' . http_build_query($urlArr);

        $json = file_get_contents($url);
        $data = json_decode($json);

        return $data;
    }    

}