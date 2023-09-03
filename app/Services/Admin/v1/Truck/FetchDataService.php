<?php

namespace App\Services\Admin\v1\Truck;

use App\Models\Trucks\Trucks;

class FetchDataService
{
    /**
     * Get all data from database
     *
     * @return array
     */
    public function getDefaultData(): array
    {
        $trucksData = Trucks::select("*")->get();

        $componentArray = [];

        $componentArray = [
            "trucksData" => $trucksData
        ];

        return $componentArray;
    }
}
