<?php
/**
 * Tiny JSON-over-HTTP client with a disk cache.
 *
 * Every remote call goes through here so a slow or unreachable API degrades
 * into a friendly message instead of a fatal error.
 */

declare(strict_types=1);

/**
 * Fetch and decode JSON, serving from the cache when a fresh copy exists.
 *
 * @return array{ok: bool, data: array, error: ?string, cached: bool}
 */
function fetchJson(string $url, int $ttl = 3600): array
{
    $cached = cacheGet($url, $ttl);
    if ($cached !== null) {
        return ['ok' => true, 'data' => $cached, 'error' => null, 'cached' => true];
    }

    [$body, $error] = httpGet($url);

    if ($body === null) {
        // Fall back to a stale copy rather than showing nothing at all.
        $stale = cacheGet($url, PHP_INT_MAX);
        if ($stale !== null) {
            return ['ok' => true, 'data' => $stale, 'error' => null, 'cached' => true];
        }

        return ['ok' => false, 'data' => [], 'error' => $error, 'cached' => false];
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'data' => [], 'error' => 'The service returned an unreadable response.', 'cached' => false];
    }

    cachePut($url, $decoded);

    return ['ok' => true, 'data' => $decoded, 'error' => null, 'cached' => false];
}

/**
 * Perform the GET request. Returns [body, error]; body is null on failure.
 *
 * @return array{0: ?string, 1: ?string}
 */
function httpGet(string $url): array
{
    $timeout = (int) config('api.timeout', 12);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => 'Noor/1.0 (+https://github.com/Towfique-Ahmed/noor)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            return [null, 'Could not reach the service: ' . ($err ?: 'unknown error')];
        }
        if ($status >= 400) {
            return [null, 'The service replied with HTTP ' . $status . '.'];
        }

        return [(string) $body, null];
    }

    $context = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => $timeout,
            'header'        => "Accept: application/json\r\nUser-Agent: Noor/1.0\r\n",
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return [null, 'Could not reach the service.'];
    }

    return [$body, null];
}

/**
 * Absolute path of the cache file for a key.
 */
function cachePath(string $key): string
{
    return rtrim((string) config('cache.dir'), '/') . '/' . sha1($key) . '.json';
}

/**
 * Read a cache entry if it is younger than $ttl seconds.
 */
function cacheGet(string $key, int $ttl): ?array
{
    $file = cachePath($key);
    if (!is_file($file)) {
        return null;
    }

    $age = time() - (int) filemtime($file);
    if ($ttl !== PHP_INT_MAX && $age > $ttl) {
        return null;
    }

    $decoded = json_decode((string) file_get_contents($file), true);

    return is_array($decoded) ? $decoded : null;
}

/**
 * Write a cache entry, creating the cache directory when needed.
 */
function cachePut(string $key, array $value): void
{
    $dir = rtrim((string) config('cache.dir'), '/');
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return;
    }

    $tmp = cachePath($key) . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, json_encode($value, JSON_UNESCAPED_UNICODE)) !== false) {
        @rename($tmp, cachePath($key));
    }
}
