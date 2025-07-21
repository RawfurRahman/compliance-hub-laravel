<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciExternalScan extends Model
{
    use HasFactory;
    protected $table = 'pci_external_scans';
    protected $guarded = [];
}
