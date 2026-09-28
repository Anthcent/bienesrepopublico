<?php

namespace App\Services;

use App\Repositories\BackupRepository;
use DateTimeImmutable;

final class BackupStatusService
{
    public function __construct(private ?BackupRepository $repository = null)
    {
        $this->repository ??= new BackupRepository();
    }

    public function status(int $warningHours): array
    {
        $latest = $this->repository->latest();
        $verified = $this->repository->lastVerifiedRestore();
        $state = 'missing';
        $ageHours = null;

        if ($latest) {
            $ageHours = max(0, (int) floor((time() - (new DateTimeImmutable($latest['completed_at']))->getTimestamp()) / 3600));
            $state = $latest['status'] === 'failed'
                ? 'failed'
                : ($ageHours > $warningHours ? 'stale' : 'healthy');
        }

        return [
            'state' => $state,
            'age_hours' => $ageHours,
            'latest' => $latest,
            'last_verified_restore' => $verified,
        ];
    }
}
