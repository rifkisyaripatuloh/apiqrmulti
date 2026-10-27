<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrLink extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = ['qr_page_id', 'token', 'label', 'icon_key', 'destination_url', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'total_clicks' => 'integer'];

    public function qrPage(): BelongsTo
    {
        return $this->belongsTo(QrPage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QrEvent::class);
    }
}
