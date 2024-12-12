<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Session extends Model
{
    use HasFactory;
    protected $table = 'customer_sessions';
    protected $fillable = ['user_id', 'activated_at', 'appointment_at'];

    protected $casts = [
        'activated_at' => 'datetime',
        'appointment_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 