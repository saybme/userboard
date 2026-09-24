<?php

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Classes\Auth\FilesClass;
use Saybme\Ub\Classes\Document\DocumentClass;
use Saybme\Ub\Classes\Auth\TelegramAuth;
use Saybme\Ub\Classes\Auth\TelegramBot;
use Saybme\Ub\Models\User;

Route::get('api/auth/logout', function() {
    return redirect('/')->withCookie(Cookie::forget('auth'));
});


// Загружаем фото
// Route::post('api/cabinet/add/photo', function() {

//     $q = new AuthClass;
//     $user = $q->getActiveUser();   

//     Log::error($user);    

//     $file = new System\Models\File;
//     $file->data = files('file');
//     $file->is_public = true;
//     $file->save();
    
//     $user->files()->add($file);
    
// })->middleware('web');

// Загружаем фото
Route::post('api/photo/add', function() {
    $q = new FilesClass;      
    return $q->add();      
})->middleware('web');


Route::middleware(['web'])->group(function () {

    // Старый OIDC (сохранён, основной UX больше не использует)
    Route::get('/auth/telegram', [
        TelegramAuth::class,
        'redirect'
    ]);

    Route::get('/auth/telegram/callback', [
        TelegramAuth::class,
        'callback'
    ]);

    // Новый Bot flow: возврат на сайт после подтверждения
    Route::get('/auth/telegram/complete', [
        TelegramBot::class,
        'complete'
    ]);

    // Вход по одноразовой ссылке из письма
    Route::get('/auth/email/login', function () {
        $token = (string) request()->query('token', '');
        $auth = new AuthClass;
        $result = $auth->loginByEmailToken($token);

        if ($result instanceof User) {
            return redirect('/cabinet');
        }

        $messages = [
            'used' => 'Ссылка уже была использована. Запросите новую.',
            'expired' => 'Срок действия ссылки истёк. Запросите новую.',
            'invalid' => 'Ссылка для входа недействительна.',
        ];

        Session::flash('auth_error', $messages[$result] ?? $messages['invalid']);
        Session::flash('open_auth_modal', 1);

        return redirect('/');
    });

});

/*
 * Telegram webhook без web/CSRF:
 * Telegram шлёт POST без CSRF-токена.
 * Проверка идёт через X-Telegram-Bot-Api-Secret-Token.
 */
Route::post('/auth/telegram/bot/webhook', [
    TelegramBot::class,
    'webhook'
]);
