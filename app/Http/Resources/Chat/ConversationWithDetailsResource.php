<?php declare(strict_types=1);

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationWithDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $otherParticipant = $this->otherParticipantFor($currentUser);
        $lastMessage = $this->messages->first();
        $unreadCount = $this->unread_count ?? 0;

        return [
            'id' => $this->id,
            'other_participant' => $otherParticipant ? [
                'id' => $otherParticipant->id,
                'name' => $otherParticipant->name,
                'email' => $otherParticipant->email,
                'role' => $otherParticipant->role->value,
            ] : null,
            'last_message' => $lastMessage ? [
                'id' => $lastMessage->id,
                'body' => $this->truncateMessage($lastMessage->body),
                'sender_id' => $lastMessage->sender_id,
                'created_at' => $lastMessage->created_at,
            ] : null,
            'unread_count' => $unreadCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Truncate message body to 50 characters for preview.
     */
    private function truncateMessage(string $body): string
    {
        return strlen($body) > 50 ? substr($body, 0, 50) . '...' : $body;
    }
}
