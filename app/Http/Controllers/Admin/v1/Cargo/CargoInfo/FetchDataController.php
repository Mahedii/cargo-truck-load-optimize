<?php

namespace App\Http\Controllers\Admin\v1\Cargo\CargoInfo;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cargo\CargoInformation;
use App\Services\Admin\v1\Cargo\CargoInfo\FetchDataService;

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
            $this->fetchDataService = new FetchDataService();
            $fetchedData = $this->fetchDataService->getDefaultData();

            return view('admin.v1.cargo.cargo-info.index', $fetchedData);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Fetch selected data
     *
     */
    public function fetchData($slug)
    {
        // dd($cargo_id);
        // return response()->json(['status' => 200]);
        try {
            $fetchedData = CargoInformation::where('slug', $slug)->get();
            $count = CargoInformation::where('slug', $slug)->count();
            $result = [
                'status' => 200,
                'count' => $count,
                'fetchedData' => $fetchedData,
            ];
            return response()->json($result);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
