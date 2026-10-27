<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        Auth::start();
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function check(?string $token): bool
    {
        Auth::start();
        return is_string($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
    }
}
