<?php

namespace App\Services\Admin\v1\Cargo\CargoInfo;

use Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Trucks\Trucks;
use App\Models\Cargo\CargoInformation;

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
    public function cargoInfoAddData($request): array
    {
        $slug = $this->generateSlug("cargo-" . $request->cargo_id . "box");
        $box_dimension = $request->length . "*" . $request->width . "*" . $request->height;
        $response = CargoInformation::create([
            'cargo_id' => $request->cargo_id,
            'box_dimension' => $box_dimension,
            'quantity' => $request->quantity,
            'slug' => $slug,
            'creator' => Auth::user()->id
        ]);

        if($response) {
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
        $slug=Str::slug($name);

        if (CargoInformation::where('slug',Str::slug($name))->exists()) {

            $original = $slug;

            $count = 1;

            while(CargoInformation::where('slug',$slug)->exists()) {

                $slug = "{$original}-" . $count++;
            }

            return $slug;

        }
        return $slug;
    }

}
