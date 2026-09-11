<?php namespace Saybme\Ub\Models;

use Model;

class AuthChallenge extends Model
{
    public $table = 'saybme_ub_auth_challenges';

    protected $fillable = [
        'provider',
        'start_token_hash',
        'completion_token_hash',
        'expected_phone',
        'verified_phone',
        'provider_user_id',
        'telegram_chat_id',
        'status',
        'session_id',
        'ip_address',
        'expires_at',
        'verified_at',
        'used_at',
    ];

    protected $dates = [
        'expires_at',
        'verified_at',
        'used_at',
        'created_at',
        'updated_at',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
