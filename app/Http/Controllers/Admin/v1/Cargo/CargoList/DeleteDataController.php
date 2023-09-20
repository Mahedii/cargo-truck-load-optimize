<?php

namespace App\Http\Controllers\Admin\v1\Cargo\CargoList;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\v1\Cargo\CargoList\DeleteDataService;

class DeleteDataController extends Controller
{
    private $deleteDataService;

    /**
     * Fetch expected data
     *
     * @param string $slug
     */
    public function __invoke($slug)
    {
        try {
            $this->deleteDataService = new DeleteDataService($slug);
            $response = $this->deleteDataService->getResponse();
            if ($response["status"] == 200) {
                return redirect()->back()->with('crudMsg', 'Data Deleted Successfully');
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
