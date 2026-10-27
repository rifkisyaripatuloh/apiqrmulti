<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrPage extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = ['user_id', 'code', 'title', 'description', 'status'];

    protected $casts = ['total_scans' => 'integer', 'total_clicks' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(QrLink::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QrEvent::class);
    }
}
