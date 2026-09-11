<?php namespace Saybme\Ub\Classes\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Saybme\Ub\Classes\App\AppClass;
use Throwable;

class TelegramAuth
{
    private const AUTH_URL = 'https://oauth.telegram.org/auth';
    private const TOKEN_URL = 'https://oauth.telegram.org/token';
    private const JWKS_URL = 'https://oauth.telegram.org/.well-known/jwks.json';
    private const ISSUER = 'https://oauth.telegram.org';

    /**
     * Сколько действует подтверждение номера.
     * 15 минут.
     */
    private const VERIFICATION_TTL = 900;

    /**
     * Отправляем пользователя в Telegram.
     */
    public function redirect()
    {
        $clientId = env('TELEGRAM_CLIENT_ID');
        $redirectUri = env('TELEGRAM_REDIRECT_URI');

        if (!$clientId || !$redirectUri) {
            abort(500, 'Telegram OAuth is not configured');
        }

        /*
         * Удаляем старое незавершенное подтверждение OAuth.
         * telegram_expected_phone не трогаем — его задаёт форма регистрации.
         */
        Session::forget([
            'telegram_oauth_state',
            'telegram_code_verifier',
        ]);

        /*
         * Защита от CSRF.
         */
        $state = bin2hex(random_bytes(32));

        /*
         * PKCE verifier.
         */
        $codeVerifier = $this->base64UrlEncode(
            random_bytes(64)
        );

        /*
         * PKCE challenge.
         */
        $codeChallenge = $this->base64UrlEncode(
            hash('sha256', $codeVerifier, true)
        );

        Session::put('telegram_oauth_state', $state);
        Session::put('telegram_code_verifier', $codeVerifier);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',

            /*
             * Просим профиль и подтвержденный номер.
             */
            'scope' => 'openid profile phone',

            'state' => $state,

            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect(
            self::AUTH_URL . '?' . $query
        );
    }

    /**
     * Telegram возвращает пользователя сюда.
     */
    public function callback()
    {
        try {
            $state = request()->query('state');
            $code = request()->query('code');

            if (!$state || !$code) {
                return $this->failRedirect(
                    'Не удалось подтвердить номер через Telegram. Попробуйте ещё раз.'
                );
            }

            /*
             * Проверяем state.
             */
            $sessionState = Session::get(
                'telegram_oauth_state'
            );

            if (
                !$sessionState ||
                !hash_equals($sessionState, $state)
            ) {
                return $this->failRedirect(
                    'Сессия подтверждения устарела. Подтвердите номер ещё раз.'
                );
            }

            /*
             * State уже использован.
             */
            Session::forget('telegram_oauth_state');

            /*
             * Получаем PKCE verifier.
             */
            $codeVerifier = Session::pull(
                'telegram_code_verifier'
            );

            if (!$codeVerifier) {
                return $this->failRedirect(
                    'Сессия подтверждения устарела. Подтвердите номер ещё раз.'
                );
            }

            /*
             * Настройки.
             */
            $clientId = env('TELEGRAM_CLIENT_ID');
            $clientSecret = env('TELEGRAM_CLIENT_SECRET');
            $redirectUri = env('TELEGRAM_REDIRECT_URI');

            if (
                !$clientId ||
                !$clientSecret ||
                !$redirectUri
            ) {
                throw new \RuntimeException(
                    'Telegram OAuth is not configured'
                );
            }

            /*
             * Меняем authorization code на token.
             */
            $tokenResponse = Http::asForm()
                ->withBasicAuth(
                    $clientId,
                    $clientSecret
                )
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $clientId,
                    'code_verifier' => $codeVerifier,
                ]);

            if (!$tokenResponse->successful()) {
                Log::warning(
                    'Telegram token request failed',
                    [
                        'status' => $tokenResponse->status(),
                    ]
                );

                return $this->failRedirect(
                    'Не удалось получить подтверждение Telegram. Попробуйте ещё раз.'
                );
            }

            $tokenData = $tokenResponse->json();

            $idToken = $tokenData['id_token'] ?? null;

            if (!$idToken) {
                return $this->failRedirect(
                    'Telegram не вернул данные подтверждения. Попробуйте ещё раз.'
                );
            }

            /*
             * Получаем публичные ключи Telegram.
             */
            $jwksResponse = Http::get(
                self::JWKS_URL
            );

