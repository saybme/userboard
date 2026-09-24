<?php namespace Saybme\Ub\Models;

use Model;

class EmailLoginToken extends Model
{
    public $table = 'saybme_ub_email_login_tokens';

    protected $fillable = [
        'user_id',
        'email',
        'token_hash',
        'status',
        'ip_address',
        'expires_at',
        'used_at',
    ];

    protected $dates = [
        'expires_at',
        'used_at',
        'created_at',
        'updated_at',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
