<?php

namespace App\Services\Admin\v1\User\UserProfile;

use Auth;
use App\Models\User;

class FetchDataService
{
    /**
     * Get all data from database
     *
     * @return array
     */
    public function getDefaultData(): array
    {
        $userProfileData = User::where('id', Auth::user()->id)->get();

        $componentArray = [];

        $componentArray = [
            "userProfileData" => $userProfileData
        ];

        return $componentArray;
    }
}
