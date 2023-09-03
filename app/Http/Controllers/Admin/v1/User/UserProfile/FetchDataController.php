<?php

namespace App\Http\Controllers\Admin\v1\User\UserProfile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\v1\User\UserProfile\FetchDataService;

class FetchDataController extends Controller
{
    /**
     * Fetch all data
     *
     */
    public function index()
    {
        try {
            $this->fetchDataService = new FetchDataService();
            $fetchedData = $this->fetchDataService->getDefaultData();

            return view('admin.v1.user-profile.index', $fetchedData);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
