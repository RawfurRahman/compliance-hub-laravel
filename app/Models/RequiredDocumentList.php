<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequiredDocumentList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_id',
        'source_file_name',
        'source_file_path',
        'imported_by',
    ];

    public function documents()
    {
        return $this->hasMany(RequiredDocument::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
