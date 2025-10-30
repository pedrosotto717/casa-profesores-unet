<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\BeneficiarioEstatus;
use App\Models\AuditLog;
use App\Models\Beneficiario;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class BeneficiarioService
{
    /**
     * Create a new beneficiario with pending status.
     */
    public function create(int $agremiado_id, array $data): Beneficiario
    {
        return DB::transaction(function () use ($agremiado_id, $data) {
            $beneficiario = Beneficiario::create([
                'agremiado_id' => $agremiado_id,
                'nombre_completo' => $data['nombre_completo'],
                'parentesco' => $data['parentesco'],
                'estatus' => BeneficiarioEstatus::Pendiente,
            ]);

            // Log audit
            $this->logAudit(
                action: 'create',
                beneficiario: $beneficiario,
                user_id: $agremiado_id,
                before: null,
                after: [
                    'nombre_completo' => $beneficiario->nombre_completo,
                    'parentesco' => $beneficiario->parentesco->value,
                    'estatus' => $beneficiario->estatus->value,
                ]
            );

            return $beneficiario;
        });
    }

    /**
     * Update beneficiario data.
     */
    public function update(Beneficiario $beneficiario, array $data, int $user_id): Beneficiario
    {
        return DB::transaction(function () use ($beneficiario, $data, $user_id) {
            $oldData = [
                'nombre_completo' => $beneficiario->nombre_completo,
                'parentesco' => $beneficiario->parentesco->value,
            ];

            $beneficiario->update($data);

            // Log audit
            $this->logAudit(
                action: 'update',
                beneficiario: $beneficiario,
                user_id: $user_id,
                before: $oldData,
                after: [
                    'nombre_completo' => $beneficiario->nombre_completo,
                    'parentesco' => $beneficiario->parentesco->value,
                ]
            );

            return $beneficiario;
        });
    }

    /**
     * Approve a pending beneficiario.
     */
    public function approve(Beneficiario $beneficiario, User $admin): Beneficiario
    {
        return DB::transaction(function () use ($beneficiario, $admin) {
            $beneficiario->update(['estatus' => BeneficiarioEstatus::Aprobado]);

            // Log audit
            $this->logAudit(
                action: 'approve',
                beneficiario: $beneficiario,
                user_id: $admin->id,
                before: ['estatus' => BeneficiarioEstatus::Pendiente->value],
                after: ['estatus' => $beneficiario->estatus->value]
            );

            return $beneficiario;
        });
    }

    /**
     * Reject a pending beneficiario.
     */
    public function reject(Beneficiario $beneficiario, User $admin): Beneficiario
    {
        return DB::transaction(function () use ($beneficiario, $admin) {
            $beneficiario->update(['estatus' => BeneficiarioEstatus::Inactivo]);

            // Log audit
            $this->logAudit(
                action: 'reject',
                beneficiario: $beneficiario,
                user_id: $admin->id,
                before: ['estatus' => BeneficiarioEstatus::Pendiente->value],
                after: ['estatus' => $beneficiario->estatus->value]
            );

            return $beneficiario;
        });
    }

    /**
     * Delete a beneficiario.
     */
    public function delete(Beneficiario $beneficiario): void
    {
        $beneficiario->delete();
    }

    /**
     * Log audit information.
     */
    private function logAudit(
        string $action,
        Beneficiario $beneficiario,
        int $user_id,
        ?array $before,
        ?array $after
    ): void {
        AuditLog::create([
            'entity_type' => Beneficiario::class,
            'entity_id' => $beneficiario->id,
            'user_id' => $user_id,
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
