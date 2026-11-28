<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $penyusun_application_id
 * @property int $mata_kuliah_id
 * @property int $tahap_id
 * @property int $nomor_modul
 * @property string|null $judul_modul
 * @property string|null $deskripsi_modul
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string $status
 * @property string|null $catatan_admin
 * @property \Illuminate\Support\Carbon|null $uploaded_at
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property int|null $validated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Modul extends Model
{
    protected $fillable = [
        'penyusun_application_id',
        'mata_kuliah_id',
        'tahap_id',
        'nomor_modul',
        'judul_modul',
        'deskripsi_modul',
        'file_path',
        'file_name',
        'status',
        'catatan_admin',
        'uploaded_at',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'nomor_modul' => 'integer',
        'uploaded_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function penyusunApplication(): BelongsTo
    {
        return $this->belongsTo(PenyusunApplication::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function tahapPenyusunan(): BelongsTo
    {
        return $this->belongsTo(TahapPenyusunan::class, 'tahap_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function finalDrafts(): HasMany
    {
        return $this->hasMany(FinalDraft::class, 'penyusun_application_id', 'penyusun_application_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
