<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\JenisArsip;

class Document extends Model
{
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
        'file',
        'jenis_id',
        'created_by',
    ];

    public function jenis()
    {
        return $this->belongsTo(JenisArsip::class, 'jenis_id');
    }
}
