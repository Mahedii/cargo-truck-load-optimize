<?php

namespace App\Services\Admin\v1\Truck;

use Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Trucks\Trucks;

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
        //     $response = $this->truckInfoAddData($this->request);
        // }
        $response = $this->truckInfoAddData($this->request);

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
    public function truckInfoAddData($request): array
    {
        $slug = $this->generateSlug($request->truck_type);
        $response = Trucks::create([
            'truck_type' => $request->truck_type,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
            'max_weight' => $request->max_weight,
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

        if (Trucks::where('slug',Str::slug($name))->exists()) {

            $original = $slug;

            $count = 1;

            while(Trucks::where('slug',$slug)->exists()) {

                $slug = "{$original}-" . $count++;
            }

            return $slug;

        }
        return $slug;
    }

}
