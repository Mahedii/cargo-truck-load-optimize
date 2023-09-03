<?php

namespace App\Models\Cargo;

use App\Models\Cargo\Cargo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CargoInformation extends Model
{
    use HasFactory;

    protected $fillable = ['cargo_id', 'box_dimension', 'quantity', 'slug'];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }
}
