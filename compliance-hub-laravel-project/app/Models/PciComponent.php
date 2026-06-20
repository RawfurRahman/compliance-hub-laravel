<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PciComponent extends Model
{
    use HasFactory;
    protected $table = 'pci_components';
    protected $guarded = [];
}
