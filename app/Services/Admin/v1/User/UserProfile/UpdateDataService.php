<?php

namespace App\Services\Admin\v1\User\UserProfile;

use Auth;
use File;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UpdateDataService
{
    /**
     * Client form request container
     *
     * @var Request $request
     */
    private Request $request;

    /**
     * Set the request container
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get update data response
     *
     * @return array
     */
    public function getResponse(): array
    {
        $validated = $this->validateData($this->request);

        if ($validated) {
            $response = $this->userProfileUpdateData($this->request);
        }

        return $response;
    }

    /**
     * Validate data
     *
     * @param object $request
     */
    private function validateData($request)
    {
        $validated = $request->validate([
            'name' => 'required|string:max:50',
            'email' => 'required|email',
            'password' => 'string|min:8|max:25|nullable',
            'singleFile' => 'mimes:jpg,png,jpeg,gif,svg|max:5120',
        ], [
            'singleFile.max' => 'Image size cannot exceed :max',
        ]);

        return $validated;
    }

    /**
     * Update selected data
     *
     * @return array
     */
    private function userProfileUpdateData($request): array
    {
        $fileInput = $request->file('singleFile');

        if ($fileInput) {
            //$fileExtension = $fileInput->extension();
            $fileExtension = strtolower($fileInput->getClientOriginalExtension());

            // $fileName = $fileInput->getClientOriginalName();
            $fileName = 'avatar.' . $fileExtension;

            $path = public_path('admin/assets/images/users/' . Auth::user()->id . '/');
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0777, true, true);
            }

            // Image::make($inputFile)->resize(300,200)->save(public_path('admin/assets/images/contact-us/'.Auth::user()->id.'/'.$fileName));
            $request->singleFile->move($path, $fileName);

            $filePath = 'admin/assets/images/users/' . Auth::user()->id . '/' . $fileName;
        } else {
            $imageData = User::where('id', Auth::user()->id)->first();
            $fileName = $imageData->avatar;
        }

        $response = User::where('id', Auth::user()->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request['password']),
            'avatar' => $fileName,
            'updated_at' => Carbon::now()
        ]);

        if ($response) {
            $result = ['status' => 200];
        } else {
            $result = ['status' => 500];
        }

        return $result;
    }
}
