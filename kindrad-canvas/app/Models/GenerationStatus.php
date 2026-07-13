<?php

namespace App\Models;

use Database\Factories\GenerationStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenerationStatus extends Model
{
    /** @use HasFactory<GenerationStatusFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];
}
