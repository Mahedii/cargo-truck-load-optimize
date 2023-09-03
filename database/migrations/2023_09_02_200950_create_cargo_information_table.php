<?php

use App\Models\Cargo\CargoInformation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cargo_information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cargo_id');
            $table->foreign('cargo_id')->references('id')->on('cargos');
            $table->string('box_dimension'); // Store dimensions as a string (e.g., '1*1*1')
            $table->integer('quantity');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        CargoInformation::create([
            'cargo_id' => '1',
            'box_dimension' => '1*2*1',
            'quantity' => '2',
            'slug' => 'box-1',
            'created_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargo_information');
    }
};
