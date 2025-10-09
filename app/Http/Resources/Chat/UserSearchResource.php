<?php declare(strict_types=1);

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $hasBlockedMe = $this->blocksCreated()
            ->where('blocked_id', $currentUser->id)
            ->active()
            ->exists();
        $iBlockedThem = $currentUser->blocksCreated()
            ->where('blocked_id', $this->id)
            ->active()
            ->exists();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'has_blocked_me' => $hasBlockedMe,
            'i_blocked_them' => $iBlockedThem,
        ];
    }
}
