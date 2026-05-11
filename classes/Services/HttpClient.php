<?php

namespace App\Services;

use App\Cache\FileCache;
use RuntimeException;

final class HttpClient
{
    private string $rateLimitFile;

    public function __construct(private readonly ?FileCache $cache = null)
    {
        $this->rateLimitFile = Config::get('app.cache_path') . '/http-rate-limit.json';
    }

    public function getJson(string $url, array $headers = [], ?int $cacheTtl = null): array
    {
        return $this->requestJson('GET', $url, [], $headers, $cacheTtl);
    }

    public function postJson(string $url, array $payload, array $headers = [], ?int $cacheTtl = null): array
    {
        return $this->requestJson('POST', $url, $payload, $headers, $cacheTtl);
    }

    public function requestJson(string $method, string $url, array $payload = [], array $headers = [], ?int $cacheTtl = null): array
    {
        $cacheKey = $method . ':' . $url . ':' . md5(json_encode($payload));
        if ($cacheTtl !== null && $this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = $this->request($method, $url, $payload, $headers);
        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON response from ' . $url);
        }

        if ($cacheTtl !== null && $this->cache !== null) {
            $this->cache->put($cacheKey, $decoded, $cacheTtl);
        }

        return $decoded;
    }

    public function request(string $method, string $url, array $payload = [], array $headers = []): string
    {
        $providerConfig = Config::get('providers.http');
        $timeout = (int) ($providerConfig['timeout'] ?? 20);
        $retries = (int) ($providerConfig['retries'] ?? 1);
        $retryDelayMs = (int) ($providerConfig['retry_delay_ms'] ?? 500);

        $this->throttle((int) ($providerConfig['rate_limit_per_minute'] ?? 60));

        $attempts = 0;
        $lastError = 'Unknown HTTP error';

        do {
            $attempts++;
            $ch = curl_init();
            $requestHeaders = $headers;

            if ($method === 'GET' && $payload !== []) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . http_build_query($payload);
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => $requestHeaders,
            ]);

            if ($method === 'POST') {
                $requestHeaders[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
            }

            $result = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($result !== false && $statusCode >= 200 && $statusCode < 300) {
                return $result;
            }

            $lastError = $error !== '' ? $error : 'HTTP status ' . $statusCode;
            usleep($retryDelayMs * 1000);
        } while ($attempts <= $retries);

        throw new RuntimeException('Request failed for ' . $url . ': ' . $lastError);
    }

    private function throttle(int $perMinute): void
    {
        if ($perMinute <= 0) {
            return;
        }

        $records = [];
        if (is_file($this->rateLimitFile)) {
            $records = json_decode((string) file_get_contents($this->rateLimitFile), true) ?: [];
        }

        $now = microtime(true);
        $windowStart = $now - 60;
        $records = array_values(array_filter($records, static fn (float $timestamp): bool => $timestamp >= $windowStart));

        if (count($records) >= $perMinute) {
            $targetTime = ((float) $records[0]) + 60.0;
            $sleepMicroseconds = max(0, (int) round(($targetTime - $now) * 1_000_000));
            if ($sleepMicroseconds > 0) {
                usleep($sleepMicroseconds);
            }
            $now = microtime(true);
            $windowStart = $now - 60;
            $records = array_values(array_filter($records, static fn (float $timestamp): bool => $timestamp >= $windowStart));
        }

        $records[] = $now;
        file_put_contents($this->rateLimitFile, json_encode($records));
    }
}
