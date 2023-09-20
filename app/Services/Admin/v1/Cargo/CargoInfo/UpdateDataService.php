<?php

namespace App\Services\Admin\v1\Cargo\CargoInfo;

use Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Trucks\Trucks;
use App\Models\Cargo\CargoInformation;

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
        // $validated = $this->validateData($this->request);

        // if ($validated) {
        //     $response = $this->cargoInfoUpdateData($this->request);
        // }

        $response = $this->cargoInfoUpdateData($this->request);

        if ($response) {
            $result = ['status' => 200];
        } else {
            $result = ['status' => 500];
        }

        return $result;
    }

    /**
     * Validate data
     *
     * @param object $request
     */
    private function validateData($request)
    {
        $validated = $request->validate([
            'title' => 'required|string:max:50',
            'singleFile' => 'mimes:jpg,png,jpeg,gif,svg|max:5120',
        ], [
            'title.required' => 'Please provide a title',
            'title.string' => 'Title must be type of string',
            'title.max' => 'Title cannot exceed :max characters',
            'singleFile.max' => 'Image size cannot exceed :max',
        ]);

        return $validated;
    }

    /**
     * Update selected data
     *
     * @return array
     */
    private function cargoInfoUpdateData($request): array
    {
        $response = CargoInformation::where('slug', $request->slug)->update([
            'cargo_id' => $request->cargo_id,
            'box_dimension' => $request->box_dimension,
            'quantity' => $request->quantity,
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
