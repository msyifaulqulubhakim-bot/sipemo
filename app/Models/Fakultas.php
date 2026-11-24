<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fakultas extends Model
{
    use HasFactory;

    protected $table = 'fakultas';

    protected $fillable = [
        'kode_fakultas',
        'nama_fakultas',
        'nama_singkat',
    ];

    protected $casts = [
        'kode_fakultas' => 'integer',
    ];

    public function jurusans(): HasMany
    {
        return $this->hasMany(Jurusan::class, 'fakultas_id');
    }
}
