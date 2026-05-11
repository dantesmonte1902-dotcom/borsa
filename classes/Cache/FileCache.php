<?php

namespace App\Cache;

final class FileCache
{
    public function __construct(private readonly string $basePath)
    {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0775, true);
        }
    }

    public function remember(string $key, int $ttl, callable $resolver): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $resolver();
        $this->put($key, $value, $ttl);

        return $value;
    }

    public function get(string $key): mixed
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (!is_array($payload) || ($payload['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $payload['value'] ?? null;
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $payload = [
            'expires_at' => time() + $ttl,
            'value' => $value,
        ];

        file_put_contents($this->path($key), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function path(string $key): string
    {
        return rtrim($this->basePath, '/') . '/' . sha1($key) . '.json';
    }
}