            if (!$jwksResponse->successful()) {
                return $this->failRedirect(
                    'Не удалось проверить данные Telegram. Попробуйте ещё раз.'
                );
            }

            $jwks = $jwksResponse->json();

            /*
             * Проверяем криптографическую подпись JWT.
             *
             * Firebase JWT также проверит exp/nbf,
             * если эти поля присутствуют в токене.
             */
            $decoded = JWT::decode(
                $idToken,
                JWK::parseKeySet($jwks)
            );

            /*
             * Проверяем issuer.
             */
            if (
                !isset($decoded->iss) ||
                $decoded->iss !== self::ISSUER
            ) {
                throw new \RuntimeException(
                    'Invalid issuer'
                );
            }

            /*
             * Проверяем audience.
             */
            $audience = $decoded->aud ?? null;

            $validAudience = is_array($audience)
                ? in_array(
                    (string) $clientId,
                    array_map(
                        'strval',
                        $audience
                    ),
                    true
                )
                : (string) $audience === (string) $clientId;

            if (!$validAudience) {
                throw new \RuntimeException(
                    'Invalid audience'
                );
            }

            /*
             * Проверяем телефон.
             */
            $phone = $decoded->phone_number ?? null;

            $phoneVerified = filter_var(
                $decoded->phone_number_verified ?? false,
                FILTER_VALIDATE_BOOLEAN
            );

            if (!$phone || !$phoneVerified) {
                return $this->failRedirect(
                    'Telegram не подтвердил номер телефона.'
                );
            }

            /*
             * Нормализуем номер так же, как в форме.
             */
            $q = new AppClass;
            $phone = $q->setPhone($phone);

            if (!$phone) {
                throw new \RuntimeException(
                    'Invalid phone number'
                );
            }

            $expectedPhone = Session::get(
                'telegram_expected_phone'
            );

            if (
                !$expectedPhone ||
                !hash_equals(
                    (string) $expectedPhone,
                    (string) $phone
                )
            ) {
                Session::forget([
                    'telegram_expected_phone',
                    'phone_verification',
                    'telegram_oauth_state',
                    'telegram_code_verifier',
                ]);

                return $this->failRedirect(
                    'Номер телефона Telegram не совпадает с указанным номером.'
                );
            }

            /*
             * Меняем идентификатор сессии после
             * успешной внешней авторизации.
             */
            Session::regenerate();

            Session::forget('telegram_expected_phone');

            /*
             * Существующий пользователь — сразу вход.
             */
            $auth = new AuthClass;
            $existingUser = $auth->loginByPhone($phone);

            if ($existingUser) {
                return redirect('/cabinet');
            }

            /*
             * Сохраняем подтверждение для регистрации.
             */
            Session::put('phone_verification', [
                'provider' => 'telegram',

                'phone' => $phone,

                /*
                 * sub — стандартный OIDC ID пользователя.
                 */
                'provider_user_id' => (string) (
                    $decoded->sub ?? ''
                ),

                /*
                 * Дополнительно сохраняем Telegram id,
                 * если он присутствует.
                 */
                'telegram_id' => isset($decoded->id)
                    ? (string) $decoded->id
                    : null,

                'name' => $decoded->name ?? null,

                'username' =>
                    $decoded->preferred_username ?? null,

                'verified_at' => time(),

                'expires_at' =>
                    time() + self::VERIFICATION_TTL,
            ]);

            Session::put('auth.phone', $phone);
            Session::put('auth.utype', 1);

            Session::flash('open_auth_modal', 1);

            return redirect($this->returnUrl());

        } catch (Throwable $e) {
            Log::error(
                'Telegram authentication error',
                [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            return $this->failRedirect(
                'Ошибка проверки Telegram. Попробуйте ещё раз.'
            );
        }
    }

    private function failRedirect(string $message)
    {
        Session::forget([
            'telegram_oauth_state',
            'telegram_code_verifier',
            'phone_verification',
        ]);

        Session::flash('auth_error', $message);
        Session::flash('open_auth_modal', 1);

        return redirect($this->returnUrl());
    }

    private function returnUrl(): string
    {
        return env(
            'TELEGRAM_AFTER_AUTH_URL',
            '/'
        );
    }

    /**
     * Base64 URL Safe Encoding.
     */
    private function base64UrlEncode(
        string $data
    ): string {
        return rtrim(
            strtr(
                base64_encode($data),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
