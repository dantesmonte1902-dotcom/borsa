<?php

namespace App\Services;

use RuntimeException;

final class Config
{
    private static array $loaded = [];

    public static function all(string $file): array
    {
        if (!isset(self::$loaded[$file])) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            if (!is_file($path)) {
                throw new RuntimeException('Config file not found: ' . $file);
            }

            self::$loaded[$file] = require $path;
        }

        return self::$loaded[$file];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        [$file, $path] = array_pad(explode('.', $key, 2), 2, null);
        $config = self::all($file);

        if ($path === null) {
            return $config;
        }

        $segments = explode('.', $path);
        $value = $config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
