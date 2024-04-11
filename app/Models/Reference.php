<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reference
 *
 * @property int         $id
 * @property string      $code
 * @property string      $operation_id
 * @property User        $user
 * @property Transaction $transaction
 *
 * @method   int    getKey()
 */
class Reference extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'operation_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
