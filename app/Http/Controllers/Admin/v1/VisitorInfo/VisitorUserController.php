<?php

namespace App\Http\Controllers\Admin\v1\VisitorInfo;

use App\Http\Controllers\Controller;
use App\Models\Page\VisitorInfo\VisitorInfo;

class VisitorUserController extends Controller
{
    /**
     * Update selected data
     *
     * @return array
     */
    public function index()
    {
        $visitorInfo = VisitorInfo::all();

        return view('admin.v1.visitor-user-info.show-list', compact('visitorInfo'));
    }
}
