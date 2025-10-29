<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Factura;
use Illuminate\Pagination\LengthAwarePaginator;

final class FacturaService
{
    /**
     * Create a new factura.
     */
    public function createFactura(array $data): Factura
    {
        return Factura::create($data);
    }

    /**
     * Get facturas by user with optional filters.
     */
    public function getFacturasByUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Factura::where('user_id', $userId);

        return $this->applyFiltersAndPaginate($query, $filters);
    }

    /**
     * Get all facturas with optional filters.
     */
    public function getAllFacturas(array $filters = []): LengthAwarePaginator
    {
        $query = Factura::with('user');

        return $this->applyFiltersAndPaginate($query, $filters);
    }

    /**
     * Get a single factura by ID.
     */
    public function getFacturaById(int $id): Factura
    {
        return Factura::with('user')->findOrFail($id);
    }

    /**
     * Apply filters and paginate query.
     */
    private function applyFiltersAndPaginate($query, array $filters): LengthAwarePaginator
    {
        if (isset($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (isset($filters['estatus_pago'])) {
            $query->where('estatus_pago', $filters['estatus_pago']);
        }

        if (isset($filters['fecha_desde'])) {
            $query->where('fecha_emision', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('fecha_emision', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['monto_min'])) {
            $query->where('monto', '>=', $filters['monto_min']);
        }

        if (isset($filters['monto_max'])) {
            $query->where('monto', '<=', $filters['monto_max']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }
}

