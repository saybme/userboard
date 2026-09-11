<?php namespace Saybme\Ub\Classes\Auth;

use Illuminate\Support\Facades\Http;
use ValidationException;
use Log;

class SmsruClass {

    // Отправка сообщения через сервис https://smsc.ru/
    public function send($phone, $text) {
        $payload = [
            'phones' => $phone,
            'mes' => $text,
            'apikey' => env('SMSRU_API_KEY'),
            'fmt' => 3
        ];

        try {
            $response = Http::asForm()->post('https://smsc.ru/sys/send.php', $payload);
        } catch (\Throwable $e) {
            $this->logError('ошибка запроса', [
                'phone' => $phone,
                'text' => $text,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ValidationException([
                'phone' => 'Не удалось отправить СМС. Попробуйте позже.',
            ]);
        }

        $body = $response->body();
        $data = $response->json();

        if (!$response->successful() || !is_array($data)) {
            $this->logError('некорректный ответ', [
                'phone' => $phone,
                'text' => $text,
                'http_status' => $response->status(),
                'body' => $body,
            ]);

            throw new ValidationException([
                'phone' => 'Не удалось отправить СМС. Попробуйте позже.',
            ]);
        }

        if (!empty($data['error_code'])) {
            $this->logError('ошибка отправки', [
                'phone' => $phone,
                'text' => $text,
                'http_status' => $response->status(),
                'error_code' => $data['error_code'],
                'error' => $data['error'] ?? null,
                'body' => $body,
                'response' => $data,
            ]);

            throw new ValidationException([
                'phone' => $this->errorMessage($data['error_code']),
            ]);
        }

        return $data;
    }

    private function logError($title, array $context) {
        Log::error('SMSC: ' . $title . ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function errorMessage($code) {
        $messages = [
            1 => 'Ошибка сервиса отправки СМС. Попробуйте позже.',
            2 => 'Ошибка авторизации сервиса СМС.',
            3 => 'Недостаточно средств для отправки СМС.',
            4 => 'IP-адрес заблокирован сервисом СМС.',
            5 => 'Неверный формат даты в запросе СМС.',
            6 => 'Сообщение запрещено или неверный номер телефона.',
            7 => 'Неверный формат номера телефона.',
            8 => 'Сообщение не может быть доставлено.',
            9 => 'Слишком много запросов. Попробуйте позже.',
        ];

        return $messages[(int) $code] ?? 'Не удалось отправить СМС. Попробуйте позже.';
    }
}
