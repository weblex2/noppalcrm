<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelRelationship extends Model
{
    protected $fillable = [
        'source_model',
        'related_model',
        'relationship_type',
        'foreign_key',
        'pivot_table',
        'foreign_pivot_key',
        'related_pivot_key',
        'method_name',
    ];
}
