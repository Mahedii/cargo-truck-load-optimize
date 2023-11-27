<?php

namespace App\Services\Admin\v1\Cargo\CargoInfo;

use Auth;
// use Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Cargo\CargoInformation;
use Illuminate\Support\Facades\Validator;

class AddDataService
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
     * Get response for adding data
     *
     * @return array
     */
    public function getResponse(): array
    {
        // $validated = $this->validateData($this->request);

        // if ($validated) {
        //     $response = $this->cargoInfoAddData($this->request);
        // }
        $response = $this->cargoInfoAddData($this->request);

        if ($response) {
            $result = ['status' => 200];
        } else {
            $result = ['status' => 500];
        }

        return $result;
    }

    /**
     * add data to database
     *
     * @return array
     */
    public function cargoInfoAddData($request): mixed
    {
        // $request->validate([
        //     'cargo_id.*' => 'required',
        //     'length.*' => 'required',
        //     'width.*' => 'required',
        //     'height.*' => 'required',
        //     'quantity.*' => 'required',
        // ]);

        // $rules = [
        //     'cargo_id.*' => 'required',
        //     'length.*' => 'sometimes|required',
        //     'width.*' => 'sometimes|required',
        //     'height.*' => 'sometimes|required',
        //     'quantity.*' => 'sometimes|required',
        // ];

        // // Loop through the submitted data and dynamically add rules for each field
        // foreach ($request->input('cargo_id') as $key => $cargoId) {
        //     $rules["length.$key"] = Rule::requiredIf($request->filled("cargo_id.$key"));
        //     $rules["width.$key"] = Rule::requiredIf($request->filled("cargo_id.$key"));
        //     $rules["height.$key"] = Rule::requiredIf($request->filled("cargo_id.$key"));
        //     $rules["quantity.$key"] = Rule::requiredIf($request->filled("cargo_id.$key"));
        // }

        // // Validate the request data
        // $request->validate($rules);


        $validationRules = [];

        foreach ($request->input('cargo_id') as $key => $cargoId) {
            if ($request->input("cargo_id.$key") != null || $request->input("length.$key") != null || $request->input("width.$key") != null || $request->input("height.$key") != null || $request->input("quantity.$key") != null) {
                $validationRules["cargo_id.$key"] = ['required'];
                $validationRules["length.$key"] = ['required'];
                $validationRules["width.$key"] = ['required'];
                $validationRules["height.$key"] = ['required'];
                $validationRules["quantity.$key"] = ['required'];
            }
        }

        $customMessages = [
            'cargo_id.*.required' => 'The cargo id field is required.',
            'length.*.required' => 'The length field is required.',
            'width.*.required' => 'The width field is required.',
            'height.*.required' => 'The height field is required.',
            'quantity.*.required' => 'The quantity field is required.',
        ];

        // $request->validate($validationRules);
        // $validator = Validator::make($request->all(), $validationRules);
        $validator = Validator::make($request->all(), $validationRules, $customMessages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        dd("gg");

        // Loop through the submitted data and store it in the database
        foreach ($request->input('cargo_id') as $key => $cargoId) {
            $slug = $this->generateSlug("cargo-" . $cargoId . "box");
            $box_dimension = $request->input("length.$key") . "*" . $request->input("width.$key") . "*" . $request->input("height.$key");
            if ($cargoId != null) {
                $response = CargoInformation::create([
                    'cargo_id' => $cargoId,
                    'box_dimension' => $box_dimension,
                    'quantity' => $request->input("quantity.$key"),
                    'slug' => $slug,
                    'creator' => Auth::user()->id
                ]);
            }
        }


        // $slug = $this->generateSlug("cargo-" . $request->cargo_id . "box");
        // $box_dimension = $request->length . "*" . $request->width . "*" . $request->height;
        // $response = CargoInformation::create([
        //     'cargo_id' => $request->cargo_id,
        //     'box_dimension' => $box_dimension,
        //     'quantity' => $request->quantity,
        //     'slug' => $slug,
        //     'creator' => Auth::user()->id
        // ]);

        if ($response) {
            $result = ['status' => 200];
        } else {
            $result = ['status' => 500];
        }

        return $result;
    }

    /**
     * generate slug
     *
     * @return string
     */
    public static function generateSlug($name): string
    {
        $slug = Str::slug($name);

        if (CargoInformation::where('slug', Str::slug($name))->exists()) {
            $original = $slug;

            $count = 1;

            while (CargoInformation::where('slug', $slug)->exists()) {
                $slug = "{$original}-" . $count++;
            }

            return $slug;
        }
        return $slug;
    }
}
