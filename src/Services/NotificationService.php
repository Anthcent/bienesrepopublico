<?php

namespace App\Services;

use App\Core\Session;
use App\Repositories\LoanRepository;
use App\Repositories\NotificationRepository;

/**
 * Fuente de verdad de las alertas persistentes (tabla `notifications`).
 * Sin dependencia obligatoria de cron: se sincroniza en puntos de entrada
 * de la aplicación (login, dashboard, módulo préstamos, shell autenticado
 * con throttling) usando UPSERT sobre una clave idempotente.
 */
final class NotificationService
{
    private NotificationRepository $notifications;
    private LoanRepository $loans;

    public function __construct()
    {
        $this->notifications = new NotificationRepository();
        $this->loans = new LoanRepository();
    }

    public function syncLoanAlerts(int $threshold = 3): void
    {
        $loans = $this->loans->dueSoonOrOverdue($threshold);
        $userIds = $this->notifications->allActiveUserIds();
        $today = new \DateTimeImmutable('today');
        $activeLoanIds = [];

        foreach ($loans as $loan) {
            $activeLoanIds[] = (int) $loan['id'];
            $due = new \DateTimeImmutable($loan['fecha_vencimiento']);
            $diff = (int) $today->diff($due)->format('%r%a');

            if ($diff < 0) {
                $nivel = 'danger';
                $titulo = 'Préstamo vencido';
                $mensaje = sprintf('%s debía devolverse el %s y sigue abierto.', $loan['codigo'], $due->format('d/m/Y'));
                $clavePrefix = "loan.overdue.{$loan['id']}";
            } elseif ($diff === 0) {
                $nivel = 'warning';
                $titulo = 'Préstamo vence hoy';
                $mensaje = sprintf('%s vence hoy.', $loan['codigo']);
                $clavePrefix = "loan.duetoday.{$loan['id']}";
            } else {
                $nivel = 'warning';
                $titulo = 'Préstamo próximo a vencer';
                $mensaje = sprintf('%s vence el %s (en %d día%s).', $loan['codigo'], $due->format('d/m/Y'), $diff, $diff === 1 ? '' : 's');
                $clavePrefix = "loan.dueSoon.{$loan['id']}";
            }

            $activeKeys = array_map(
                fn (int $userId): string => $clavePrefix . '.' . $due->format('Y-m-d') . '.' . $userId,
                $userIds
            );
            $this->notifications->purgeOtherLoanAlertCycles((int) $loan['id'], $activeKeys);

            foreach ($userIds as $index => $userId) {
                $this->notifications->upsert([
                    'tipo' => 'loan_alert',
                    'usuario_destino_id' => $userId,
                    'titulo' => $titulo,
                    'mensaje' => $mensaje,
                    'nivel' => $nivel,
                    'entidad_tipo' => 'loan',
                    'entidad_id' => $loan['id'],
                    'clave_unica' => $activeKeys[$index],
                ]);
            }
        }

        $this->notifications->purgeLoanAlertsOutside($activeLoanIds);
    }

    /** Sincroniza con throttling para no golpear la BD en cada request del shell. */
    public function syncLoanAlertsThrottled(int $threshold, int $throttleSeconds): void
    {
        $last = Session::get('_last_notif_sync', 0);
        $lastThreshold = Session::get('_last_notif_sync_threshold');
        if ($lastThreshold === $threshold && time() - $last < $throttleSeconds) {
            return;
        }
        $this->syncLoanAlerts($threshold);
        Session::set('_last_notif_sync', time());
        Session::set('_last_notif_sync_threshold', $threshold);
    }

    public function notifyIncompleteInfo(int $userId, int $assetId, string $numeroBien): void
    {
        $this->notifications->upsert([
            'tipo' => 'incomplete_info',
            'usuario_destino_id' => $userId,
            'titulo' => 'Información incompleta',
            'mensaje' => sprintf('%s no tiene fotografía o características secundarias completas.', $numeroBien),
            'nivel' => 'info',
            'entidad_tipo' => 'asset',
            'entidad_id' => $assetId,
            'clave_unica' => "asset.incomplete.{$assetId}.{$userId}",
        ]);
    }

    public function notifyReturnDamage(array $userIds, int $loanId, string $codigo, string $numeroBien): void
    {
        foreach ($userIds as $userId) {
            $this->notifications->upsert([
                'tipo' => 'return_damage',
                'usuario_destino_id' => $userId,
                'titulo' => 'Deterioro detectado en devolución',
                'mensaje' => sprintf('%s (%s) presentó un empeoramiento de estado físico al devolverse.', $numeroBien, $codigo),
                'nivel' => 'danger',
                'entidad_tipo' => 'loan',
                'entidad_id' => $loanId,
                'clave_unica' => "loan.damage.{$loanId}.{$numeroBien}." . time(),
            ]);
        }
    }

    public function resolveForLoan(int $loanId): void
    {
        $this->notifications->resolveByKeyPrefix("loan.overdue.{$loanId}.");
        $this->notifications->resolveByKeyPrefix("loan.duetoday.{$loanId}.");
        $this->notifications->resolveByKeyPrefix("loan.dueSoon.{$loanId}.");
    }

    public function forUser(int $userId): array
    {
        return $this->notifications->forUser($userId);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->notifications->markRead($id, $userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->notifications->markAllRead($userId);
    }

    public function markResolved(int $id, int $userId): void
    {
        $this->notifications->markResolved($id, $userId);
    }
}
