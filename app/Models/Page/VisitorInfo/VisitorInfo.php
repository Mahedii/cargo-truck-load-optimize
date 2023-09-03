<?php

namespace App\Models\Page\VisitorInfo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip',
        'countryName',
        'countryCode',
        'regionName',
        'regionCode',
        'cityName',
        'zipCode',
        'isoCode',
        'postalCode',
        'latitude',
        'longitude',
        'metroCode',
        'areaCode',
    ];
}
