<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'nomor_surat',
        'kode_klasifikasi',
        'tanggal_surat',
        'tanggal_terima',
        'asal_surat',
        'tujuan_surat',
        'pengirim',
        'penerima',
        'perihal',
        'lampiran',
        'jenis',
        'keterangan',
        'file',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_terima' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the jenis arsip that owns the document.
     */
    public function jenisArsip(): BelongsTo
    {
        return $this->belongsTo(JenisArsip::class, 'jenis', 'id');
    }

    /**
     * Get the user who created this document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Scope a query to only include surat masuk.
     */
    public function scopeMasuk($query)
    {
        return $query->where('type', 'masuk');
    }

    /**
     * Scope a query to only include surat keluar.
     */
    public function scopeKeluar($query)
    {
        return $query->where('type', 'keluar');
    }

    /**
     * Scope a query to search documents.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                  ->orWhere('perihal', 'like', "%{$search}%")
                  ->orWhere('asal_surat', 'like', "%{$search}%")
                  ->orWhere('tujuan_surat', 'like', "%{$search}%")
                  ->orWhere('pengirim', 'like', "%{$search}%")
                  ->orWhere('penerima', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, ?string $type)
    {
        if ($type && in_array($type, ['masuk', 'keluar'])) {
            return $query->where('type', $type);
        }
        return $query;
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateBetween($query, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->where('tanggal_surat', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('tanggal_surat', '<=', $endDate);
        }
        return $query;
    }
}
