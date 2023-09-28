<?php

namespace App\Services\Admin\v1\Cargo\CargoInfo;

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
        $cargoInfoData = CargoInformation::select('cargo_information.*', 'cargos.name as cargo_name')
        ->join('cargos', 'cargos.id', '=', 'cargo_information.cargo_id')
        ->get();


        $componentArray = [];

        $componentArray = [
            "cargoListData" => $cargoListData,
            "cargoInfoData" => $cargoInfoData
        ];

        return $componentArray;
    }
}
