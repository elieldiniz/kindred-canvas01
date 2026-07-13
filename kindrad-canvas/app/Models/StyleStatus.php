<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StyleStatus extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];
}
