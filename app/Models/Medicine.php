<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'name',
        'instructions_html',
        'symptoms',
        'active_ingredients',
        'min_age',
        'pregnancy_safe',
        'country',
        'available_in_ukraine',
    ];

    protected $casts = [
        'active_ingredients'  => 'array',
        'pregnancy_safe'      => 'boolean',
        'available_in_ukraine' => 'boolean',
    ];
}
