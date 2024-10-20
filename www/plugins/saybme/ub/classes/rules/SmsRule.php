<?php namespace Saybme\Ub\Classes\Rules;

use Saybme\Ub\Classes\Auth\AuthClass;
use Log;

class SmsRule {

    public function validate($attribute, $value, $params) {

        $q = new AuthClass;
        $sms = $q->getAuthSession()['sms'];       

        $value = trim($value);
        if(!$value) return false;

        if($sms == $value) return true;

        return false;
    }

    public function message() {
        return 'Введен неверный код из СМС';
    }

}