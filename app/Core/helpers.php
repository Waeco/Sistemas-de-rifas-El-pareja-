<?php

declare(strict_types=1);

use App\Core\Csrf;

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $value): string {
    return '$' . number_format((float)$value, 2, '.', ',') . ' MXN';
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function route(string $name, array $params = []): string {
    $query = http_build_query(array_merge(['route' => $name], $params));
    return 'index.php?' . $query;
}

function old(string $key, string $default = ''): string {
    return e($_POST[$key] ?? $default);
}

function flash(string $key, ?string $message = null): ?string {
    \App\Core\Auth::start();
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function abort_403(): never {
    http_response_code(403);
    echo '403 - Acceso no autorizado';
    exit;
}
