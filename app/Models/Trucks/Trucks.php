<?php

namespace App\Models\Trucks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trucks extends Model
{
    use HasFactory;

    protected $fillable = ['truck_type', 'max_weight', 'length', 'width', 'height', 'slug'];
}
