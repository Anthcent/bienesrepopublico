<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$entries = array_values(array_filter(array_map('trim', explode(',', getenv('TRUSTED_PROXIES') ?: ''))));
if (!$entries) {
    fwrite(STDERR, "TRUSTED_PROXIES no puede estar vacío en producción.\n");
    exit(1);
}

foreach ($entries as $entry) {
    if (filter_var($entry, FILTER_VALIDATE_IP)) {
        continue;
    }
    if (str_contains($entry, '/')) {
        [$network, $prefix] = array_pad(explode('/', $entry, 2), 2, null);
        if (
            filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && ctype_digit((string) $prefix)
            && (int) $prefix >= 0
            && (int) $prefix <= 32
        ) {
            continue;
        }
    }
    fwrite(STDERR, "TRUSTED_PROXIES contiene una IP o red CIDR inválida: {$entry}\n");
    exit(1);
}

echo "Configuración de proxy confiable validada.\n";
