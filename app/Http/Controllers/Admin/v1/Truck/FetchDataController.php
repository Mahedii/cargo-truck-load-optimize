<?php

namespace App\Http\Controllers\Admin\v1\Truck;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Admin\v1\Truck\FetchDataService;

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

            return view('admin.v1.truck.index', $fetchedData);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
