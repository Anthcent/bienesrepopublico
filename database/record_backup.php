<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

require __DIR__ . '/../src/Support/autoload.php';

use App\Repositories\BackupRepository;

$action = $argv[1] ?? '';
$options = [];
$arguments = array_slice($argv, 2);
for ($index = 0; $index < count($arguments); $index++) {
    $argument = $arguments[$index];
    if (!str_starts_with($argument, '--')) {
        continue;
    }
    $pair = explode('=', substr($argument, 2), 2);
    $name = $pair[0];
    $value = $pair[1] ?? ($arguments[$index + 1] ?? null);
    if (!isset($pair[1]) && is_string($value) && !str_starts_with($value, '--')) {
        $index++;
    }
    $options[$name] = $value;
}
$startedAt = (string) ($options['started-at'] ?? date('Y-m-d H:i:s'));
try {
    $repository = new BackupRepository();
    if ($action === 'success') {
        $size = filter_var($options['size'] ?? null, FILTER_VALIDATE_INT);
        $checksum = strtolower(trim((string) ($options['checksum'] ?? '')));
        $storage = trim((string) ($options['storage'] ?? ''));
        if ($size === false || $size < 0 || !preg_match('/^[a-f0-9]{64}$/', $checksum) || $storage === '' || mb_strlen($storage) > 120) {
            throw new InvalidArgumentException('success requiere --size, --checksum SHA-256 y --storage (máximo 120 caracteres).');
        }
        $id = $repository->recordSuccess($size, $checksum, $storage, $startedAt);
        echo "Respaldo exitoso registrado con ID {$id}.\n";
        exit(0);
    }

    if ($action === 'failure') {
        $message = trim((string) ($options['message'] ?? ''));
        if ($message === '' || mb_strlen($message) > 500) {
            throw new InvalidArgumentException('failure requiere --message de hasta 500 caracteres.');
        }
        $id = $repository->recordFailure($message, $startedAt);
        echo "Fallo de respaldo registrado con ID {$id}.\n";
        exit(0);
    }

    if ($action === 'restore-verified') {
        $id = filter_var($options['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || !$repository->markRestoreVerified($id)) {
            throw new InvalidArgumentException('restore-verified requiere el --id de un respaldo exitoso existente.');
        }
        echo "Recuperación del respaldo {$id} marcada como verificada.\n";
        exit(0);
    }

    throw new InvalidArgumentException('Acción válida: success, failure o restore-verified.');
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
