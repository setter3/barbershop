<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZibalGateway
{
    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function request(
        int $amount,
        string $callbackUrl,
        string $orderId,
        string $mobile,
        string $description,
    ): array {
        $this->ensureConfigured();

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->post((string) config('services.zibal.request_url'), [
                'merchant' => (string) config('services.zibal.merchant'),
                'amount' => $amount,
                'callbackUrl' => $callbackUrl,
                'orderId' => $orderId,
                'mobile' => $this->localMobile($mobile),
                'description' => $description,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zibal payment request failed with HTTP '.$response->status().'.');
        }

        $data = $response->json();

        if (! is_array($data) || (int) ($data['result'] ?? 0) !== 100 || empty($data['trackId'])) {
            throw new RuntimeException('Zibal rejected the payment request. Result: '.(string) ($data['result'] ?? 'unknown'));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function verify(string $trackId): array
    {
        $this->ensureConfigured();

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300, throw: false)
            ->post((string) config('services.zibal.verify_url'), [
                'merchant' => (string) config('services.zibal.merchant'),
                'trackId' => (int) $trackId,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zibal verification failed with HTTP '.$response->status().'.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Zibal returned an invalid verification response.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function inquiry(string $trackId): array
    {
        $this->ensureConfigured();

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300, throw: false)
            ->post((string) config('services.zibal.inquiry_url'), [
                'merchant' => (string) config('services.zibal.merchant'),
                'trackId' => (int) $trackId,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zibal inquiry failed with HTTP '.$response->status().'.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Zibal returned an invalid inquiry response.');
        }

        return $data;
    }

    public function startUrl(string $trackId): string
    {
        return rtrim((string) config('services.zibal.start_url'), '/').'/'.rawurlencode($trackId);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.zibal.merchant'));
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Zibal merchant is not configured.');
        }
    }

    private function localMobile(string $mobile): string
    {
        return str_starts_with($mobile, '+98') ? '0'.substr($mobile, 3) : $mobile;
    }
}
