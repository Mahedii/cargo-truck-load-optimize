<?php

use App\Models\Trucks\Trucks;
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
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('truck_type');
            $table->decimal('max_weight', 8, 2);
            $table->decimal('length', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Trucks::create([
            'truck_type' => '1 Ton Side grill',
            'max_weight' => '1',
            'length' => '2',
            'width' => '1.1',
            'height' => '1',
            'slug' => '1-ton-side-grill',
            'created_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
