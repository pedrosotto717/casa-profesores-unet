<?php declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'name',
        'email',
        'password',
        'sso_uid',
        'status',
        'responsible_email',
        'aspired_role',
        'solvent_until',
        'auth_code',
        'auth_code_expires_at',
        'auth_code_attempts',
        'last_code_sent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => \App\Enums\UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => \App\Enums\UserStatus::class,
            'aspired_role' => \App\Enums\AspiredRole::class,
            'solvent_until' => 'date',
            'auth_code_expires_at' => 'datetime',
            'last_code_sent_at' => 'datetime',
        ];
    }

    /**
     * Get conversations where this user is the first participant.
     */
    public function conversationsAsUserOne()
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    /**
     * Get conversations where this user is the second participant.
     */
    public function conversationsAsUserTwo()
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    /**
     * Get all conversations for this user.
     */
    public function conversations()
    {
        return $this->conversationsAsUserOne->merge($this->conversationsAsUserTwo);
    }

    /**
     * Get messages sent by this user.
     */
    public function sentMessages()
    {
        return $this->hasMany(ConversationMessage::class, 'sender_id');
    }

    /**
     * Get messages received by this user.
     */
    public function receivedMessages()
    {
        return $this->hasMany(ConversationMessage::class, 'receiver_id');
    }

    /**
     * Get read statuses for this user.
     */
    public function conversationReads()
    {
        return $this->hasMany(ConversationRead::class);
    }

    /**
     * Get blocks created by this user.
     */
    public function blocksCreated()
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /**
     * Get blocks where this user is blocked.
     */
    public function blocksReceived()
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    /**
     * Get aportes made by this user.
     */
    public function aportes()
    {
        return $this->hasMany(Aporte::class);
    }

    /**
     * Get facturas of this user.
     */
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

}
