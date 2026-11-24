<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodeHistory extends Model
{
    use HasFactory;

    protected $table = 'periode_histories';

    public $timestamps = false;

    protected $fillable = [
        'periode_id',
        'user_id',
        'aksi',
        'alasan',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke periode penyusunan terkait
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePenyusunan::class, 'periode_id');
    }

    /**
     * Relasi ke User (Admin) yang mengeksekusi aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

