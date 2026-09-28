<?php

namespace App\Core;

final class ClientIp
{
    public static function address(): ?string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!is_string($remote) || !filter_var($remote, FILTER_VALIDATE_IP)) {
            return null;
        }

        $config = require __DIR__ . '/../Config/config.php';
        if (self::isTrustedProxy($remote, $config['app']['trusted_proxies'])) {
            $chain = array_values(array_filter(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))), static function (string $ip): bool {
                return (bool) filter_var($ip, FILTER_VALIDATE_IP);
            }));
            $chain[] = $remote;
            for ($index = count($chain) - 1; $index >= 0; $index--) {
                if (!self::isTrustedProxy($chain[$index], $config['app']['trusted_proxies'])) {
                    return $chain[$index];
                }
            }
        }

        $isPublic = filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        if ($config['app']['environment'] === 'production' && !$isPublic) {
            return null;
        }

        return $remote;
    }

    private static function isTrustedProxy(string $remote, array $trustedProxies): bool
    {
        foreach ($trustedProxies as $trusted) {
            if ($remote === $trusted) {
                return true;
            }
            if (!str_contains($trusted, '/') || !filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            [$network, $prefix] = array_pad(explode('/', $trusted, 2), 2, null);
            if (!filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !ctype_digit((string) $prefix)) {
                continue;
            }
            $prefix = (int) $prefix;
            if ($prefix < 0 || $prefix > 32) {
                continue;
            }
            $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
            if ((ip2long($remote) & $mask) === (ip2long($network) & $mask)) {
                return true;
            }
        }
        return false;
    }
}
