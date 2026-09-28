<?php

namespace App\Services;

use App\Core\ClientIp;
use App\Repositories\LoginAttemptRepository;

final class LoginProtectionService
{
    private array $settings;

    public function __construct(
        private ?LoginAttemptRepository $repository = null,
        ?SecuritySettingsService $settings = null,
    ) {
        $this->repository ??= new LoginAttemptRepository();
        $this->settings = ($settings ?? new SecuritySettingsService())->current();
    }

    public function isBlocked(string $email): bool
    {
        $identifiers = $this->identifiers($email);
        $this->repository->acquireLocks($identifiers);
        return $this->repository->isBlocked(
            $identifiers,
            $this->settings['max_login_attempts'],
            $this->settings['lockout_minutes'],
        );
    }

    public function registerFailure(string $email): bool
    {
        $identifiers = $this->identifiers($email);
        $this->repository->recordFailure($identifiers);
        return $this->repository->isBlocked(
            $identifiers,
            $this->settings['max_login_attempts'],
            $this->settings['lockout_minutes'],
        );
    }

    public function clear(string $email): void
    {
        $this->repository->clearAccount($this->identifiers($email)['account']);
    }

    public function release(string $email): void
    {
        $this->repository->releaseLocks($this->identifiers($email));
    }

    public function lockoutMinutes(): int
    {
        return $this->settings['lockout_minutes'];
    }

    private function identifiers(string $email): array
    {
        $identifiers = [
            'account' => hash('sha256', mb_strtolower(trim($email))),
        ];
        $ip = ClientIp::address();
        if ($ip !== null) {
            $identifiers['ip'] = hash('sha256', $ip);
        }
        return $identifiers;
    }
}
