<?php

if (!function_exists('h')) {
    function h(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('email_button')) {
    function email_button(string $url, string $label): string
    {
        return '<a href="' . h($url) . '" style="display:inline-block; background:#2f6fed; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-weight:600; margin-top:12px;">' . h($label) . '</a>';
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . base_url($path));
        exit;
    }
}
