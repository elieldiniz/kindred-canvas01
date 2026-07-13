<?php

namespace App\Models;

use Database\Factories\CreditTransactionReasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditTransactionReason extends Model
{
    /** @use HasFactory<CreditTransactionReasonFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'expected_sign',
    ];
}
