<?php

namespace App\Domain\Anomaly;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'anomaly_id',
        'user_id',
        'comment',
    ];

    public function anomaly(): BelongsTo
    {
        return $this->belongsTo(Anomaly::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
