<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visitor_infos', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable(true);
            $table->string('countryName')->nullable(true);
            $table->string('countryCode')->nullable(true);
            $table->string('regionName')->nullable(true);
            $table->string('regionCode')->nullable(true);
            $table->string('cityName')->nullable(true);
            $table->string('zipCode')->nullable(true);
            $table->string('isoCode')->nullable(true);
            $table->string('postalCode')->nullable(true);
            $table->string('latitude')->nullable(true);
            $table->string('longitude')->nullable(true);
            $table->string('metroCode')->nullable(true);
            $table->string('areaCode')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visitor_infos');
    }
};
