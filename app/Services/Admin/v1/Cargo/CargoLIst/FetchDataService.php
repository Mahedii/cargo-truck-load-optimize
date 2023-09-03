<?php

namespace App\Services\Admin\v1\Cargo\CargoList;

use App\Models\Cargo\Cargo;
use App\Models\Cargo\CargoInformation;

class FetchDataService
{
    /**
     * Get all data from database
     *
     * @return array
     */
    public function getDefaultData(): array
    {
        $cargoListData = Cargo::select("*")->get();

        $componentArray = [];

        $componentArray = [
            "cargoListData" => $cargoListData
        ];

        return $componentArray;
    }
}
