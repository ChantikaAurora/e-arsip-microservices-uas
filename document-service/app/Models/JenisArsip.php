<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisArsip extends Model
{
    protected $table = 'jenis_arsips';

    protected $fillable = [
        'jenis',
        'keterangan',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'jenis_id');
    }
}
