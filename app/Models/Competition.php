<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'name',
        'location',
        'date',
        'lat',
        'lng',
        'source_url',
        'saved',
    ];
}
