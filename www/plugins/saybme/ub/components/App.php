<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\User;
use Input;
use Redirect;
use Session;
use ValidationException;

class App extends \Cms\Classes\ComponentBase
{

    public function componentDetails() {
        return [
            'name' => 'Скрипты',
            'description' => 'Все скрипты сайта'
        ];
    }

    public function onRun()
    {
        if (Session::pull('open_auth_modal')) {
            $this->page['open_auth_modal'] = true;
        }
    }    

    // Отправляем форму
    public function onSendForm(){
        $data = Input::all();

        $result = $this->renderPartial('form/success');
        return $result;
    }

    // Модальное окно входа
    function onModalOpen(){
        $result['modal'] = $this->renderPartial(
            'modal/open-form',
            $this->authFormOptions()
        );
        return $result;
    }

    // Проверка: email уже зарегистрирован или нет
    public function onCheckAuthEmail()
    {
        $email = mb_strtolower(trim((string) Input::get('email', Input::get('login', ''))));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'exists' => null,
            ];
        }

        $exists = User::active()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        return [
            'exists' => $exists,
            'email' => $email,
        ];
    }

    // Авторизация
    function onAuth(){

        $step = Input::get('step');
        $authAction = Input::get('auth_action');
        $q = new AuthClass;
        $q->syncAuthIdentifier();

        $options = array();
        $tpl = 'modal/form-inputs';

        // Вход / регистрация по email
        if ($authAction === 'email_login') {
            $q->loginByEmail();
            return Redirect::refresh();
        }

        if ($authAction === 'email_register') {
            $q->registerByEmail();
            return Redirect::to('/cabinet');
        }

        if ($authAction === 'email_link') {
            $email = $q->sendEmailLoginLink();
            $options['step'] = 'email_link_sent';
            $options['email_link_sent_to'] = $email;
            $result['#open-result'] = $this->renderPartial($tpl, $options);
            return $result;
        }

        // Enter по email без выбора кнопки
        if (
            $step == 2
            && trim((string) Input::get('email')) !== ''
            && trim((string) Input::get('phone')) === ''
        ) {
            throw new ValidationException([
                'email' => 'Нажмите «Войти» или «Зарегистрироваться».',
            ]);
        }

        // Шаг №1 → менеджер / SMS / Telegram
        if($step == 2) {
            
            $q->authStepOne();
            
            if (Session::get('auth.utype') == 2) {
                $options['auth'] = $q->getAuthSession();
                $options['step'] = 2;
            } else {
                $user = $q->authStepTwo();
                $method = Input::get('verify_method');

                if ($method === 'telegram') {
                    $url = $q->startTelegramBotVerification();
                    return Redirect::to($url);
                }

                if ($method === 'sms') {
                    // SMS: регистрация или вход
                    $q->saveContactUser(!$user);
                    $options['auth'] = $q->getAuthSession();
                    $options['step'] = 3;
                    $options['is_registration'] = !$user;
                } else {
                    throw new ValidationException([
                        'verify_method' => 'Выберите способ подтверждения.',
                    ]);
                }
            }
        }

        // Регистрация после подтверждения номера (пароль)
        if($step == 3) {
            try {
                $q->registerWithVerifiedPhone();
                return Redirect::to('/cabinet');
            } catch (ValidationException $e) {
                $verification = $q->getPhoneVerification();

                if ($verification === 'expired' || !$verification) {
                    $options['step'] = 1;
                    $options['auth_error'] = 'Срок подтверждения номера истёк. Подтвердите номер ещё раз.';
                    $result['#open-result'] = $this->renderPartial($tpl, $options);
                    return $result;
                }

                throw $e;
            }
        }     
        
        // SMS-код: вход или переход к паролю для регистрации
        if($step == 4){
            $q->validateSmsCode();

            if (Session::get('auth.is_new')) {
                $q->markPhoneVerifiedBySms();

                $options['step'] = 2;
                $options['auth'] = $q->getAuthSession();
                $options['phone_verified'] = true;
                $options['verify_provider'] = 'sms';

                $result['#open-result'] = $this->renderPartial($tpl, $options);
                return $result;
            }

            $q->authStepFour();
            return Redirect::refresh();  
        }

        // Вход менеджера
        if($step == 'manager'){          
            $q->authStepFour();
            return Redirect::refresh();     
        }

        $result['#open-result'] = $this->renderPartial($tpl, $options);
        return $result;
    }

    private function authFormOptions()
    {
        $q = new AuthClass;
        $options = [
            'step' => 1,
        ];

        if (Session::has('auth_error')) {
            $options['auth_error'] = Session::pull('auth_error');
        }

        $verification = $q->getPhoneVerification();

        if ($verification === 'expired') {
            $options['step'] = 1;
            $options['auth_error'] = 'Срок подтверждения номера истёк. Подтвердите номер ещё раз.';
            return $options;
        }

        if ($verification) {
            Session::put('auth.phone', $verification['phone']);
            Session::put('auth.utype', 1);

            $options['step'] = 2;
            $options['auth'] = $q->getAuthSession();
            $options['phone_verified'] = true;
            $options['verify_provider'] = $verification['provider'] ?? 'telegram';
        }

        return $options;
    }

}
