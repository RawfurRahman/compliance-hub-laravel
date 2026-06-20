<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequiredDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'required_document_list_id',
        'document_name',
        'category',
        'reference',
        'description',
        'sort_order',
    ];

    public function documentList()
    {
        return $this->belongsTo(RequiredDocumentList::class, 'required_document_list_id');
    }
}
