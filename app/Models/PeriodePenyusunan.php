<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class PeriodePenyusunan extends Model
{
    use HasFactory;

    protected $table = 'periode_penyusunans';

    protected $attributes = [
        'status' => 'draft',
    ];

    protected $fillable = [
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'deskripsi',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'archived_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($periode) {
            // Proteksi 1: Periode yang sudah archived tidak dapat diubah statusnya menjadi active atau draft
            if ($periode->exists && $periode->getOriginal('status') === 'archived' && $periode->status !== 'archived') {
                throw new \DomainException('Periode yang sudah diarsipkan tidak dapat diaktifkan kembali atau diubah statusnya.');
            }

            // Proteksi 2: Periode yang diaktifkan harus valid (tidak expired dan maksimal satu aktif)
            if ($periode->status === 'active') {
                // Cegah aktivasi jika tanggal selesai sudah terlewati
                if ($periode->tanggal_selesai) {
                    $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
                    $tanggalSelesai = \Carbon\Carbon::parse($periode->tanggal_selesai)->toDateString();

                    if ($tanggalSelesai < $today) {
                        throw new \DomainException('Periode dengan tanggal selesai yang sudah terlewati tidak dapat diaktifkan.');
                    }
                }

                // Cegah pembuatan / pembaruan periode aktif jika sudah ada periode aktif lain
                $existingActive = static::where('status', 'active')
                    ->where('id', '!=', $periode->id ?? 0)
                    ->exists();

                if ($existingActive) {
                    throw new \InvalidArgumentException('Hanya boleh ada satu periode berstatus aktif dalam satu waktu.');
                }
            }
        });

        // Proteksi integritas: cegah penghapusan periode jika berstatus archived atau memiliki relasi data
        static::deleting(function ($periode) {
            if ($periode->isArchived()) {
                throw new \DomainException('Periode yang sudah diarsipkan tidak boleh dihapus.');
            }

            if ($periode->penyusunApplications()->exists() || $periode->tahapPenyusunans()->exists() || $periode->histories()->exists()) {
                throw new \DomainException('Periode penyusunan tidak dapat dihapus karena masih memiliki data pengajuan penyusun, tahap penyusunan, atau riwayat audit terkait.');
            }
        });
    }

    /**
     * Relasi ke tahap penyusunan (periode memiliki 4 tahap)
     */
    public function tahapPenyusunans(): HasMany
    {
        return $this->hasMany(TahapPenyusunan::class, 'periode_id')->orderBy('tahap');
    }

    /**
     * Relasi ke pengajuan penyusun yang ditempatkan pada periode ini
     */
    public function penyusunApplications(): HasMany
    {
        return $this->hasMany(PenyusunApplication::class, 'periode_id');
    }

    /**
     * Relasi ke riwayat perubahan status / audit trail periode
     */
    public function histories(): HasMany
    {
        return $this->hasMany(PeriodeHistory::class, 'periode_id')->orderByDesc('created_at');
    }

    /**
     * Scope untuk periode yang berstatus draft
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope untuk periode yang berstatus aktif
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope untuk periode yang telah diarsipkan
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Cek apakah periode berstatus draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Cek apakah periode sedang aktif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Cek apakah periode telah diarsipkan
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Cek apakah tanggal selesai periode telah terlewati (berdasarkan waktu Asia/Jakarta)
     */
    public function isExpired(): bool
    {
        if (!$this->tanggal_selesai) {
            return false;
        }

        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
        $tanggalSelesai = \Carbon\Carbon::parse($this->tanggal_selesai)->toDateString();

        return $tanggalSelesai < $today;
    }

    /**
     * Cek apakah periode memenuhi syarat untuk diaktifkan
     */
    public function canBeActivated(): bool
    {
        return !$this->isArchived() && !$this->isExpired();
    }

    /**
     * Helper untuk mengambil satu-satunya periode aktif saat ini
     */
    public static function getActive(): ?self
    {
        return static::active()->first();
    }
}

