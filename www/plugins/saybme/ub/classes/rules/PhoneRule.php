<?php namespace Saybme\Ub\Classes\Rules;

use Saybme\Ub\Classes\App\AppClass;
use Log;

class PhoneRule {

    public function validate($attribute, $value, $params) {

        $q = new AppClass;
        $phone = $q->setPhone($value);

        if(strlen($phone) != 11) return false;

        return true;
    }

    public function message() {
        return 'Телефон введен некорректно.';
    }

}