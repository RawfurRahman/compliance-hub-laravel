<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciNetwork extends Model
{
    use HasFactory;
    protected $table = 'pci_networks';
    protected $guarded = [];
}
