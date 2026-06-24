<?php

namespace App\Modules\RiskManagement\Models;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiskAttachment extends Model
{
    protected $table = 'risk_attachments';
    protected $fillable = [
        'risk_register_id', 'evidence_id', 'filename', 'disk',
        'path', 'mime_type', 'file_size', 'attachment_type', 'notes', 'uploaded_by',
    ];
    protected $casts = ['file_size' => 'integer'];

    public function risk() { return $this->belongsTo(RiskRegister::class, 'risk_register_id'); }
    public function evidence() { return $this->belongsTo(Evidence::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
    }
}
