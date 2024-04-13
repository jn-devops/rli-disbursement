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
 * @property array       $inputs
 * @property array       $request
 * @property array       $response
 * @property array       $status
 *
 * @method   int    getKey()
 */
class Reference extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'operation_id', 'inputs', 'request', 'response', 'status'];

    protected $casts = [
        'inputs' => 'array',
        'request' => 'array',
        'response' => 'array',
        'status' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
