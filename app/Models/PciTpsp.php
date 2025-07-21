<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciTpsp extends Model
{
    use HasFactory;
    protected $table = 'pci_tpsps';
    protected $guarded = [];
}
