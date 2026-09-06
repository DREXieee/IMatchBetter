<?php

if (!function_exists('h')) {
    function h(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    /**
     * Builds an absolute URL under the app's base path. The subdirectory portion (e.g.
     * "/imatchbetter") always comes from APP_URL, since that reflects where the app is
     * actually deployed — but the scheme+host are taken from the current request when one
     * exists, so links work correctly whether the app is reached via localhost, 127.0.0.1,
     * a LAN IP, or (eventually) a real domain, without needing APP_URL edited per device.
     * Falls back entirely to APP_URL outside a request context (CLI scripts, cron, tests).
     */
    function base_url(string $path = ''): string
    {
        static $appBasePath = null;

        if ($appBasePath === null) {
            $configured = parse_url($_ENV['APP_URL'] ?? '');
            $appBasePath = rtrim($configured['path'] ?? '', '/');
        }

        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $origin = $scheme . '://' . $_SERVER['HTTP_HOST'];
        } else {
            $configured = parse_url($_ENV['APP_URL'] ?? '');
            $origin = ($configured['scheme'] ?? 'http') . '://' . ($configured['host'] ?? 'localhost')
                . (isset($configured['port']) ? ':' . $configured['port'] : '');
        }

        return $origin . $appBasePath . '/' . ltrim($path, '/');
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

if (!function_exists('initials')) {
    /**
     * First letter of the first word + first letter of the last word (e.g. "QA Lifecycle
     * Tester" -> "QT"), for the circular avatar placeholder shown when no photo is set.
     */
    function initials(string $fullName): string
    {
        $words = preg_split('/\s+/', trim($fullName)) ?: [];
        $words = array_filter($words, static fn (string $w): bool => $w !== '');
        $words = array_values($words);

        if (empty($words)) {
            return '';
        }
        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . base_url($path));
        exit;
    }
}
