<?php

namespace App\Http\Controllers\Admin\v1\Cargo\CargoInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\v1\Cargo\CargoInfo\UpdateDataService;

class UpdateDataController extends Controller
{
    private $updateDataService;

    /**
     * update form data
     *
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        try {
            $this->updateDataService = new UpdateDataService($request);
            $response = $this->updateDataService->getResponse();

            if ($response["status"] == 200) {
                return redirect()->route('cargoInfo.load.allData')->with('crudMsg', 'Data Updated Successfully');
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
