<?php

namespace App\Services\Admin\v1\Cargo\CargoList;

use Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Cargo\Cargo;
use Illuminate\Http\Request;

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
        //     $response = $this->cargoListAddData($this->request);
        // }
        $response = $this->cargoListAddData($this->request);

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
    public function cargoListAddData($request): array
    {
        $slug = $this->generateSlug($request->name);
        $response = Cargo::create([
            'name' => $request->name,
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

        if (Cargo::where('slug',Str::slug($name))->exists()) {

            $original = $slug;

            $count = 1;

            while(Cargo::where('slug',$slug)->exists()) {

                $slug = "{$original}-" . $count++;
            }

            return $slug;

        }
        return $slug;
    }

}
