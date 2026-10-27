<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['qr_page_id', 'qr_link_id', 'type', 'visitor_key', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function qrPage(): BelongsTo
    {
        return $this->belongsTo(QrPage::class);
    }

    public function qrLink(): BelongsTo
    {
        return $this->belongsTo(QrLink::class);
    }
}
