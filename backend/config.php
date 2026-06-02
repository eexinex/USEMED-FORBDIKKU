<?php
// backend/config.php
// Core config + helpers for USE MED

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_save_path('/tmp');
    session_set_cookie_params([
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => $isSecure ? 'None' : 'Lax'
    ]);
    session_start();
}

if (!function_exists('envv')) {
    function envv(string $key, $default = null) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return $_ENV[$key] ?? $default;
    }
}

// Optional local config. You may create either file and define DB constants there.
foreach ([__DIR__ . '/config.local.php', __DIR__ . '/config/local.php'] as $localConfig) {
    if (is_file($localConfig)) {
        require_once $localConfig;
    }
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'USE MED');
}

if (!function_exists('detect_app_base')) {
    function detect_app_base(): string
    {
        // Hugging Face Spaces provides SPACE_HOST (e.g. username-spacename.hf.space)
        $spaceHost = getenv('SPACE_HOST');
        if ($spaceHost !== false && $spaceHost !== '') {
            return 'https://' . $spaceHost;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';

        if ($script !== '') {
            $pos = strpos($script, '/public/');
            if ($pos !== false) {
                return rtrim(substr($script, 0, $pos + strlen('/public')), '/');
            }

            if (str_ends_with($script, '/public')) {
                return rtrim($script, '/');
            }
        }

        // If we are here, we are likely running directly from DocumentRoot (e.g. Docker / HF Spaces)
        // Return empty string so APP_URL becomes '' and paths start with '/'
        return '';
    }
}

if (!defined('APP_URL')) {
    // Ignore USEMED_PUBLIC_URL environment variable to prevent wrong manual settings from breaking the app
    define('APP_URL', rtrim(detect_app_base(), '/'));
}

if (!defined('LINE_CHANNEL_ACCESS_TOKEN')) {
    define('LINE_CHANNEL_ACCESS_TOKEN', (string) envv('LINE_CHANNEL_ACCESS_TOKEN', ''));
}

if (!defined('LINE_CHANNEL_SECRET')) {
    define('LINE_CHANNEL_SECRET', (string) envv('LINE_CHANNEL_SECRET', ''));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', (string) envv('DB_HOST', ''));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', (string) envv('DB_NAME', ''));
}

if (!defined('DB_USER')) {
    define('DB_USER', (string) envv('DB_USER', ''));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', (string) envv('DB_PASS', ''));
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', (string) envv('DB_CHARSET', 'utf8mb4'));
}

if (!defined('DEMO_MODE')) {
    $hasDbConfig = DB_HOST !== '' && DB_NAME !== '' && DB_USER !== '';
    $envDemo = envv('USEMED_DEMO_MODE', null);
    define('DEMO_MODE', $envDemo === null ? !$hasDbConfig : !in_array((string) $envDemo, ['0', 'false', 'FALSE', 'off', 'OFF'], true));
}

if (!defined('DEMO_PATIENT_HN')) {
    define('DEMO_PATIENT_HN', (string) envv('DEMO_PATIENT_HN', 'HN0001'));
}
if (!defined('DEMO_PATIENT_PASSWORD')) {
    define('DEMO_PATIENT_PASSWORD', (string) envv('DEMO_PATIENT_PASSWORD', '123456'));
}
if (!defined('DEMO_DOCTOR_USERNAME')) {
    define('DEMO_DOCTOR_USERNAME', (string) envv('DEMO_DOCTOR_USERNAME', 'doctor1'));
}
if (!defined('DEMO_DOCTOR_PASSWORD')) {
    define('DEMO_DOCTOR_PASSWORD', (string) envv('DEMO_DOCTOR_PASSWORD', '123456'));
}
if (!defined('DEMO_ADMIN_USERNAME')) {
    define('DEMO_ADMIN_USERNAME', (string) envv('DEMO_ADMIN_USERNAME', 'admin'));
}
if (!defined('DEMO_ADMIN_PASSWORD')) {
    define('DEMO_ADMIN_PASSWORD', (string) envv('DEMO_ADMIN_PASSWORD', 'admin123'));
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $path = trim($path);
        if ($path === '') {
            return APP_URL . '/';
        }
        if (preg_match('~^https?://~i', $path) === 1) {
            return $path;
        }
        return APP_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('frontend_url')) {
    function frontend_url(string $path = ''): string
    {
        $base = rtrim((string) APP_URL, '/');
        if (str_ends_with($base, '/public')) {
            $base = substr($base, 0, -strlen('/public')) . '/frontend';
        } else {
            // For absolute URL or empty, just append /frontend if there's no path
            // But actually frontend is NOT in public/ on DocumentRoot.
            // Wait, if DocumentRoot is public/, then /frontend is NOT ACCESSIBLE!
            // We should use an absolute path or relative path, but since it's not served, 
            // frontend_url is only used for the LINE Bot CSS. We'll just return APP_URL/../frontend
            $parts = parse_url($base);
            if (isset($parts['scheme']) && isset($parts['host'])) {
                $base = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . (isset($parts['path']) ? dirname($parts['path']) : '') . '/frontend';
            } else {
                $base = $base === '' ? '/frontend' : rtrim(dirname($base), '/') . '/frontend';
            }
            $base = str_replace('\\', '/', $base);
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('is_post')) {
    function is_post(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path): void
    {
        header('Location: ' . app_url($path));
        exit;
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('flash_get')) {
    function flash_get(): ?array
    {
        if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
}

if (!function_exists('active_class')) {
    function active_class(string $active, string $key): string
    {
        return $active === $key ? 'active' : '';
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'UM';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($name, 0, 2, 'UTF-8');
        }

        return substr($name, 0, 2);
    }
}

if (!function_exists('badge_class')) {
    function badge_class(string $value): string
    {
        $v = strtolower(trim($value));
        if (str_contains($v, 'low') || str_contains($v, 'ต่ำ')) {
            return 'green';
        }
        if (str_contains($v, 'medium') || str_contains($v, 'กลาง')) {
            return 'orange';
        }
        if (str_contains($v, 'high') || str_contains($v, 'สูง')) {
            return 'red';
        }
        return 'blue';
    }
}
