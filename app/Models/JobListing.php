<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
     protected $fillable = [
        'external_id',
        'title',
        'company',
        'city',
        'province',
        'region',
        'lat',
        'lng',
        'raw_location',
    ];
}
