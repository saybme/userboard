<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Classes\Auth\AuthClass;
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

    // Проверка: пользователь по email или менеджер по логину/email
    public function onCheckAuthEmail()
    {
        $identifier = trim((string) Input::get('login', Input::get('email', '')));
        $q = new AuthClass;
        $user = $q->findAuthAccount($identifier);

        if ($user) {
            return [
                'exists' => true,
                'is_manager' => (int) $user->utype_id === 2,
                'email' => $identifier,
            ];
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return [
                'exists' => false,
                'is_manager' => false,
                'email' => $identifier,
            ];
        }

        return [
            'exists' => null,
            'is_manager' => false,
        ];
    }

    // Авторизация (только email)
    function onAuth(){

        $authAction = Input::get('auth_action');
        $q = new AuthClass;
        $q->syncAuthIdentifier();

        $options = array();
        $tpl = 'modal/form-inputs';

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

        throw new ValidationException([
            'email' => 'Нажмите «Войти» или «Зарегистрироваться».',
        ]);
    }

    private function authFormOptions()
    {
        $options = [
            'step' => 1,
        ];

        if (Session::has('auth_error')) {
            $options['auth_error'] = Session::pull('auth_error');
        }

        return $options;
    }

}
