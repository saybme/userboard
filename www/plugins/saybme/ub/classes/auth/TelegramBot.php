<?php namespace Saybme\Ub\Classes\Auth;

use Saybme\Ub\Classes\App\AppClass;
use Saybme\Ub\Models\AuthChallenge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use ValidationException;
use Throwable;

class TelegramBot
{
    private const CHALLENGE_TTL = 600;
    private const COMPLETION_TTL = 600;
    private const VERIFICATION_TTL = 900;
    private const RATE_LIMIT = 5;
    private const RATE_WINDOW = 600;

    /**
     * Создать challenge и вернуть deep link t.me/bot?start=TOKEN
     */
    public function createChallengeAndDeepLink(string $phone): string
    {
        $this->assertRateLimit($phone);

        AuthChallenge::where('provider', 'telegram')
            ->where('expected_phone', $phone)
            ->where('status', AuthChallenge::STATUS_PENDING)
            ->update(['status' => AuthChallenge::STATUS_EXPIRED]);

        $startToken = $this->randomToken();
        $startHash = $this->hashToken($startToken);

        $challenge = new AuthChallenge;
        $challenge->fill([
            'provider' => 'telegram',
            'start_token_hash' => $startHash,
            'expected_phone' => $phone,
            'status' => AuthChallenge::STATUS_PENDING,
            'session_id' => Session::getId(),
            'ip_address' => request()->ip(),
            'expires_at' => Carbon::now()->addSeconds(self::CHALLENGE_TTL),
        ]);
        $challenge->save();

        Session::put('auth.phone', $phone);
        Session::put('auth.utype', 1);
        Session::forget('phone_verification');

        return $this->deepLink($startToken);
    }

    /**
     * Webhook Telegram Update
     */
    public function webhook()
    {
        $secret = (string) env('TELEGRAM_BOT_WEBHOOK_SECRET', '');
        $header = (string) request()->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (!$secret || !hash_equals($secret, $header)) {
            return response('Forbidden', 403);
        }

        $update = request()->all();

        try {
            if (!empty($update['message']['contact'])) {
                $this->handleContact($update['message']);
            } elseif (!empty($update['message']['text'])) {
                $this->handleText($update['message']);
            }
        } catch (Throwable $e) {
            Log::error('Telegram bot webhook error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        return response('OK', 200);
    }

    /**
     * GET /auth/telegram/complete?token=
     */
    public function complete()
    {
        $token = (string) request()->query('token', '');

        if ($token === '') {
            return $this->failComplete('Ссылка подтверждения недействительна.');
        }

        $challenge = AuthChallenge::where('provider', 'telegram')
            ->where('completion_token_hash', $this->hashToken($token))
            ->first();

        if (!$challenge) {
            return $this->failComplete('Ссылка подтверждения недействительна.');
        }

        if ($challenge->status === AuthChallenge::STATUS_USED) {
            return $this->failComplete('Ссылка уже была использована.');
        }

        if ($challenge->status !== AuthChallenge::STATUS_VERIFIED) {
            return $this->failComplete('Подтверждение номера ещё не завершено.');
        }

        if (
            !$challenge->verified_at ||
            $challenge->verified_at->copy()->addSeconds(self::COMPLETION_TTL)->isPast()
        ) {
            $challenge->status = AuthChallenge::STATUS_EXPIRED;
            $challenge->save();
            return $this->failComplete('Срок подтверждения истёк. Подтвердите номер ещё раз.');
        }

        if (!$challenge->verified_phone || !$challenge->provider_user_id) {
            return $this->failComplete('Данные подтверждения неполные. Подтвердите номер ещё раз.');
        }

        $phone = $challenge->verified_phone;

        $challenge->status = AuthChallenge::STATUS_USED;
        $challenge->used_at = Carbon::now();
        $challenge->save();

        Session::regenerate();

        $auth = new AuthClass;
        $existing = $auth->loginByPhone($phone);

        if ($existing) {
            return redirect('/cabinet');
        }

        Session::put('phone_verification', [
            'provider' => 'telegram',
            'phone' => $phone,
            'provider_user_id' => (string) $challenge->provider_user_id,
            'telegram_id' => (string) $challenge->provider_user_id,
            'verified_at' => time(),
            'expires_at' => time() + self::VERIFICATION_TTL,
        ]);

        Session::put('auth.phone', $phone);
        Session::put('auth.utype', 1);
        Session::flash('open_auth_modal', 1);

        return redirect($this->afterAuthUrl());
    }

    /**
     * Зарегистрировать webhook в Telegram (вызвать один раз).
     */
    public function registerWebhook(): array
    {
        $url = env('TELEGRAM_BOT_WEBHOOK_URL');
        $secret = env('TELEGRAM_BOT_WEBHOOK_SECRET');

        if (!$url || !$secret) {
            throw new \RuntimeException('TELEGRAM_BOT_WEBHOOK_URL or SECRET is not configured');
        }

        $response = $this->api('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => json_encode(['message']),
            'drop_pending_updates' => true,
        ]);

        return $response;
    }

    private function handleText(array $message): void
    {
        $text = trim((string) ($message['text'] ?? ''));
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '' || $text === '') {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $parts = preg_split('/\s+/', $text, 2);
            $token = $parts[1] ?? '';
            $this->handleStart($message, $token);
            return;
        }
    }

