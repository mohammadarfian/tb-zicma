<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Initialize
    protected $fillable = [
        'kode_barang', 'kategori', 'nama_barang', 'satuan', 'merek', 'stok', 'harga', 'keterangan',
    ];
}