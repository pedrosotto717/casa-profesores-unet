<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Aporte;
use App\Models\User;
use App\Models\Factura;
use App\Enums\UserStatus;
use App\Enums\TipoFactura;
use App\Enums\EstatusFactura;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class AporteService
{
    /**
     * Create a new aporte and automatically update user solvency.
     */
    public function createAporte(int $userId, float $amount, string $moneda, ?string $aporteDate = null): Aporte
    {
        return DB::transaction(function () use ($userId, $amount, $moneda, $aporteDate) {
            $aporteDate = $aporteDate ? Carbon::parse($aporteDate) : Carbon::today();
            
            // Create the aporte
            $aporte = Aporte::create([
                'user_id' => $userId,
                'amount' => $amount,
                'moneda' => $moneda,
                'aporte_date' => $aporteDate,
            ]);

            // Create associated factura
            $factura = Factura::create([
                'user_id' => $userId,
                'tipo' => TipoFactura::AporteSolvencia,
                'monto' => $amount,
                'moneda' => $moneda,
                'fecha_emision' => $aporteDate,
                'fecha_pago' => $aporteDate,
                'estatus_pago' => EstatusFactura::Pagado,
                'descripcion' => 'Aporte de solvencia mensual',
            ]);

            // Link factura to aporte
            $aporte->update(['factura_id' => $factura->id]);

            // Update user to solvent status
            $this->updateUserSolvency($userId, $aporteDate);

            // Log the action
            $this->logAporteAction('aporte_created', $aporte);

            return $aporte->fresh(['factura']);
        });
    }

    /**
     * Update an existing aporte.
     */
    public function updateAporte(int $aporteId, array $data): Aporte
    {
        return DB::transaction(function () use ($aporteId, $data) {
            $aporte = Aporte::findOrFail($aporteId);
            $oldData = $aporte->toArray();

            $aporte->update($data);

            // Update associated factura if amount, currency or date changed
            if ($aporte->factura && (isset($data['amount']) || isset($data['moneda']) || isset($data['aporte_date']))) {
                $facturaData = [];
                
                if (isset($data['amount'])) {
                    $facturaData['monto'] = $data['amount'];
                }
                
                if (isset($data['moneda'])) {
                    $facturaData['moneda'] = $data['moneda'];
                }
                
                if (isset($data['aporte_date'])) {
                    $aporteDate = Carbon::parse($data['aporte_date']);
                    $facturaData['fecha_emision'] = $aporteDate;
                    $facturaData['fecha_pago'] = $aporteDate;
                    
                    // Recalculate user solvency
                    $this->updateUserSolvency($aporte->user_id, $aporteDate);
                }
                
                if (!empty($facturaData)) {
                    $aporte->factura->update($facturaData);
                }
            }

            // Log the action
            $this->logAporteAction('aporte_updated', $aporte, $oldData);

            return $aporte->fresh(['factura']);
        });
    }

    /**
     * Delete an aporte.
     */
    public function deleteAporte(int $aporteId): bool
    {
        return DB::transaction(function () use ($aporteId) {
            $aporte = Aporte::findOrFail($aporteId);
            $userId = $aporte->user_id;
            $facturaId = $aporte->factura_id;

            // Delete aporte
            $aporte->delete();

            // Delete associated factura if exists
            if ($facturaId) {
                Factura::find($facturaId)?->delete();
            }

            // Recalculate user solvency based on remaining aportes
            $this->recalculateUserSolvency($userId);

            // Log the action
            $this->logAporteAction('aporte_deleted', $aporte);

            return true;
        });
    }

    /**
     * Get paginated list of aportes with optional filters.
     */
    public function getAportesPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = Aporte::with('user');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('aporte_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('aporte_date', '<=', $filters['date_to']);
        }

        if (isset($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (isset($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        return $query->orderBy('aporte_date', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Update user solvency based on aporte date.
     */
    private function updateUserSolvency(int $userId, Carbon $aporteDate): void
    {
        $user = User::findOrFail($userId);
        
        // Calculate solvent_until as aporte_date + 30 days
        $solventUntil = $aporteDate->copy()->addDays(30);

        $user->update([
            'status' => UserStatus::Solvente,
            'solvent_until' => $solventUntil,
        ]);
    }

    /**
     * Recalculate user solvency based on all their aportes.
     */
    private function recalculateUserSolvency(int $userId): void
    {
        $user = User::findOrFail($userId);
        
        // Get the most recent aporte
        $latestAporte = Aporte::where('user_id', $userId)
                             ->orderBy('aporte_date', 'desc')
                             ->first();

        if ($latestAporte) {
            // User has aportes, calculate solvency
            $solventUntil = Carbon::parse($latestAporte->aporte_date)->addDays(30);
            
            $user->update([
                'status' => UserStatus::Solvente,
                'solvent_until' => $solventUntil,
            ]);
        } else {
            // No aportes, mark as insolvent
            $user->update([
                'status' => UserStatus::Insolvente,
                'solvent_until' => null,
            ]);
        }
    }

    /**
     * Log aporte actions for audit trail.
     */
    private function logAporteAction(string $action, Aporte $aporte, ?array $oldData = null): void
    {
        // Skip logging for now to avoid auth issues
        // TODO: Implement proper logging when auth context is available
        return;
    }
}
