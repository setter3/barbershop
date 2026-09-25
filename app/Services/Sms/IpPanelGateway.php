<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IpPanelGateway
{
    /**
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function sendPattern(string $recipient, string $pattern, array $params): array
    {
        $this->ensureConfigured();

        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders(['Authorization' => (string) config('services.sms.token')])
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 300, throw: false)
            ->post(rtrim((string) config('services.sms.base_url'), '/').'/api/send', [
                'sending_type' => 'pattern',
                'from_number' => $this->e164((string) config('services.sms.sender_number')),
                'code' => $pattern,
                'recipients' => [$this->e164($recipient)],
                'params' => $params,
            ]);

        $data = $response->json();
        $messageCode = is_array($data) ? data_get($data, 'meta.message_code', 'unknown') : 'invalid-json';

        if (! $response->successful() || ! is_array($data) || data_get($data, 'meta.status') !== true) {
            throw new RuntimeException("IPPanel rejected the SMS request (HTTP {$response->status()}, code {$messageCode}).");
        }

        return $data;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.sms.token'))
            && filled(config('services.sms.sender_number'))
            && filled(config('services.sms.customer_pattern'))
            && filled(config('services.sms.owner_pattern'))
            && filled(config('services.sms.owner_mobile'));
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('SMS service is not fully configured.');
        }
    }

    private function e164(string $number): string
    {
        $number = strtr(trim($number), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '98'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '98')) {
            $digits = '98'.$digits;
        }

        $normalized = '+'.$digits;

        if (preg_match('/^\+[1-9]\d{7,14}$/', $normalized) !== 1) {
            throw new RuntimeException('An SMS phone number is not valid in E.164 format.');
        }

        return $normalized;
    }
}
