<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;

class TahapPenyusunan extends Model
{
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'periode_id',
        'tahap',
        'nama_tahap',
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'deskripsi',
        'deskripsi_tahap',
        'status',
        'activated_at',
        'deactivated_at',
        'completed_at',
        'activated_by',
        'completed_by',
    ];

    protected $casts = [
        'periode_id' => 'integer',
        'tahap' => 'integer',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePenyusunan::class, 'periode_id');
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function moduls(): HasMany
    {
        return $this->hasMany(Modul::class, 'tahap_id');
    }

    public function finalDrafts(): HasManyThrough
    {
        return $this->hasManyThrough(FinalDraft::class, Modul::class, 'tahap_id', 'penyusun_application_id', 'id', 'penyusun_application_id');
    }

    public function isActiveStatus(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactiveStatus(): bool
    {
        return ($this->status ?? self::STATUS_INACTIVE) === self::STATUS_INACTIVE;
    }

    public function isCompletedStatus(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_COMPLETED => 'Selesai',
            default => 'Nonaktif Sementara',
        };
    }

    public function isCurrentlyActive(): bool
    {
        return $this->isActiveStatus();
    }

    public function isPast(): bool
    {
        return $this->isCompletedStatus() || Carbon::now()->toDateString() > $this->tanggal_selesai;
    }

    public function isFuture(): bool
    {
        return $this->isInactiveStatus();
    }

    public function isAccessible(): bool
    {
        return $this->isActiveStatus();
    }

    public function canStillUpload(): bool
    {
        return $this->isActiveStatus();
    }

    public function hasUnfinishedModuls($penyusunApplicationId): bool
    {
        // Cek apakah ada modul dalam tahap ini yang belum diupload atau belum divalidasi
        $modul = Modul::where('penyusun_application_id', $penyusunApplicationId)
            ->where('tahap_id', $this->id)
            ->first();

        if (!$modul) {
            return true; // Belum ada modul yang diupload
        }

        // Cek apakah modul masih pending atau ditolak
        return in_array($modul->status, ['pending', 'rejected']);
    }

    public function isAccessibleForUser($penyusunApplicationId): bool
    {
        // Tahap hanya dapat diakses apabila status eksplisitnya adalah Aktif ('active')
        // dan periode induknya masih berstatus aktif.
        if (!$this->isActiveStatus()) {
            return false;
        }

        if ($this->periode && !$this->periode->isActive()) {
            return false;
        }

        return true;
    }

    public function getModulStart(): int
    {
        if ($this->tahap == 1) {
            return 1;
        } elseif ($this->tahap == 2) {
            return 3;
        } elseif ($this->tahap == 3) {
            return 5;
        } else {
            return ($this->tahap - 3) + 5;
        }
    }

    public function getModulEnd(): int
    {
        if ($this->tahap == 1) {
            return 2;
        } elseif ($this->tahap == 2) {
            return 4;
        } elseif ($this->tahap == 3) {
            return 6;
        } else {
            return ($this->tahap - 3) + 6;
        }
    }

    public function scopeInActivePeriod($query)
    {
        return $query->whereHas('periode', function ($q) {
            $q->where('status', 'active');
        });
    }

    public function scopeCurrent($query)
    {
        return $query->whereHas('periode', fn($q) => $q->where('status', 'active'))
                    ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeAccessible($query)
    {
        return $query->whereHas('periode', fn($q) => $q->where('status', 'active'))
                    ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeGlobal($query)
    {
        // Untuk tahap global, ambil tahap yang terhubung ke periode aktif
        return $query->whereHas('periode', fn($q) => $q->where('status', 'active'));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->isCurrentlyActive();
    }
}
