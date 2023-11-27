<?php

namespace App\Http\Controllers\Admin\v1\Cargo\CargoList;

use Exception;
use App\Models\Cargo\Cargo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Admin\v1\Cargo\CargoList\FetchDataService;

class FetchDataController extends Controller
{
    private $fetchDataService;

    /**
     * Fetch expected data
     *
     */
    public function index()
    {
        try {
            // $this->fetchDataService = new FetchDataService();
            // $fetchedData = $this->fetchDataService->getDefaultData();
            $fetchedData = $this->getDefaultData();

            return view('admin.v1.cargo.cargo-list.index', $fetchedData);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Get all data from database
     *
     * @return array
     */
    private function getDefaultData(): array
    {
        $cargoListData = Cargo::select("*")->get();

        $componentArray = [];

        $componentArray = [
            "cargoListData" => $cargoListData
        ];

        return $componentArray;
    }
}
