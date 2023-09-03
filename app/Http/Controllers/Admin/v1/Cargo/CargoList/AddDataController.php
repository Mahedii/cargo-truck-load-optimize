<?php

namespace App\Http\Controllers\Admin\v1\Cargo\CargoList;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Admin\v1\Cargo\CargoList\AddDataService;

class AddDataController extends Controller
{
    private $addDataService;

    /**
     * Add form data
     *
     * @param string $functionName
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        try {
            $this->addDataService = new AddDataService($request);
            $response = $this->addDataService->getResponse();

            if ($response["status"] == 200) {
                return redirect()->back()->with('crudMsgSp', 'Data Added Successfully');
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
