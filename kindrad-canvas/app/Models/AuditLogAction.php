<?php

namespace App\Models;

use Database\Factories\AuditLogActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLogAction extends Model
{
    /** @use HasFactory<AuditLogActionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];
}
