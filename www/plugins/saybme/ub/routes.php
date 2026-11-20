<?php

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Classes\Auth\FilesClass;
use Saybme\Ub\Classes\Document\DocumentClass;
use Saybme\Ub\Classes\Auth\TelegramAuth;
use Saybme\Ub\Classes\Auth\TelegramBot;
use Saybme\Ub\Classes\LicensePlate\LicensePlateGenerator;
use Saybme\Ub\Classes\LicensePlate\PlateException;
use Saybme\Ub\Models\User;
use Illuminate\Support\Facades\Response;

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

/*
 * Демо генератора номерных знаков — только при app.debug и с локального/debug-запроса.
 */
if (config('app.debug')) {
    // Маршруты регистрируются только при debug; повторная проверка внутри — защита от рассинхрона конфига
    $licensePlateGuard = static function (): void {
        if (!config('app.debug')) {
            abort(404);
        }
        $ip = (string) request()->ip();
        $isLocal = in_array($ip, ['127.0.0.1', '::1'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.');
        // В debug разрешаем и нелокальные запросы (удобство стенда); при debug=false сюда не попадём
        if (!$isLocal && !config('app.debug')) {
            abort(404);
        }
    };

    Route::get('/dev/license-plate', function () use ($licensePlateGuard) {
        $licensePlateGuard();
        $generateUrl = url('/dev/license-plate/generate');
        ob_start();
        include __DIR__ . '/classes/licenseplate/demo/index.php';
        $html = ob_get_clean();
        return Response::make($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    })->middleware('web');

    Route::match(['GET', 'POST'], '/dev/license-plate/generate', function () use ($licensePlateGuard) {
        $licensePlateGuard();
        try {
            $req = request();
            $type = (string) $req->input('type', 'car');
            $number = (string) $req->input('number', '');
            $region = (string) $req->input('region', '');
            $format = strtolower((string) $req->input('format', 'svg'));
            $width = $req->input('width');
            $execution = (int) $req->input('execution', 1);
            $flag = filter_var($req->input('flag', true), FILTER_VALIDATE_BOOLEAN);

            $g = new LicensePlateGenerator();
            $data = $g->generate([
                'type' => $type,
                'number' => $number,
                'region' => $region,
                'format' => $format,
                'width' => $width !== null && $width !== '' ? (int) $width : null,
                'execution' => $execution,
                'flag' => $flag,
            ]);

            if ($format === 'png') {
                return Response::make($data, 200, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'no-store',
                ]);
            }

            return Response::make($data, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]);
        } catch (PlateException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    })->middleware('web');
}
