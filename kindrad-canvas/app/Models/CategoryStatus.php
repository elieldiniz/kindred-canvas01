<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryStatus extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];
}