    private function handleStart(array $message, string $token): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');

        if ($chatId === '' || $fromId === '') {
            return;
        }

        if ($token === '') {
            $this->sendMessage($chatId, 'Откройте подтверждение заново с сайта Услуги-8.рф.');
            return;
        }

        $challenge = AuthChallenge::where('provider', 'telegram')
            ->where('start_token_hash', $this->hashToken($token))
            ->first();

        if (
            !$challenge ||
            $challenge->status !== AuthChallenge::STATUS_PENDING ||
            $challenge->isExpired()
        ) {
            if ($challenge && $challenge->isExpired()) {
                $challenge->status = AuthChallenge::STATUS_EXPIRED;
                $challenge->save();
            }

            $this->sendMessage(
                $chatId,
                'Ссылка подтверждения недействительна или устарела. Вернитесь на сайт и начните заново.'
            );
            return;
        }

        $challenge->provider_user_id = $fromId;
        $challenge->telegram_chat_id = $chatId;
        $challenge->save();

        $this->sendContactRequest($chatId);
    }

    private function handleContact(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');
        $contact = $message['contact'] ?? null;

        if ($chatId === '' || $fromId === '' || !is_array($contact)) {
            return;
        }

        $contactUserId = isset($contact['user_id'])
            ? (string) $contact['user_id']
            : '';

        if ($contactUserId === '' || !hash_equals($fromId, $contactUserId)) {
            $this->sendMessage(
                $chatId,
                'Можно подтвердить только номер, привязанный к вашему Telegram-аккаунту.',
                true
            );
            return;
        }

        $challenge = AuthChallenge::where('provider', 'telegram')
            ->where('provider_user_id', $fromId)
            ->where('status', AuthChallenge::STATUS_PENDING)
            ->orderBy('id', 'desc')
            ->first();

        if (!$challenge || $challenge->isExpired()) {
            if ($challenge) {
                $challenge->status = AuthChallenge::STATUS_EXPIRED;
                $challenge->save();
            }

            $this->sendMessage(
                $chatId,
                'Сессия подтверждения устарела. Вернитесь на сайт и начните заново.',
                true
            );
            return;
        }

        $q = new AppClass;
        $telegramPhone = $q->setPhone($contact['phone_number'] ?? '');

        if (
            !$telegramPhone ||
            !hash_equals(
                (string) $challenge->expected_phone,
                (string) $telegramPhone
            )
        ) {
            Log::info('Telegram contact phone mismatch', [
                'expected' => $this->maskPhone($challenge->expected_phone),
                'got' => $this->maskPhone($telegramPhone ?: ''),
                'user' => $fromId,
            ]);

            $this->sendMessage(
                $chatId,
                "Номер телефона, привязанный к Telegram, не совпадает с номером, указанным на сайте.\n\nВернитесь на сайт и укажите номер, который используется в вашем Telegram.",
                true
            );
            return;
        }

        $completionToken = $this->randomToken();

        $challenge->status = AuthChallenge::STATUS_VERIFIED;
        $challenge->verified_phone = $telegramPhone;
        $challenge->verified_at = Carbon::now();
        $challenge->completion_token_hash = $this->hashToken($completionToken);
        $challenge->expires_at = Carbon::now()->addSeconds(self::COMPLETION_TTL);
        $challenge->save();

        $returnUrl = $this->completeUrl($completionToken);

        $this->sendSuccess($chatId, $returnUrl);
    }

    private function sendContactRequest(string $chatId): void
    {
        $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => "Для подтверждения номера телефона нажмите кнопку ниже.\nTelegram передаст номер, привязанный к вашему аккаунту.",
            'reply_markup' => json_encode([
                'keyboard' => [[
                    [
                        'text' => '📱 Подтвердить номер телефона',
                        'request_contact' => true,
                    ],
                ]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function sendSuccess(string $chatId, string $returnUrl): void
    {
        $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => "✅ Номер телефона подтверждён.\n\nВернитесь на сайт, чтобы продолжить.",
            'reply_markup' => json_encode([
                'remove_keyboard' => true,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Нажмите кнопку ниже:',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    [
                        'text' => '← ВЕРНУТЬСЯ НА УСЛУГИ-8.РФ',
                        'url' => $returnUrl,
                    ],
                ]],
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function sendMessage(string $chatId, string $text, bool $removeKeyboard = false): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($removeKeyboard) {
            $payload['reply_markup'] = json_encode([
                'remove_keyboard' => true,
            ], JSON_UNESCAPED_UNICODE);
        }

        $this->api('sendMessage', $payload);
    }

    private function api(string $method, array $params = []): array
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        if (!$token) {
            throw new \RuntimeException('TELEGRAM_BOT_TOKEN is not configured');
        }

        $response = Http::asForm()->post(
            'https://api.telegram.org/bot' . $token . '/' . $method,
            $params
        );

        $data = $response->json() ?: [];

        if (!$response->successful() || empty($data['ok'])) {
            Log::warning('Telegram Bot API error', [
                'method' => $method,
                'status' => $response->status(),
                'description' => $data['description'] ?? null,
            ]);
        }

        return is_array($data) ? $data : [];
    }

    private function assertRateLimit(string $phone): void
    {
        $ip = (string) request()->ip();
        $sessionId = (string) Session::getId();

        $keys = [
            'tg_bot_rl:phone:' . $phone,
            'tg_bot_rl:ip:' . $ip,
            'tg_bot_rl:sid:' . $sessionId,
        ];

        foreach ($keys as $key) {
            $count = (int) Cache::get($key, 0);
            if ($count >= self::RATE_LIMIT) {
                throw new ValidationException([
                    'phone' => 'Слишком много попыток. Подождите несколько минут и попробуйте снова.',
                ]);
            }
        }

        foreach ($keys as $key) {
            $count = (int) Cache::get($key, 0);
            Cache::put($key, $count + 1, self::RATE_WINDOW);
        }
    }

    private function deepLink(string $token): string
    {
        $username = trim((string) env('TELEGRAM_BOT_USERNAME', 'uslugi8ru_bot'), '@');
        return 'https://t.me/' . $username . '?start=' . rawurlencode($token);
    }

    private function completeUrl(string $token): string
    {
        $base = rtrim((string) env(
            'TELEGRAM_SITE_URL',
            'https://xn---8-glcun9anc.xn--p1ai'
        ), '/');

        return $base . '/auth/telegram/complete?token=' . rawurlencode($token);
    }

    private function afterAuthUrl(): string
    {
        return env('TELEGRAM_AFTER_AUTH_URL', '/');
    }

    private function failComplete(string $message)
    {
        Session::flash('auth_error', $message);
        Session::flash('open_auth_modal', 1);
        return redirect($this->afterAuthUrl());
    }

    private function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function maskPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';
        if (strlen($phone) < 6) {
            return '***';
        }

        return substr($phone, 0, 4) . '*****' . substr($phone, -2);
    }
}
