<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciInternalScan extends Model
{
    use HasFactory;
    protected $table = 'pci_internal_scans';
    protected $guarded = [];
}
