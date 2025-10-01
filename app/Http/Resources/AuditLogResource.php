<?php declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'action' => $this->action,
            'actor' => $this->when($this->user, [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'role' => $this->user?->role,
            ]),
            'entity' => [
                'type' => $this->entity_type,
                'id' => $this->entity_id,
                'label' => $this->getEntityLabel(),
            ],
            'before' => $this->sanitizeData($this->before),
            'after' => $this->sanitizeData($this->after),
        ];
    }

    /**
     * Get a human-readable label for the entity.
     */
    private function getEntityLabel(): ?string
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        return match ($this->entity_type) {
            'Area' => "Area #{$this->entity_id}",
            'Academy' => "Academy #{$this->entity_id}",
            'User' => "User #{$this->entity_id}",
            'Invitation' => "Invitation #{$this->entity_id}",
            'File' => "File #{$this->entity_id}",
            default => "{$this->entity_type} #{$this->entity_id}",
        };
    }

    /**
     * Sanitize sensitive data from before/after arrays.
     */
    private function sanitizeData(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $sensitiveFields = [
            'password',
            'password_confirmation',
            'remember_token',
            'api_token',
            'access_token',
            'refresh_token',
            'secret',
            'private_key',
            'ssn',
            'social_security_number',
            'credit_card',
            'bank_account',
        ];

        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $sanitized)) {
                $sanitized[$field] = '[REDACTED]';
            }
        }

        return $sanitized;
    }
}
