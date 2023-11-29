<?php

namespace App\Http\Controllers\Admin\v1\Cargo\DistributeCargo;

use Exception;
use App\Models\Cargo\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Trucks\Trucks;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Cargo\CargoInformation;
use App\Services\Admin\v1\Cargo\DistributeCargo\FetchDataService;

class FetchDataController extends Controller
{
    private $fetchDataService;
    private $totalBoxVolumeWithoutHeight;
    private array $consolidatedCargo;
    private array $remainingCargo;
    private array $truckCargoInfoAfterLoad;
    private array $truckBoxContainCapacity;
    private $cargoInfo;

    /**
     * Fetch expected data
     *
     */
    public function index()
    {
        try {
            $this->fetchDataService = new FetchDataService();
            $fetchedData = $this->fetchDataService->getDefaultData();

            return view('admin.v1.cargo.distribute-cargo.index', $fetchedData);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Fetch optimized data
     *
     */
    public function getOptimizedData1(Request $request)
    {
        $cargo_id = $request->cargo_id;
        // dd($cargo_id);
        // return response()->json(['status' => 200]);
        // Retrieve cargo information
        $cargo = Cargo::find($cargo_id);
        // $cargoInfo = $cargo->CargoInformation;
        $cargoInfo = CargoInformation::where('cargo_id', $cargo_id)->get()->toArray();
        // dd($cargoInfo);

        // Retrieve available trucks
        $trucks = Trucks::all();

        // Initialize variables
        $this->consolidatedCargo = [];
        $this->remainingCargo = [];
        $this->truckCargoInfoAfterLoad = [];
        $this->truckBoxContainCapacity = [];

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        usort($cargoInfo, function ($a, $b) {
            $dimA = explode('*', $a['box_dimension']);
            $dimB = explode('*', $b['box_dimension']);
            $volA = $dimA[0] * $dimA[1] * $dimA[2];
            $volB = $dimB[0] * $dimB[1] * $dimB[2];
            if ($volA === $volB) {
                return $b['quantity'] - $a['quantity'];
            }
            return $volB - $volA;
        });

        // Call the function for the initial cargo
        $this->assignBoxesToTrucks($cargoInfo, $trucks);

        // dump($this->remainingCargo);

        // If there's remaining cargo, call the function again
        while (!empty($this->remainingCargo)) {
            dd("Remaining cargo not empty!");
            $this->assignBoxesToTrucks($this->remainingCargo, $trucks);
        }

        $result = [
            'status' => 200,
            'consolidatedCargo' => $this->consolidatedCargo,
        ];

        // dump($result);

        // dd("finish for now");

        dd($result);

        // $this->consolidatedCargo now contains cargo boxes, their corresponding trucks, and quantities
        // $this->remainingCargo has been assigned to other available trucks

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
    }

    private function assignBoxesToTrucks1($cargoInfo, $trucks)
    {
        // Iterate through each cargo box
        foreach ($cargoInfo as $box) {
            $this->truckBoxContainCapacity = [];
            // dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxQuantity = $box['quantity'];
            // dump($boxVolume);

            // Initialize variables for the best-fitting truck and maximum quantity of boxes to load
            $bestFittingTruck = null;
            $maxBoxesToLoad = 0;

            // dump($this->truckCargoInfoAfterLoad);

            if (isset($this->truckCargoInfoAfterLoad["remaining_space_on_truck"])) {
                if ($boxVolume <= $this->truckCargoInfoAfterLoad["remaining_space_on_truck"]) {
                    $truckVolume = $this->truckCargoInfoAfterLoad["remaining_space_on_truck"];

                    // Calculate how many boxes can fit in the truck, considering quantity
                    $maxBoxes = floor($truckVolume / $boxVolume);
                    // dump($maxBoxes);

                    $this->truckBoxContainCapacity[] = [
                        "truck_type" => $this->truckCargoInfoAfterLoad["truck_type"],
                        "truck_volume" => $truckVolume,
                        "box_contain_capacity" => $maxBoxes,
                    ];
                }
            } else {
                foreach ($trucks as $truck) {
                    $truckVolume = $truck->length * $truck->width * $truck->height;
                    $truck_dimension = $truck->length . "*" . $truck->width . "*" . $truck->height;
                    // dump($truck->truck_type);
                    // dump($truckVolume);

                    // Calculate how many boxes can fit in the truck, considering quantity
                    $maxBoxes = floor($truckVolume / $boxVolume);
                    // dump($maxBoxes);

                    $this->truckBoxContainCapacity[] = [
                        "truck_type" => $truck->truck_type,
                        "truck_dimension" => $truck_dimension,
                        "truck_volume" => $truckVolume,
                        "box_contain_capacity" => $maxBoxes,
                    ];
                }
            }
            // dump($this->truckBoxContainCapacity);

            $minDiff = PHP_INT_MAX;
            $maxDiff = PHP_INT_MAX;
            $closestMin = null;
            $closestMax = null;

            foreach ($this->truckBoxContainCapacity as $item) {
                $capacity = $item["box_contain_capacity"];

                if ($capacity <= $boxQuantity) {
                    $minDiffCurrent = $boxQuantity - $capacity;
                    if ($minDiffCurrent < $minDiff) {
                        $minDiff = $minDiffCurrent;
                        $closestMin = $capacity;
                        $minValueTruckType = $item["truck_type"];
                        $minValueTruckDimension = $item["truck_dimension"];
                        $minValueTruckVolume = $item["truck_volume"];
                        $minValueBoxContainCapacity = $item["box_contain_capacity"];
                    }
                }

                if ($capacity >= $boxQuantity) {
                    $maxDiffCurrent = $capacity - $boxQuantity;
                    if ($maxDiffCurrent < $maxDiff) {
                        $maxDiff = $maxDiffCurrent;
                        $closestMax = $capacity;
                        $maxValueTruckType = $item["truck_type"];
                        $maxValueTruckDimension = $item["truck_dimension"];
                        $maxValueTruckVolume = $item["truck_volume"];
                        $maxValueBoxContainCapacity = $item["box_contain_capacity"];
                    }
                }
            }

            // dump($closestMin);
            // dump($closestMax);
            // // dump($maxValueBoxContainCapacity);
            // dd("");

            if (!empty($closestMin) && empty($closestMax)) {
                $boxContainCapacity = $minValueBoxContainCapacity;
                $truckType = $minValueTruckType;
                $truck_dimension = $minValueTruckDimension;
                $truck_volume = $minValueTruckVolume;
                // $remainingSpaceOnTruck = "";
                // $totalLoadedBoxVolume = $boxVolume * $minValueBoxContainCapacity;
                $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
                $loadedBoxQuantity = $boxContainCapacity;
            } elseif ((empty($closestMin) && !empty($closestMax))) {
                $boxContainCapacity = $maxValueBoxContainCapacity;
                $truckType = $maxValueTruckType;
                $truck_dimension = $maxValueTruckDimension;
                $truck_volume = $maxValueTruckVolume;
                // $totalLoadedBoxVolume = $boxVolume * $maxValueBoxContainCapacity;
                $remainingboxQuantity = 0;
                $loadedBoxQuantity = $boxQuantity;
            } elseif (!empty($closestMin) && !empty($closestMax)) {
                if (($boxQuantity - $closestMin) <= ($closestMax - $boxQuantity)) {
                    $boxContainCapacity = $minValueBoxContainCapacity;
                    $truckType = $minValueTruckType;
                    $truck_dimension = $minValueTruckDimension;
                    $truck_volume = $minValueTruckVolume;
                    $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
                    $loadedBoxQuantity = $boxContainCapacity;
                } elseif (($closestMax - $boxQuantity) <= ($boxQuantity - $closestMin)) {
                    $boxContainCapacity = $maxValueBoxContainCapacity;
                    $truckType = $maxValueTruckType;
                    $truck_dimension = $maxValueTruckDimension;
                    $truck_volume = $maxValueTruckVolume;
                    $remainingboxQuantity = 0;
                    $loadedBoxQuantity = $boxQuantity;
                }
            }
            // dd("");

            $bestFittingTruck = $truckType;
            $maxBoxesToLoad = $boxContainCapacity;
            // dump($bestFittingTruck);
            // dump($maxBoxesToLoad);
            // dump($remainingboxQuantity);
            // // dump($remainingSpaceOnTruck);
            // dd("");

            // If a fitting truck is found, add the boxes to it; otherwise, save the boxes for later
            if ($bestFittingTruck) {
                $this->consolidatedCargo[] = [
                    'truck_type' => $bestFittingTruck,
                    'truck_dimension' => $truck_dimension,
                    'truck_volume' => $truck_volume,
                    'can_load_max_box_quantity' => $maxBoxesToLoad,
                    'box_dimension' => $box['box_dimension'],
                    'single_box_volume' => $boxVolume,
                    'total_box_quantity' => $boxQuantity,
                    'loaded_box_quantity' => $loadedBoxQuantity,
                    'remaining_box_quantity' => $remainingboxQuantity,
                    'loaded_box_volume' => $boxVolume * $loadedBoxQuantity,
                    'remaining_space_on_truck' => $truck_volume - ($boxVolume * $loadedBoxQuantity),
                ];

                if ($remainingboxQuantity >= 0) {
                    // Reduce the box quantity by the loaded quantity
                    $box['quantity'] -= $loadedBoxQuantity;
                }

                // dd($box['quantity']);

                // If there are remaining boxes of this type, save them for later
                if ($box['quantity'] > 0) {
                    $this->remainingCargo = [];
                    $this->remainingCargo[] = $box;
                    $this->truckCargoInfoAfterLoad = [
                        "box_dimension" => $box['box_dimension'],
                        "box_dimension_volume" => $boxVolume,
                        "quantity" => $box['quantity'],
                        "truck_type" => $bestFittingTruck,
                        "remaining_space_on_truck" => $truck_volume - ($boxVolume * $loadedBoxQuantity),
                    ];

                    // If there's remaining cargo, call the function again
                    while (!empty($this->remainingCargo)) {
                        // dd("Remaining cargo not empty!");
                        $this->assignRemainingCargoBoxesToTrucks($this->remainingCargo, $trucks);
                    }
                } else {
                    $this->remainingCargo = [];
                    $this->truckCargoInfoAfterLoad = [];
                }
            } else {
                $this->remainingCargo[] = [
                    'box_dimension' => $box['box_dimension'],
                    'quantity' => $boxQuantity,
                ];
            }
            // dump($this->consolidatedCargo);
            // dump($this->remainingCargo);
        }
    }

    private function assignRemainingCargoBoxesToTrucks1($cargoInfo, $trucks)
    {
        // Iterate through each cargo box
        foreach ($cargoInfo as $box) {
            $this->truckBoxContainCapacity = [];
            // dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxQuantity = $box['quantity'];
            // dump($boxVolume);

            // Initialize variables for the best-fitting truck and maximum quantity of boxes to load
            $bestFittingTruck = null;
            $maxBoxesToLoad = 0;

            // dump($this->truckCargoInfoAfterLoad);

            if ($boxVolume <= $this->truckCargoInfoAfterLoad["remaining_space_on_truck"]) {
                $truckVolume = $this->truckCargoInfoAfterLoad["remaining_space_on_truck"];

                // Calculate how many boxes can fit in the truck, considering quantity
                $maxBoxes = floor($truckVolume / $boxVolume);
                // dump($maxBoxes);

                $this->truckBoxContainCapacity[] = [
                    "truck_type" => $this->truckCargoInfoAfterLoad["truck_type"],
                    "truck_volume" => $truckVolume,
                    "box_contain_capacity" => $maxBoxes,
                ];
            } else {
                foreach ($trucks as $truck) {
                    $truckVolume = $truck->length * $truck->width * $truck->height;
                    $truck_dimension = $truck->length . "*" . $truck->width . "*" . $truck->height;
                    // dump($truck->truck_type);
                    // dump($truckVolume);

                    // Calculate how many boxes can fit in the truck, considering quantity
                    $maxBoxes = floor($truckVolume / $boxVolume);
                    // dump($maxBoxes);

                    $this->truckBoxContainCapacity[] = [
                        "truck_type" => $truck->truck_type,
                        "truck_dimension" => $truck_dimension,
                        "truck_volume" => $truckVolume,
                        "box_contain_capacity" => $maxBoxes,
                    ];
                }
            }

            // dump($this->truckBoxContainCapacity);

            $minDiff = PHP_INT_MAX;
            $maxDiff = PHP_INT_MAX;
            $closestMin = null;
            $closestMax = null;

            foreach ($this->truckBoxContainCapacity as $item) {
                $capacity = $item["box_contain_capacity"];

                if ($capacity <= $boxQuantity) {
                    $minDiffCurrent = $boxQuantity - $capacity;
                    if ($minDiffCurrent < $minDiff) {
                        $minDiff = $minDiffCurrent;
                        $closestMin = $capacity;
                        $minValueTruckType = $item["truck_type"];
                        $minValueTruckDimension = $item["truck_dimension"];
                        $minValueTruckVolume = $item["truck_volume"];
                        $minValueBoxContainCapacity = $item["box_contain_capacity"];
                    }
                }

                if ($capacity >= $boxQuantity) {
                    $maxDiffCurrent = $capacity - $boxQuantity;
                    if ($maxDiffCurrent < $maxDiff) {
                        $maxDiff = $maxDiffCurrent;
                        $closestMax = $capacity;
                        $maxValueTruckType = $item["truck_type"];
                        $maxValueTruckDimension = $item["truck_dimension"];
                        $maxValueTruckVolume = $item["truck_volume"];
                        $maxValueBoxContainCapacity = $item["box_contain_capacity"];
                    }
                }
            }

            // dump($closestMin);
            // dump($closestMax);

            if (!empty($closestMin) && empty($closestMax)) {
                $boxContainCapacity = $minValueBoxContainCapacity;
                $truckType = $minValueTruckType;
                $truck_dimension = $minValueTruckDimension;
                $truck_volume = $minValueTruckVolume;
                $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
                $loadedBoxQuantity = $boxContainCapacity;
            } elseif ((empty($closestMin) && !empty($closestMax))) {
                $boxContainCapacity = $maxValueBoxContainCapacity;
                $truckType = $maxValueTruckType;
                $truck_dimension = $maxValueTruckDimension;
                $truck_volume = $maxValueTruckVolume;
                $remainingboxQuantity = 0;
                $loadedBoxQuantity = $boxQuantity;
            } elseif (!empty($closestMin) && !empty($closestMax)) {
                if (($boxQuantity - $closestMin) <= ($closestMax - $boxQuantity)) {
                    $boxContainCapacity = $minValueBoxContainCapacity;
                    $truckType = $minValueTruckType;
                    $truck_dimension = $minValueTruckDimension;
                    $truck_volume = $minValueTruckVolume;
                    $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
                    $loadedBoxQuantity = $boxContainCapacity;
                } elseif (($closestMax - $boxQuantity) <= ($boxQuantity - $closestMin)) {
                    $boxContainCapacity = $maxValueBoxContainCapacity;
                    $truckType = $maxValueTruckType;
                    $truck_dimension = $maxValueTruckDimension;
                    $truck_volume = $maxValueTruckVolume;
                    $remainingboxQuantity = 0;
                    $loadedBoxQuantity = $boxQuantity;
                }
            }
            // dd("");

            $bestFittingTruck = $truckType;
            $maxBoxesToLoad = $boxContainCapacity;
            // dump($bestFittingTruck);
            // dump($maxBoxesToLoad);
            // dump($remainingboxQuantity);
            // // dump($remainingSpaceOnTruck);
            // dd("");

            // If a fitting truck is found, add the boxes to it; otherwise, save the boxes for later
            if ($bestFittingTruck) {
                $this->consolidatedCargo[] = [
                    'truck_type' => $bestFittingTruck,
                    'truck_dimension' => $truck_dimension,
                    'truck_volume' => $truck_volume,
                    'can_load_max_box_quantity' => $maxBoxesToLoad,
                    'box_dimension' => $box['box_dimension'],
                    'single_box_volume' => $boxVolume,
                    'total_box_quantity' => $boxQuantity,
                    'loaded_box_quantity' => $loadedBoxQuantity,
                    'remaining_box_quantity' => $remainingboxQuantity,
                    'loaded_box_volume' => $boxVolume * $loadedBoxQuantity,
                    'remaining_space_on_truck' => $truck_volume - ($boxVolume * $loadedBoxQuantity),
                ];

                if ($remainingboxQuantity >= 0) {
                    // Reduce the box quantity by the loaded quantity
                    $box['quantity'] -= $loadedBoxQuantity;
                }

                // dd($box['quantity']);

                // If there are remaining boxes of this type, save them for later
                if ($box['quantity'] > 0) {
                    $this->remainingCargo = [];
                    $this->remainingCargo[] = $box;
                    $this->truckCargoInfoAfterLoad = [
                        "box_dimension" => $box['box_dimension'],
                        "box_dimension_volume" => $boxVolume,
                        "quantity" => $box['quantity'],
                        "truck_type" => $bestFittingTruck,
                        "remaining_space_on_truck" => $truck_volume - ($boxVolume * $loadedBoxQuantity),
                    ];
                } else {
                    $this->remainingCargo = [];
                    $this->truckCargoInfoAfterLoad = [
                        "box_dimension" => "",
                        "box_dimension_volume" => "",
                        "quantity" => "",
                        "truck_type" => $bestFittingTruck,
                        "remaining_space_on_truck" => $truck_volume - ($boxVolume * $loadedBoxQuantity),
                    ];
                }
            } else {
                $this->remainingCargo[] = [
                    'box_dimension' => $box['box_dimension'],
                    'quantity' => $boxQuantity,
                ];
            }
            // dump($this->consolidatedCargo);
            // dump($this->remainingCargo);
        }
    }

    public function getData(Request $request)
    {
        $cargo_id = $request->cargo_id;
        // dd($cargo_id);
        // Retrieve cargo information
        $cargo = Cargo::find($cargo_id);
        // $this->cargoInfo = $cargo->CargoInformation;
        $this->cargoInfo = CargoInformation::where('cargo_id', $cargo_id)->get()->toArray();
        // dd($this->cargoInfo);

        // Retrieve available trucks
        // $trucks = Trucks::select("*")->get()->toArray();
        $trucks = Trucks::select("*")->get();

        $uniqueTrucks = collect();

        // Create an array to keep track of the calculated values.
        $calculatedValues = [];

        foreach ($trucks as $truck) {
            $width = $truck->width;
            $length = $truck->length;
            $calculatedValue = $width * $length;

            // Check if the calculated value is already in the array.
            if (!in_array($calculatedValue, $calculatedValues)) {
                // If it's not in the array, add it and add the truck to the uniqueTrucks collection.
                $calculatedValues[] = $calculatedValue;
                $uniqueTrucks->push($truck);
            }
        }

        // $uniqueTrucks now contains unique trucks based on the width * length value.
        // dd($uniqueTrucks);
        $uniqueTrucksArray = $uniqueTrucks->toArray();

        // Initialize variables
        $this->consolidatedCargo = [];
        $this->remainingCargo = [];
        $this->truckCargoInfoAfterLoad = [];
        $this->truckBoxContainCapacity = [];
        // dump($this->cargoInfo);

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        $dimensions = [];
        $quantities = [];

        foreach ($this->cargoInfo as $key => $cargo) {
            $dim = explode('*', $cargo['box_dimension']);
            $volume = $dim[0] * $dim[1];
            $dimensions[$key] = $volume;
            $quantities[$key] = $cargo['quantity'];
        }
        array_multisort($dimensions, SORT_DESC, $quantities, SORT_DESC, $this->cargoInfo);

        // dd($this->cargoInfo[1]);

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        usort($uniqueTrucksArray, function ($a, $b) {
            $volA = $a['length'] * $a['width'];
            $volB = $b['length'] * $b['width'];
            if ($volA === $volB) {
                return $b['max_weight'] - $a['max_weight'];
            }
            return $volB - $volA;
        });

        // dd($uniqueTrucksArray);

        $truckInfo = $filteredTruckInfo = $chosenTrucks = $cargoBoxLoadInfo = $finalTrucks = [];
        $boxTotalVolumeWithoutHeight = [];
        $minValueTruckType = $totalBoxLength = $totalRowNeededForContainingBox =  $emptySpacePerRow = null;

        $smallestValue = PHP_INT_MAX; // Initialize to a high value.
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $highestFillableBoxInEachTruck = PHP_INT_MIN;
        $minDiff = PHP_INT_MAX;
        $maxDiff = PHP_INT_MAX;
        $closestMin = null;
        $closestMax = null;

        foreach ($this->cargoInfo as $cargokey => $box) {
            dump($cargokey);
            // $this->truckBoxContainCapacity = [];
            dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            // $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxVolumeWithoutHeight = $boxDim[0] * $boxDim[1];
            $boxQuantity = $box['quantity'];
            // dump($boxVolume);

            $boxLength = $boxDim[0];
            $boxWidth = $boxDim[1];

            $boxTotalVolumeWithoutHeight[] = $boxVolumeWithoutHeight * $boxQuantity;

            if (array_key_exists(0, $filteredTruckInfo)) {
                $minDifference = PHP_INT_MAX;
                $maxDifference = PHP_INT_MIN;
                $minDifferenceKey = PHP_INT_MIN;
                // $highestFiilableBoxQuantityInEachTruck = $highestFiilableBoxQuantityInEachTruckKey = PHP_INT_MIN;
                // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                $selectedTempTruck = $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                // dd($selectedTempTruck);

                if ($selectedTempTruck['total_truck'] > 1) {
                    $finalTrucks[] = $selectedTempTruck;
                    $finalTrucks[sizeof($finalTrucks) - 1]['total_truck'] = ($selectedTempTruck['total_truck'] - 1);
                    unset($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'][$selectedTempTruck['total_truck'] - 1]);
                    unset($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'][$selectedTempTruck['total_truck'] - 1]);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                    // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'] as $tmpBoxKey => $tmpBox) {
                    //     $searchedDimension = $tmpBox["box_dimension"];
                    //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                    //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                    // }

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                    // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'] as $tmpBoxKey => $tmpBox) {
                    //     $searchedDimension = $tmpBox["box_dimension"];
                    //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                    //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                    //     $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
                    // }



                    $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                    // dd($partiallyLoadedTruckBoxQuantity);
                    $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
                    // dd($filteredTruckInfo);
                    // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                    $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                    // dd($filteredTruckInfoKey);
                    $finalTrucks[] = $filteredTruckInfoKey;
                    // dd($finalTrucks);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                    // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'] as $tmpBoxKey => $tmpBox) {
                    //     $searchedDimension = $tmpBox["box_dimension"];
                    //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                    //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                    // }


                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                    // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'] as $tmpBoxKey => $tmpBox) {
                    //     $searchedDimension = $tmpBox["box_dimension"];
                    //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                    //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                    //     $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
                    // }
                    // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                } else {
                    $finalTrucks[] = $selectedTempTruck;
                    // dd($finalTrucks);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                    // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'] as $tmpBoxKey => $tmpBox) {
                    //     $searchedDimension = $tmpBox["box_dimension"];
                    //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                    //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                    // }

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                    // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'] as $tmpBoxKey => $tmpBox) {
                    //     $searchedDimension = $tmpBox["box_dimension"];
                    //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                    //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                    //     $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
                    // }
                }
                dump($finalTrucks);






                // dump($highestFiilableBoxQuantityInEachTruckKey);
                // if (array_key_exists($filteredTruckInfoKey, $filteredTruckInfo)) {
                //     $selectedTempTruck = $filteredTruckInfo[$filteredTruckInfoKey];
                //     $truckDimension = explode('*', $selectedTempTruck['truck_dimension']);
                //     $boxDimension = explode('*', $selectedTempTruck['box_dimension']);

                //     $index = sizeof($cargoBoxLoadInfo);
                //     dump($selectedTempTruck);

                //     $cargoBoxLoadInfo[$index] = [
                //         "truck" => $selectedTempTruck['truck'],
                //         "truck_dimension" => $selectedTempTruck['truck_dimension'],
                //         "truck_length" => $truckDimension[0],
                //         "truck_width" => $truckDimension[1],
                //         "box_dimension" => $selectedTempTruck['box_dimension'],
                //         "box_length" => $boxDimension[0],
                //         "box_width" => $boxDimension[1],
                //         "empty_space_per_row" => $selectedTempTruck['empty_space_per_row'],
                //         "box_contain_per_row" => $selectedTempTruck['box_contain_per_row'],
                //         "total_row_for_containing_box" => $selectedTempTruck['total_row_for_containing_box'],
                //         "total_box_length" => $selectedTempTruck['total_box_length'],
                //         "total_box_quantity" => $selectedTempTruck['total_box_quantity'],
                //         "fillable_box_quantity_in_each_truck" => $selectedTempTruck['fillable_box_quantity_in_each_truck'],
                //         "fillable_row_in_each_truck" => $selectedTempTruck['fillable_row_in_each_truck'],
                //     ];

                //     $totalTruck = ($selectedTempTruck['total_row_for_containing_box'] * $boxDimension[0]) / $truckDimension[0];
                //     if (is_float($totalTruck)) {
                //         $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (intval($totalTruck) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                //         $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
                //         // dd($filteredTruckInfo);
                //         $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                //         $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                //         dd($filteredTruckInfoKey);
                //         // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                //     }

                //     // check if the empty width is greater than the new box width and if it is than store the storeable boxes
                //     if ($selectedTempTruck['empty_space_per_row'] >= $boxWidth) {
                //         $boxContainPerRowInEmptySpace = intval($selectedTempTruck['empty_space_per_row'] / $boxWidth);
                //         $totalNoOfRow = intval($selectedTempTruck['total_box_length'] / $boxLength);
                //         $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                //         $boxQuantity -= $filledQuantity;

                //         $cargoBoxLoadInfo[$index] = [
                //             "other_box_in_empty_space" => [
                //                 "box_dimension" => $box['box_dimension'],
                //                 "box_length" => $dim[0],
                //                 "box_width" => $dim[1],
                //                 "box_contain_per_row" => $boxContainPerRowInEmptySpace,
                //                 "total_row_for_containing_box" => $totalNoOfRow,
                //                 // "total_filled_box_length" => $selectedTempTruck['total_box_length'],
                //                 "total_box_quantity" => $boxQuantity,
                //                 "fillable_box_quantity_in_each_truck_empty_space" => $filledQuantity,
                //                 "remaining_box_quantity" => $boxQuantity - $filledQuantity,
                //                 // "fillable_row_in_each_truck_empty_space" => $selectedTempTruck['fillable_row_in_each_truck'],
                //             ]
                //         ];
                //     }
                // }
                // dump($boxQuantity);
            }
            // dump($boxQuantity);

            if ($boxQuantity == 0) {
                continue;
            } else {
                $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $box['box_dimension'], $boxQuantity);

                // dump($filteredTruckInfo);

                // if (!Arr::exists($this->cargoInfo, ++$cargokey)) {
                if (!array_key_exists(++$cargokey, $this->cargoInfo)) {
                    // dd("yo");
                    // $smallestKey = null;

                    // foreach ($filteredTruckInfo as $key => $truckData) {
                    //     $dimension = explode('*', $truckData['truck_dimension']);
                    //     // $calculatedValue = $truckData["total_truck"] * $dimension[0] - $truckData["total_box_length"];
                    //     $calculatedValue = $truckData["fillable_box_quantity_in_each_truck"] - $truckData["total_box_quantity"];
                    //     dump($calculatedValue);
                    //     if ($calculatedValue < $smallestValue) {
                    //         $smallestValue = $calculatedValue;
                    //         $smallestKey = $key;
                    //     }
                    // }

                    // $selectedTempTruck = $filteredTruckInfo[$smallestKey];
                    // $truckDimension = explode('*', $selectedTempTruck['truck_dimension']);
                    // $boxDimension = explode('*', $selectedTempTruck['box_dimension']);

                    // $index = sizeof($cargoBoxLoadInfo);

                    // $cargoBoxLoadInfo[$index] = [
                    //     "truck" => $selectedTempTruck['truck'],
                    //     "truck_dimension" => $selectedTempTruck['truck_dimension'],
                    //     "truck_length" => $truckDimension[0],
                    //     "truck_width" => $truckDimension[1],
                    //     "box_dimension" => $selectedTempTruck['box_dimension'],
                    //     "box_length" => $boxDimension[0],
                    //     "box_width" => $boxDimension[1],
                    //     "empty_space_per_row" => $selectedTempTruck['empty_space_per_row'],
                    //     "box_contain_per_row" => $selectedTempTruck['box_contain_per_row'],
                    //     "total_row_for_containing_box" => $selectedTempTruck['total_row_for_containing_box'],
                    //     "total_box_length" => $selectedTempTruck['total_box_length'],
                    //     "total_box_quantity" => $selectedTempTruck['total_box_quantity'],
                    //     "fillable_box_quantity_in_each_truck" => $selectedTempTruck['fillable_box_quantity_in_each_truck'],
                    //     "fillable_row_in_each_truck" => $selectedTempTruck['fillable_row_in_each_truck'],
                    // ];
                    // $smallestArrayElement = $filteredTruckInfo[$smallestKey];
                    dump("last box");
                    // dump($smallestArrayElement);
                    // $chosenTrucks [] = $smallestArrayElement;



                    // dd($box['box_dimension']);
                    // dd($finalTrucks);
                    // dd($this->cargoInfo[$cargokey]);
                    // dd($this->cargoInfo[--$cargokey]['quantity']);
                    // dd($filteredTruckInfo);
                    $selectedTempTruck = $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension'], true);
                    // dd($filteredTruckInfoKey);
                    // dump($filteredTruckInfoKey);
                    // dd(sizeof($finalTrucks));

                    if ($selectedTempTruck['total_truck'] > 1) {
                        $finalTrucks[] = $selectedTempTruck;
                        $finalTrucks[sizeof($finalTrucks) - 1]['total_truck'] = ($selectedTempTruck['total_truck'] - 1);
                        unset($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'][$selectedTempTruck['total_truck'] - 1]);
                        unset($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'][$selectedTempTruck['total_truck'] - 1]);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                        // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'] as $tmpBoxKey => $tmpBox) {
                        //     $searchedDimension = $tmpBox["box_dimension"];
                        //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                        //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                        // }

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                        // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'] as $tmpBoxKey => $tmpBox) {
                        //     $searchedDimension = $tmpBox["box_dimension"];
                        //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                        //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                        //     $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
                        // }



                        $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                        // dd($partiallyLoadedTruckBoxQuantity);
                        $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
                        // dd($filteredTruckInfo);
                        // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                        $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension'], true);
                        $finalTrucks[] = $filteredTruckInfoKey;
                        // dd($finalTrucks);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                        // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'] as $tmpBoxKey => $tmpBox) {
                        //     $searchedDimension = $tmpBox["box_dimension"];
                        //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                        //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                        // }

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                        // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'] as $tmpBoxKey => $tmpBox) {
                        //     $searchedDimension = $tmpBox["box_dimension"];
                        //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                        //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                        //     $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
                        // }

                        // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                    } else {
                        $finalTrucks[] = $selectedTempTruck;
                        // dd($finalTrucks);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                        // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'] as $tmpBoxKey => $tmpBox) {
                        //     $searchedDimension = $tmpBox["box_dimension"];
                        //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                        //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                        // }

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                        // foreach ($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'] as $tmpBoxKey => $tmpBox) {
                        //     $searchedDimension = $tmpBox["box_dimension"];
                        //     $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
                        //     $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
                        //     $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
                        // }
                    }
                }
                // dump($filteredTruckInfo);

                // dump("box length*width: $boxLength*$boxWidth Quantity: $boxQuantity");
                // dump("$minValueTruckType : $minValueTruckDimension");
                // dump("truck width: $closestMin");
                // dump("boxContainPerRow: $boxContainPerRow");
                // dump("totalRowForContainingBox: $totalRowForContainingBox");
                // dump("box total length: " . $totalRowForContainingBox*$boxLength);

                // dd("");
            }
        }

        // dump($cargoBoxLoadInfo);
        dd($finalTrucks);
        // dd($cargoBoxLoadInfo);

        dd("finish for now");

        // Return the consolidated cargo and any remaining cargo to the view
        // return response()->json($result);
        return redirect()->back()->with('finalTrucksData', $finalTrucks);
        return view('admin.v1.cargo.distribute-cargo.index', $finalTrucks);
    }

    private function reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, $boxType)
    {
        foreach ($finalTrucks[sizeof($finalTrucks) - 1][$boxType] as $tmpBoxKey => $tmpBox) {
            $searchedDimension = $tmpBox["box_dimension"];
            $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
            $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
            if ($boxType == 'other_box_load_info') {
                $boxQuantity -=  $tmpBox['total_filled_box_quantity'];
            }
        }
        return $boxQuantity;
    }

    private function getFillableQuantity($totalNoOfRow, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $usedBoxDim)
    {
        $fillableQuantity = 0;
        $boxContainPerRowInEmptySpace = 0;
        $boxContainInLastRowEmptySpace = 0;
        $usedBoxDim = explode('*', $usedBoxDim);
        $usedBoxLength = $usedBoxDim[0];
        $ifBoxCanFit = false;

        if ($totalNoOfRow == 1) {
            // if ($emptySpaceOfLastFilledRow >= $boxWidth && $emptySpaceOfLastFilledRow >= $boxLength && $boxWidth != $boxLength) {
            //     $tmpFillableQtyForLength = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxLength);
            //     $tmpFillableQtyForWidth = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxWidth);
            //     $boxContainInLastRowEmptySpace = ($tmpFillableQtyForLength >= $tmpFillableQtyForWidth) ? $tmpFillableQtyForLength : $tmpFillableQtyForWidth;
            // } else {
            //     $boxContainInLastRowEmptySpace = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxLength);
            // }

            if (($boxWidth > $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow) && $boxWidth <= $usedBoxLength) {
                $ifBoxCanFit = true;
                $tmpBoxWidth = $boxLength;
                $boxLength = $boxWidth;
                $boxWidth = $tmpBoxWidth;
                $boxContainInLastRowEmptySpace = $emptySpaceOfLastFilledRow / $boxWidth;
            } else if (($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow) && ($boxWidth <= $usedBoxLength && $boxLength <= $usedBoxLength) && $boxLength != $boxWidth) {
                $ifBoxCanFit = true;
                $boxContainPerRowForWidth = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxWidth);
                $boxContainPerRowForLength = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxLength);
                $fillableBoxQuantityForEachTruckForWidth = intval($usedBoxLength / $boxLength) * $boxContainPerRowForWidth;
                $fillableBoxQuantityForEachTruckForLength = intval($usedBoxLength / $boxWidth) * $boxContainPerRowForLength;

                if ($fillableBoxQuantityForEachTruckForWidth >= $fillableBoxQuantityForEachTruckForLength) {
                    $boxContainInLastRowEmptySpace = $boxContainPerRowForWidth;
                } else {
                    $boxContainInLastRowEmptySpace = $boxContainPerRowForLength;
                    $tmpBoxWidth = $boxLength;
                    $boxLength = $boxWidth;
                    $boxWidth = $tmpBoxWidth;
                }
            } else if ((($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength > $emptySpaceOfLastFilledRow && $boxLength <= $usedBoxLength) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxWidth > $usedBoxLength && $boxLength <= $usedBoxLength) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $usedBoxLength && $boxWidth == $boxLength) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $usedBoxLength))) {
                $ifBoxCanFit = true;
                $boxContainInLastRowEmptySpace = $emptySpaceOfLastFilledRow / $boxWidth;
            }

            // if ($emptySpaceOfLastFilledRow < $boxWidth && $emptySpaceOfLastFilledRow >= $boxLength) {
            //     $boxContainInLastRowEmptySpace = intval($emptySpaceOfLastFilledRow / $boxLength);
            // } else if ($emptySpaceOfLastFilledRow >= $boxWidth && $emptySpaceOfLastFilledRow < $boxLength) {
            //     $boxContainInLastRowEmptySpace = intval($emptySpaceOfLastFilledRow / $boxWidth);
            // } else if ($emptySpaceOfLastFilledRow >= $boxWidth && $emptySpaceOfLastFilledRow >= $boxLength && $boxWidth == $boxLength) {
            //     $boxContainInLastRowEmptySpace = intval($emptySpaceOfLastFilledRow / $boxWidth);
            // } else if ($emptySpaceOfLastFilledRow >= $boxWidth && $emptySpaceOfLastFilledRow >= $boxLength && $boxWidth != $boxLength) {
            //     $tmpFillableQtyForLength = intval($emptySpaceOfLastFilledRow / $boxLength);
            //     $tmpFillableQtyForWidth = intval($emptySpaceOfLastFilledRow / $boxWidth);
            //     $boxContainInLastRowEmptySpace = ($tmpFillableQtyForLength >= $tmpFillableQtyForWidth) ? $tmpFillableQtyForLength : $tmpFillableQtyForWidth;
            // }
        } else {
            $boxContainPerRowInEmptySpace = $this->getIntegerFromFloatingPoint($emptySpacePerRow / $boxWidth);
            $fillableQuantity = ($totalNoOfRow - 1) *  $boxContainPerRowInEmptySpace;

            // if ($emptySpaceOfLastFilledRow >= $boxWidth && $emptySpaceOfLastFilledRow >= $boxLength && $boxWidth != $boxLength) {
            //     $tmpFillableQtyForLength = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxLength);
            //     $tmpFillableQtyForWidth = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxWidth);
            //     $boxContainInLastRowEmptySpace = ($tmpFillableQtyForLength >= $tmpFillableQtyForWidth) ? $tmpFillableQtyForLength : $tmpFillableQtyForWidth;
            // } else {
            //     $boxContainInLastRowEmptySpace = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxLength);
            // }

            if (($boxWidth > $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow) && $boxWidth <= $usedBoxLength) {
                $ifBoxCanFit = true;
                $tmpBoxWidth = $boxLength;
                $boxLength = $boxWidth;
                $boxWidth = $tmpBoxWidth;
                $boxContainInLastRowEmptySpace = $emptySpaceOfLastFilledRow / $boxWidth;
            } else if (($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow) && ($boxWidth <= $usedBoxLength && $boxLength <= $usedBoxLength) && $boxLength != $boxWidth) {
                $ifBoxCanFit = true;
                $boxContainPerRowForWidth = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxWidth);
                $boxContainPerRowForLength = $this->getIntegerFromFloatingPoint($emptySpaceOfLastFilledRow / $boxLength);
                $fillableBoxQuantityForEachTruckForWidth = intval($usedBoxLength / $boxLength) * $boxContainPerRowForWidth;
                $fillableBoxQuantityForEachTruckForLength = intval($usedBoxLength / $boxWidth) * $boxContainPerRowForLength;

                if ($fillableBoxQuantityForEachTruckForWidth >= $fillableBoxQuantityForEachTruckForLength) {
                    $boxContainInLastRowEmptySpace = $boxContainPerRowForWidth;
                } else {
                    $boxContainInLastRowEmptySpace = $boxContainPerRowForLength;
                    $tmpBoxWidth = $boxLength;
                    $boxLength = $boxWidth;
                    $boxWidth = $tmpBoxWidth;
                }
            } else if ((($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength > $emptySpaceOfLastFilledRow && $boxLength <= $usedBoxLength) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxWidth > $usedBoxLength && $boxLength <= $usedBoxLength) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $usedBoxLength && $boxWidth == $boxLength) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $usedBoxLength))) {
                $ifBoxCanFit = true;
                $boxContainInLastRowEmptySpace = $emptySpaceOfLastFilledRow / $boxWidth;
            }
        }
        // dump($boxContainPerRowInEmptySpace);
        // dump($boxContainInLastRowEmptySpace);
        // dump($totalNoOfRow);

        if ($totalNoOfRow == 1) {
            // dump("if");
            $fillableQuantity += $boxContainInLastRowEmptySpace;
        } else {
            // dump("else");
            $fillableQuantity += ($totalNoOfRow - 1) * $boxContainPerRowInEmptySpace + $boxContainInLastRowEmptySpace;
        }
        // dump($fillableQuantity);
        return $fillableQuantity;
    }

    private function getIntegerFromFloatingPoint($floatingPoint)
    {
        if (is_float($floatingPoint)) {
            $arr = explode('.', $floatingPoint);
            $floatingPoint = $arr[0];
        }
        return $floatingPoint;
    }

    private function getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $cargoBoxDimension, $lasCargotBox = false)
    {
        dump($filteredTruckInfo);
        $excludedBoxDimension = $filteredTruckInfo[0]['box_dimension'];
        $tmpSelectedTempTruck = [];

        foreach ($this->cargoInfo as $cargoBoxKey => $cargoInfo) {
            if ($cargoInfo['box_dimension'] == $excludedBoxDimension) {
                continue;
            }
            dump($cargoInfo['box_dimension']);
            // $cargoBoxkey = array_search($cargoBoxDimension, array_column($this->cargoInfo, 'box_dimension'));
            // dump($this->cargoInfo[$cargoBoxkey]['box_dimension'] . " : " . $this->cargoInfo[$cargoBoxkey]['quantity']);
            // $highestFiilableBoxQuantityInEachTruck = $highestFiilableBoxQuantityInEachTruckKey = null;
            // $lowestFiilableBoxQuantityInEachTruck = $lowestFiilableBoxQuantityInEachTruckKey = null;
            // $redFlag = null;
            // foreach ($filteredTruckInfo as $key => $truckData) {
            //     $totalTruck = $truckData["total_truck"];
            //     $totalBoxQuantity = $truckData["total_box_quantity"];
            //     $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
            //     if ($fillableBoxQuantityInEachTruck <= $totalBoxQuantity && $fillableBoxQuantityInEachTruck > $highestFiilableBoxQuantityInEachTruck) {
            //         $highestFiilableBoxQuantityInEachTruck = $fillableBoxQuantityInEachTruck;
            //         $highestFiilableBoxQuantityInEachTruckKey = $key;
            //         // dump("uo $key");
            //     }
            //     $redFlag = ($fillableBoxQuantityInEachTruck > $totalBoxQuantity) ? true : false;
            //     // // check if the empty width is greater than the new box width and if it is than store the storeable boxes
            //     // if ($truckData['empty_space_per_row'] >= $boxWidth) {
            //     //     $dimension = explode('*', $truckData['truck_dimension']);
            //     //     $fillableLengthInTruck = $dimension[0] / $boxLength;
            //     //     $boxLengthNeedsToBeFilled = $boxLength * $boxQuantity;
            //     //     if ($fillableLengthInTruck > $boxLengthNeedsToBeFilled) {
            //     //         $fillDifference = $fillableLengthInTruck - $boxLengthNeedsToBeFilled;
            //     //         // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
            //     //         if ($fillDifference < $minDifference) {
            //     //             $minDifference = $fillDifference;
            //     //             $minDifferenceKey = $key;
            //     //         }
            //     //     } else {
            //     //         $fillDifference = $fillableLengthInTruck;
            //     //         if ($fillDifference > $maxDifference) {
            //     //             $maxDifference = $fillDifference;
            //     //             $minDifferenceKey = $key;
            //     //         }
            //     //     }
            //     // }
            //     // dd($highestFiilableBoxQuantityInEachTruckKey);
            // }
            // dump("redFlag : $redFlag");
            // if ($redFlag == true) {


            // $boxDimension = $this->cargoInfo[$cargoBoxkey]['box_dimension'];
            // $boxDim = explode('*', $this->cargoInfo[$cargoBoxkey]['box_dimension']);
            $boxDim = explode('*', $cargoInfo['box_dimension']);
            $boxLength = floatval($boxDim[0]);
            // $arr = explode('.', $boxLength);
            // $boxLength = $arr[0] + (0.1 * $arr[1]);
            // dump(gettype($boxLength));
            $boxWidth = $boxDim[1];
            $boxHeight = $boxDim[2];
            // dump($boxVolume);

            $tmpFilteredTruckInfo = $filteredTruckInfo;

            foreach ($filteredTruckInfo as $tempKey => $item) {
                $prevBoxDim = explode('*', $item['used_box_dimension']);
                $truckDim = explode('*', $item['truck_dimension']);
                $truckLength = $truckDim[0];
                $truckWidth = $truckDim[1];
                $truckDimension = $truckLength . "*" . $truckWidth . "*" . $truckDim[2];
                // $tmpBoxQuantity = $boxQuantity = $this->cargoInfo[$cargoBoxkey]['quantity'];
                $tmpBoxQuantity = $boxQuantity = $cargoInfo['quantity'];

                for ($i = 1; $i <= $item['total_truck']; $i++) {
                    dump($cargoInfo['quantity']);
                    $fillableQuantity = $filledQuantity = $boxContainPerRow = $filledQuantityOnPrevUnoccupiedRowSpace = $boxQuantityOnFullyUnfilledRow = $boxQuantityOnPartiallyFilledRow = 0;
                    $availableTotalNoOfRow = 0;
                    $emptySpaceByLength = 0;
                    $boxDimension = explode('*', $item['used_box_dimension']);
                    $truckDimension = explode('*', $item['truck_dimension']);

                    $emptySpacePerRow = $item['empty_space_per_row'];
                    $emptySpaceOfLastFilledRow = $item['empty_space_of_last_filled_row'];
                    $ifBoxCanFit = false;
                    $tmpBoxLength = $boxLength;
                    $tmpBoxWidth = $boxWidth;

                    if ($i == $item['total_truck']) {
                        if ($item['total_truck'] == 1) {
                            $lastTruckFilledBoxQuantity = ($item['fillable_box_quantity_in_each_truck'] > $item['total_box_quantity']) ? $item['total_box_quantity'] : $item['fillable_box_quantity_in_each_truck'];
                        } else {
                            $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - (($item['total_truck'] - 1) * $item['fillable_box_quantity_in_each_truck']);
                        }
                        $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                        // dump("lastTruckOccupiedRow : $lastTruckOccupiedRow");
                        $lastTruckOccupiedRow = is_float($lastTruckOccupiedRow) ? intval($lastTruckOccupiedRow) + 1 : $lastTruckOccupiedRow;
                        $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0]; // ghfufuggjhghjghjfvhdfkhvjdfgvfvgdfgvdhgvds
                        $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                        // dump(gettype($lastTruckOccupiedLength));
                        // dump($lastTruckUnoccupiedLength);

                        if (($boxWidth > $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow) && $boxQuantity > 0) {
                            // $ifBoxCanFit = true;
                            $boxLength = $tmpBoxWidth;
                            $boxWidth = $tmpBoxLength;

                            $totalNoOfRow = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxLength);

                            $fillableQuantity += $this->getFillableQuantity($totalNoOfRow, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $item['used_box_dimension']);
                            $filledQuantityOnPrevUnoccupiedRowSpace = $fillableQuantity;
                        } else if ((($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength > $emptySpaceOfLastFilledRow) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength == $boxWidth)) && $boxQuantity > 0) {
                            // $ifBoxCanFit = true;

                            $totalNoOfRow = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxLength);

                            $fillableQuantity += $this->getFillableQuantity($totalNoOfRow, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $item['used_box_dimension']);
                            $filledQuantityOnPrevUnoccupiedRowSpace = $fillableQuantity;
                        } else if ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow && $boxLength != $boxWidth && $boxQuantity > 0) {
                            // $ifBoxCanFit = true;

                            $totalNoOfRowForLength = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxWidth);
                            $fillableQuantityForLength = $this->getFillableQuantity($totalNoOfRowForLength, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxWidth, $boxLength, $item['used_box_dimension']);

                            $totalNoOfRowForWidth = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxLength);
                            $fillableQuantityForWidth = $this->getFillableQuantity($totalNoOfRowForWidth, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $item['used_box_dimension']);

                            if ($fillableQuantityForWidth >= $fillableQuantityForLength) {
                                $fillableQuantity = $fillableQuantityForWidth;
                            } else {
                                $boxLength = $tmpBoxWidth;
                                $boxWidth = $tmpBoxLength;
                                $fillableQuantity = $fillableQuantityForLength;
                            }
                            $filledQuantityOnPrevUnoccupiedRowSpace = $fillableQuantity;
                        }

                        if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                            $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        }

                        // // dump($item['truck'] . " : $boxWidth : " . $item['empty_space_per_row']);
                        // if (($item['empty_space_per_row'] >= $boxWidth || $item['empty_space_of_last_filled_row'] >= $boxWidth) && $boxQuantity > 0) {
                        //     $totalNoOfRow = $lastTruckOccupiedLength / $boxLength;
                        //     // dump($item['truck'] . " : $totalNoOfRow : $boxWidth : " . $item['empty_space_of_last_filled_row'] . " " . intval($item['empty_space_of_last_filled_row'] / $boxWidth));
                        //     // $totalNoOfRow = is_float($totalNoOfRow) ? intval($totalNoOfRow) + 1 : $totalNoOfRow;
                        //     if ($totalNoOfRow == 1 && $item['empty_space_of_last_filled_row'] >= $boxWidth) {
                        //         $boxContainPerRowInEmptySpace = intval($item['empty_space_of_last_filled_row'] / $boxWidth);
                        //     } else {
                        //         // $totalNoOfRow = $totalNoOfRow - 1;
                        //         $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        //         // $boxContainPerRowInEmptySpace += ($item['empty_space_of_last_filled_row'] >= $boxWidth) ? intval($item['empty_space_of_last_filled_row'] / $boxWidth) : 0;
                        //     }
                        //     $fillableQuantity = ($totalNoOfRow - 1) *  $boxContainPerRowInEmptySpace;
                        //     $fillableQuantity += ($item['empty_space_of_last_filled_row'] >= $boxWidth) ? intval($item['empty_space_of_last_filled_row'] / $boxWidth) : $boxContainPerRowInEmptySpace;
                        //     $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        //     if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                        //         // $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                        //         $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        //     }
                        //     // $lastTruckUnoccupiedLength = $truckDimension[0] - $totalNoOfRow * $boxLength;
                        //     // dd($lastTruckOccupiedLength);
                        //     // dump($boxQuantityOnPartiallyFilledRow);
                        //     // dump($totalNoOfRow);
                        //     // dump($fillableQuantity);
                        // }

                        if (($boxWidth > $truckWidth && $boxLength <= $truckWidth) && $boxWidth <= $lastTruckUnoccupiedLength && $boxQuantity > 0) {
                            $ifBoxCanFit = true;
                            $tmpBoxWidth = $boxLength;
                            $boxLength = $boxWidth;
                            $boxWidth = $tmpBoxWidth;
                            $boxContainPerRow = $truckDimension[1] / $boxWidth;
                        } else if (($boxWidth <= $truckWidth && $boxLength <= $truckWidth) && ($boxWidth <= $lastTruckUnoccupiedLength && $boxLength <= $lastTruckUnoccupiedLength) && $boxLength != $boxWidth && $boxQuantity > 0) {
                            $ifBoxCanFit = true;
                            $boxContainPerRowForWidth = $this->getIntegerFromFloatingPoint($truckDimension[1] / $boxWidth);
                            $boxContainPerRowForLength = $this->getIntegerFromFloatingPoint($truckDimension[1] / $boxLength);
                            $fillableBoxQuantityForEachTruckForWidth = intval($lastTruckUnoccupiedLength / $boxLength) * $boxContainPerRowForWidth;
                            $fillableBoxQuantityForEachTruckForLength = intval($lastTruckUnoccupiedLength / $boxWidth) * $boxContainPerRowForLength;

                            if ($fillableBoxQuantityForEachTruckForWidth >= $fillableBoxQuantityForEachTruckForLength) {
                                $boxContainPerRow = $boxContainPerRowForWidth;
                            } else {
                                $boxContainPerRow = $boxContainPerRowForLength;
                                $tmpBoxWidth = $boxLength;
                                $boxLength = $boxWidth;
                                $boxWidth = $tmpBoxWidth;
                            }
                        } else if ((($boxWidth <= $truckWidth && $boxLength > $truckWidth && $boxLength <= $lastTruckUnoccupiedLength) || ($boxWidth <= $truckWidth && $boxWidth > $lastTruckUnoccupiedLength && $boxLength <= $lastTruckUnoccupiedLength) || ($boxWidth <= $truckWidth && $boxLength <= $lastTruckUnoccupiedLength && $boxWidth == $boxLength) || ($boxWidth <= $truckWidth && $boxLength <= $lastTruckUnoccupiedLength)) && $boxQuantity > 0) {
                            $ifBoxCanFit = true;
                            $boxContainPerRow = $truckDimension[1] / $boxWidth;
                        }

                        if ($ifBoxCanFit) {
                            if ($boxContainPerRow != 0) {
                                $totalRowNeededForContainingBox = $this->getIntegerFromFloatingPoint($boxQuantity / $boxContainPerRow);
                                $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                                $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                                $fillableQuantity = ($availableTotalNoOfRow > $totalRowNeededForContainingBox) ? $totalRowNeededForContainingBox *  $boxContainPerRow : $availableTotalNoOfRow *  $boxContainPerRow;

                                $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                                $filledQuantity = ($boxQuantityOnPartiallyFilledRow > 0) ? $filledQuantity + $boxQuantityOnPartiallyFilledRow : $filledQuantity;

                                $filledTotalNoOfRow = $boxQuantityOnFullyUnfilledRow / $boxContainPerRow;
                                $filledTotalNoOfRow = is_float($filledTotalNoOfRow) ? $this->getIntegerFromFloatingPoint($filledTotalNoOfRow) + 1 : $filledTotalNoOfRow;
                                $filledLength = $filledTotalNoOfRow * $boxLength;
                                dump($lastTruckUnoccupiedLength);
                                dump($filledLength);
                                $emptySpaceByLength = $lastTruckUnoccupiedLength - $filledLength;
                            }
                        }

                        // if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        //     $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        //     if ($boxContainPerRow != 0) {
                        //         $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        //         $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        //         $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        //         $fillableQuantity = ($availableTotalNoOfRow > $totalRowNeededForContainingBox) ? $totalRowNeededForContainingBox *  $boxContainPerRow : $availableTotalNoOfRow *  $boxContainPerRow;

                        //         // $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                        //         // if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                        //         //     $filledQuantity = ($filledQuantityOnPrevUnoccupiedRowSpace > 0) ? $filledQuantity + $filledQuantityOnPrevUnoccupiedRowSpace : $filledQuantity;
                        //         // }
                        //         $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        //         $filledQuantity = ($boxQuantityOnPartiallyFilledRow > 0) ? $filledQuantity + $boxQuantityOnPartiallyFilledRow : $filledQuantity;
                        //     }
                        // }
                        // dump($boxQuantityOnPartiallyFilledRow);
                        // dump($boxQuantityOnFullyUnfilledRow);
                        $totalFilledBoxQuantity = ($boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow > $boxQuantity) ? $boxQuantity : $boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow;
                        $boxQuantity -= $filledQuantity;
                    } else {
                        // $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - $item['fillable_box_quantity_in_each_truck'];
                        // $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                        $lastTruckFilledBoxQuantity = $item['fillable_box_quantity_in_each_truck'];
                        $lastTruckOccupiedRow = $item['fillable_row_in_each_truck'];
                        $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                        $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                        // dump("lastTruckOccupiedRow: $lastTruckOccupiedRow boxLength: $boxDimension[0] lastTruckUnoccupiedLength: $lastTruckUnoccupiedLength truckLength: $truckDimension[0]");
                        // dd($lastTruckOccupiedLength);
                        // dump($truckDimension[0]);
                        // dump($boxDimension[0]);
                        // dump($lastTruckOccupiedRow);
                        // dump($lastTruckOccupiedLength);
                        // dump($lastTruckUnoccupiedLength);

                        $boxContainPerRowInEmptySpace = $totalNoOfRow = 0;
                        // dump($boxWidth);
                        // dump($boxLength);
                        // dump($emptySpacePerRow);
                        // dd($boxQuantity);

                        if (($boxWidth > $emptySpacePerRow && $boxLength <= $emptySpacePerRow) && $boxQuantity > 0) {
                            // dd("1");
                            $boxLength = $tmpBoxWidth;
                            $boxWidth = $tmpBoxLength;

                            $boxContainPerRowInEmptySpace = intval($emptySpacePerRow / $boxWidth);
                            $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                            $fillableQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        } else if ((($boxWidth <= $emptySpacePerRow && $boxLength > $emptySpacePerRow) || ($boxWidth <= $emptySpacePerRow && $boxLength == $boxWidth)) && $boxQuantity > 0) {
                            // dd("11");
                            $boxContainPerRowInEmptySpace = intval($emptySpacePerRow / $boxWidth);
                            $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                            $fillableQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        } else if ($boxWidth <= $emptySpacePerRow && $boxLength <= $emptySpacePerRow && $boxLength != $boxWidth && $boxQuantity > 0) {
                            // dd("111");
                            $boxContainPerRowInEmptySpaceForWidth = intval($emptySpacePerRow / $boxWidth);
                            $totalNoOfRowForWidth = intval($lastTruckOccupiedLength / $boxLength);
                            $fillableQuantityForWidth = $totalNoOfRowForWidth *  $boxContainPerRowInEmptySpaceForWidth;

                            $boxContainPerRowInEmptySpaceForLength = intval($emptySpacePerRow / $boxLength);
                            $totalNoOfRowForLength = intval($lastTruckOccupiedLength / $boxWidth);
                            $fillableQuantityForLength = $totalNoOfRowForLength *  $boxContainPerRowInEmptySpaceForLength;

                            if ($fillableQuantityForWidth >= $fillableQuantityForLength) {
                                $fillableQuantity = $fillableQuantityForWidth;
                                $totalNoOfRow = $totalNoOfRowForWidth;
                                $boxContainPerRowInEmptySpace = $boxContainPerRowInEmptySpaceForWidth;
                            } else {
                                $boxLength = $tmpBoxWidth;
                                $boxWidth = $tmpBoxLength;
                                $fillableQuantity = $fillableQuantityForLength;
                                $totalNoOfRow = $totalNoOfRowForLength;
                                $boxContainPerRowInEmptySpace = $boxContainPerRowInEmptySpaceForLength;
                            }
                        }
                        // dd("1111");

                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                            $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        }


                        // if ($item['empty_space_per_row'] >= $boxWidth && $boxQuantity > 0) {
                        //     $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        //     // dump($boxContainPerRowInEmptySpace);
                        //     $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                        //     $fillableQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        //     $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        //     // dump($fillableQuantity);
                        //     // dump("shit");
                        //     if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                        //         // $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                        //         $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        //     }
                        //     // $lastTruckUnoccupiedLength = $truckDimension[0] - $totalNoOfRow * $boxLength;
                        // }

                        if (($boxWidth > $truckWidth && $boxLength <= $truckWidth) && $boxWidth <= $lastTruckUnoccupiedLength && $boxQuantity > 0) {
                            $ifBoxCanFit = true;
                            $tmpBoxWidth = $boxLength;
                            $boxLength = $boxWidth;
                            $boxWidth = $tmpBoxWidth;
                            $boxContainPerRow = $truckDimension[1] / $boxWidth;
                        } else if (($boxWidth <= $truckWidth && $boxLength <= $truckWidth) && ($boxWidth <= $lastTruckUnoccupiedLength && $boxLength <= $lastTruckUnoccupiedLength) && $boxLength != $boxWidth && $boxQuantity > 0) {
                            $ifBoxCanFit = true;
                            $boxContainPerRowForWidth = $this->getIntegerFromFloatingPoint($truckDimension[1] / $boxWidth);
                            $boxContainPerRowForLength = $this->getIntegerFromFloatingPoint($truckDimension[1] / $boxLength);
                            $fillableBoxQuantityForEachTruckForWidth = intval($lastTruckUnoccupiedLength / $boxLength) * $boxContainPerRowForWidth;
                            $fillableBoxQuantityForEachTruckForLength = intval($lastTruckUnoccupiedLength / $boxWidth) * $boxContainPerRowForLength;

                            if ($fillableBoxQuantityForEachTruckForWidth >= $fillableBoxQuantityForEachTruckForLength) {
                                $boxContainPerRow = $boxContainPerRowForWidth;
                            } else {
                                $boxContainPerRow = $boxContainPerRowForLength;
                                $tmpBoxWidth = $boxLength;
                                $boxLength = $boxWidth;
                                $boxWidth = $tmpBoxWidth;
                            }
                        } else if ((($boxWidth <= $truckWidth && $boxLength > $truckWidth && $boxLength <= $lastTruckUnoccupiedLength) || ($boxWidth <= $truckWidth && $boxWidth > $lastTruckUnoccupiedLength && $boxLength <= $lastTruckUnoccupiedLength) || ($boxWidth <= $truckWidth && $boxLength <= $lastTruckUnoccupiedLength && $boxWidth == $boxLength) || ($boxWidth <= $truckWidth && $boxLength <= $lastTruckUnoccupiedLength)) && $boxQuantity > 0) {
                            $ifBoxCanFit = true;
                            $boxContainPerRow = $truckDimension[1] / $boxWidth;
                        }

                        if ($ifBoxCanFit) {
                            if ($boxContainPerRow != 0) {
                                $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                                $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                                $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                                $fillableQuantity = ($availableTotalNoOfRow > $totalRowNeededForContainingBox) ? $totalRowNeededForContainingBox *  $boxContainPerRow : $availableTotalNoOfRow *  $boxContainPerRow;

                                $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                                $filledQuantity = ($boxQuantityOnPartiallyFilledRow > 0) ? $filledQuantity + $boxQuantityOnPartiallyFilledRow : $filledQuantity;

                                $filledTotalNoOfRow = $boxQuantityOnFullyUnfilledRow / $boxContainPerRow;
                                $filledTotalNoOfRow = is_float($filledTotalNoOfRow) ? $this->getIntegerFromFloatingPoint($filledTotalNoOfRow) + 1 : $filledTotalNoOfRow;
                                $filledLength = $filledTotalNoOfRow * $boxLength;
                                dump($lastTruckUnoccupiedLength);
                                dump($filledLength);
                                $emptySpaceByLength = $lastTruckUnoccupiedLength - $filledLength;
                            }
                        }

                        // if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        //     // dump("yo");
                        //     $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        //     if ($boxContainPerRow != 0) {
                        //         // dump($truckDimension[1]);
                        //         // dump($boxWidth);
                        //         // dd($truckDimension[1] / $boxWidth);

                        //         $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        //         $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        //         $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        //         $fillableQuantity = ($availableTotalNoOfRow > $totalRowNeededForContainingBox) ? $totalRowNeededForContainingBox *  $boxContainPerRow : $availableTotalNoOfRow *  $boxContainPerRow;

                        //         // $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                        //         $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        //         $filledQuantity = ($boxQuantityOnPartiallyFilledRow > 0) ? $filledQuantity + $boxQuantityOnPartiallyFilledRow : $filledQuantity;
                        //         // dump($boxQuantityOnFullyUnfilledRow);
                        //     }
                        // }
                        // dd($filledQuantity);
                        $totalFilledBoxQuantity = ($boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow > $boxQuantity) ? $boxQuantity : $boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow;
                        $boxQuantity -= $filledQuantity;
                    }
                    // dd($filteredTruckInfo[$tempKey]);
                    if ($lasCargotBox == true || $filledQuantity == 0) {
                        $tmpFilteredTruckInfo[$tempKey]["other_box_load_info"][] = [
                            "truckArrTempKey" => null,
                            "cargoArrTempKey" => null,
                            "box_dimension" => null,
                            "used_box_dimension" => null,
                            "total_box_quantity" => null,
                            "can_contain_total_box_on_partially_filled_row" => null,
                            "can_contain_total_box_on_fully_unfilled_row" => null,
                            "can_contain_box_on_a_fully_unfilled_row" => null,
                            "total_filled_box_quantity" => null,
                            "fillable_row_in_each_truck" => null
                        ];
                    } else {
                        $tmpFilteredTruckInfo[$tempKey]["other_box_load_info"][] = [
                            "truckArrTempKey" => $tempKey,
                            "cargoArrTempKey" => '',
                            // "box_dimension" => ($filledQuantity != 0) ? $this->cargoInfo[$cargoBoxkey]['box_dimension'] : null,
                            // "total_box_quantity" => ($filledQuantity != 0) ? (($i == 1) ? $this->cargoInfo[$cargoBoxkey]['quantity'] : $tmpBoxQuantity) : null,
                            "box_dimension" => ($filledQuantity != 0) ? $cargoInfo['box_dimension'] : null,
                            "used_box_dimension" => $boxLength . "*" . $boxWidth . "*" . $boxHeight,
                            "total_box_quantity" => ($filledQuantity != 0) ? (($i == 1) ? $cargoInfo['quantity'] : $tmpBoxQuantity) : null,
                            "can_contain_total_box_on_partially_filled_row" => $boxQuantityOnPartiallyFilledRow,
                            "can_contain_total_box_on_fully_unfilled_row" => intval($availableTotalNoOfRow * $boxContainPerRow),
                            "can_contain_box_on_a_fully_unfilled_row" => $boxContainPerRow,
                            "total_filled_box_quantity" => $totalFilledBoxQuantity,
                            "fillable_row_in_each_truck" => $availableTotalNoOfRow
                        ];
                    }
                    $tmpFilteredTruckInfo[$tempKey]["truck_space"][] = [
                        "fully_filled_row_total_length_empty_space" => null,
                        "fully_filled_row_empty_space" => null,
                        "partially_filled_row_length_empty_space" => null,
                        "partially_filled_row_empty_space" => null,
                        "fully_unfilled_total_empty_space_by_length" => $emptySpaceByLength,
                        "fully_unfilled_total_empty_space_by_width" => null,
                    ];
                    $tmpBoxQuantity = $boxQuantity;
                    // dump($boxQuantity);
                }
            }
            // }
            dump($tmpFilteredTruckInfo);

            $maxFilledBoxQuantity = $maxFilledBoxTruckKey = null;
            foreach ($tmpFilteredTruckInfo as $tempKey => $item) {
                $filledBoxQuantity = $item['individual_truck'][0]['total_filled_box_quantity'] + $item['other_box_load_info'][0]['total_filled_box_quantity'];
                // dump($filledBoxQuantity);
                if ($filledBoxQuantity >= $maxFilledBoxQuantity) {
                    $maxFilledBoxQuantity = $filledBoxQuantity;
                    $maxFilledBoxTruckKey = $tempKey;
                }
            }
            $selectedTempTruck = $tmpFilteredTruckInfo[$maxFilledBoxTruckKey];
            $tmpSelectedTempTruck[] = $selectedTempTruck;
            dump($selectedTempTruck);
        }
        log::info(json_encode($tmpSelectedTempTruck));
        dd($tmpSelectedTempTruck);


        return $selectedTempTruck;

        // if ($selectedTempTruck['total_truck'] > 1) {
        //     $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
        //     // dd($partiallyLoadedTruckBoxQuantity);
        //     $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
        //     dd($filteredTruckInfo);
        //     $this->getFilteredTruckData1($filteredTruckInfo, $cargoBoxkey);
        //     $filteredTruckInfoKey = $this->getFilteredTruckDataKey($filteredTruckInfo, $cargoBoxkey);
        //     dd($filteredTruckInfoKey);
        //     // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
        // }

        // foreach ($filteredTruckInfo as $truckData) {
        //     $boxDimension = explode('*', $item['box_dimension']);
        //     $truckDimension = explode('*', $item['truck_dimension']);
        //     $lastTruckFilledBoxQuantity = intval($truckDimension[0] / $boxDimension[0]) * $item['box_contain_per_row'];
        //     // $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - $item['fillable_box_quantity_in_each_truck'];
        //     $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
        //     $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
        //     $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
        // }
        // dd($highestFiilableBoxQuantityInEachTruckKey);
        return $highestFiilableBoxQuantityInEachTruckKey;
    }

    private function getFilteredTruckDataKey1($filteredTruckInfo, $cargoBoxkey)
    {
        $highestFiilableBoxQuantityInEachTruck = $highestFiilableBoxQuantityInEachTruckKey = PHP_INT_MIN;
        $lowestFiilableBoxQuantityInEachTruck = $lowestFiilableBoxQuantityInEachTruckKey = PHP_INT_MAX;
        foreach ($filteredTruckInfo as $key => $truckData) {
            $totalTruck = $truckData["total_truck"];
            $totalBoxQuantity = $truckData["total_box_quantity"];
            $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
            if ($fillableBoxQuantityInEachTruck < $totalBoxQuantity && $fillableBoxQuantityInEachTruck > $highestFiilableBoxQuantityInEachTruck) {
                $highestFiilableBoxQuantityInEachTruck = $fillableBoxQuantityInEachTruck;
                $highestFiilableBoxQuantityInEachTruckKey = $key;
                // dump("uo $key");
            }
            if ($highestFiilableBoxQuantityInEachTruckKey < 0) {
                if ($fillableBoxQuantityInEachTruck > $totalBoxQuantity && $fillableBoxQuantityInEachTruck < $lowestFiilableBoxQuantityInEachTruck) {
                    $lowestFiilableBoxQuantityInEachTruck = $fillableBoxQuantityInEachTruck;
                    $lowestFiilableBoxQuantityInEachTruckKey = $key;
                    // dump("uo $key");
                }
            }
        }
        // dd($highestFiilableBoxQuantityInEachTruckKey);
        return $highestFiilableBoxQuantityInEachTruckKey;
    }

    private function getFilteredTruckData2($filteredTruckInfo, $cargoBoxkey)
    {
        $tempArray = $filteredTruckInfo;
        dump($filteredTruckInfo);
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $truckInfo = [];
        $boxDimension = $this->cargoInfo[$cargoBoxkey]['box_dimension'];
        $boxDim = explode('*', $boxDimension);
        $boxLength = $boxDim[0];
        $boxWidth = $boxDim[1];
        // dump($boxVolume);

        foreach ($filteredTruckInfo as $tempKey => $item) {
            $tempCargoBoxArray = $this->cargoInfo;
            $tempCargoBoxArraySize = sizeof($this->cargoInfo);
            $prevBoxDim = explode('*', $item['box_dimension']);
            $truckDim = explode('*', $item['truck_dimension']);
            $truckLength = $truckDim[0];
            $truckWidth = $truckDim[1];
            $truckDimension = $truckLength . "*" . $truckWidth . "*" . $truckDim[2];
            $filledQuantity = $boxContainPerRow = $filledQuantityOnPrevUnoccupiedRowSpace = 0;

            for ($i = 1; $i <= $item['total_truck']; $i++) {
                $boxQuantity = $this->cargoInfo[$cargoBoxkey]['quantity'];
                if ($i == $item['total_truck'] && $i == 1) {
                    // logic
                }
                if ($i == $item['total_truck']) {
                    // if (($item['fillable_box_quantity_in_each_truck'] * $item['total_truck']) > $item['total_box_quantity']) {
                    $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - (($item['total_truck'] - 1) * $item['fillable_row_in_each_truck']);
                    $boxDimension = explode('*', $item['box_dimension']);
                    $truckDimension = explode('*', $item['truck_dimension']);
                    $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                    $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                    $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;

                    if ($item['empty_space_per_row'] >= $boxWidth && $boxQuantity > 0) {
                        $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                        $filledQuantity += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        // dd($lastTruckOccupiedLength);
                        $boxQuantity -= $filledQuantity;
                    }

                    if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                            $filledQuantity += $totalRowNeededForContainingBox *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        } else {
                            $filledQuantity += $availableTotalNoOfRow *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        }
                    }
                    // }
                } else {
                    $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - $item['fillable_row_in_each_truck'];
                    $boxDimension = explode('*', $item['box_dimension']);
                    $truckDimension = explode('*', $item['truck_dimension']);
                    $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                    $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                    $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                    // dd($lastTruckOccupiedLength);

                    if ($item['empty_space_per_row'] >= $boxWidth && $boxQuantity > 0) {
                        $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                        $filledQuantity += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $boxQuantity -= $filledQuantity;
                    }

                    if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                            $filledQuantity += $totalRowNeededForContainingBox *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        } else {
                            $filledQuantity += $availableTotalNoOfRow *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        }
                    }
                }
                // dd($filteredTruckInfo[$tempKey]);
                $filteredTruckInfo[$tempKey]["other_box_load_info"] = [
                    "arrTempKey" => $tempKey,
                    "box_dimension" => $this->cargoInfo[$cargoBoxkey]['box_dimension'],
                    "box_contain_per_row" => $boxContainPerRow,
                    "total_box_quantity" => $this->cargoInfo[$cargoBoxkey]['quantity'],
                    "total_filled_box_quantity" => $filledQuantity,
                    "filled_quantity_on_prev_unoccupied_row_space" => $filledQuantityOnPrevUnoccupiedRowSpace,
                    "fillable_row_in_each_truck" => $availableTotalNoOfRow
                ];
                // dump($boxQuantity);
            }
        }

        // $this->getFilteredTruckDataKey1($filteredTruckInfo, $cargoBoxkey);
        dd($filteredTruckInfo, $boxQuantity);
    }

    private function getFilteredTruckData1($filteredTruckInfo, $cargoBoxkey)
    {
        dump($filteredTruckInfo);
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $truckInfo = [];
        $boxDimension = $this->cargoInfo[$cargoBoxkey]['box_dimension'];
        $boxDim = explode('*', $boxDimension);
        $boxLength = $boxDim[0];
        $boxWidth = $boxDim[1];
        // dump($boxVolume);

        foreach ($filteredTruckInfo as $tempKey => $item) {
            $prevBoxDim = explode('*', $item['box_dimension']);
            $truckDim = explode('*', $item['truck_dimension']);
            $truckLength = $truckDim[0];
            $truckWidth = $truckDim[1];
            $truckDimension = $truckLength . "*" . $truckWidth . "*" . $truckDim[2];
            $filledQuantity = $boxContainPerRow = $filledQuantityOnPrevUnoccupiedRowSpace = 0;

            for ($i = 1; $i <= $item['total_truck']; $i++) {
                $boxQuantity = $this->cargoInfo[$cargoBoxkey]['quantity'];
                if ($i == $item['total_truck'] && $i == 1) {
                    // logic
                }
                if ($i == $item['total_truck']) {
                    // if (($item['fillable_box_quantity_in_each_truck'] * $item['total_truck']) > $item['total_box_quantity']) {
                    $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - (($item['total_truck'] - 1) * $item['fillable_row_in_each_truck']);
                    $boxDimension = explode('*', $item['box_dimension']);
                    $truckDimension = explode('*', $item['truck_dimension']);
                    $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                    $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                    $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;

                    if ($item['empty_space_per_row'] >= $boxWidth && $boxQuantity > 0) {
                        $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                        $filledQuantity += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        // dd($lastTruckOccupiedLength);
                        $boxQuantity -= $filledQuantity;
                    }

                    if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                            $filledQuantity += $totalRowNeededForContainingBox *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        } else {
                            $filledQuantity += $availableTotalNoOfRow *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        }
                    }
                    // }
                } else {
                    $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - $item['fillable_row_in_each_truck'];
                    $boxDimension = explode('*', $item['box_dimension']);
                    $truckDimension = explode('*', $item['truck_dimension']);
                    $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                    $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                    $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                    // dd($lastTruckOccupiedLength);

                    if ($item['empty_space_per_row'] >= $boxWidth && $boxQuantity > 0) {
                        $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                        $filledQuantity += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $boxQuantity -= $filledQuantity;
                    }

                    if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                            $filledQuantity += $totalRowNeededForContainingBox *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        } else {
                            $filledQuantity += $availableTotalNoOfRow *  $boxContainPerRow;
                            $boxQuantity -= $filledQuantity;
                        }
                    }
                }
                // dd($filteredTruckInfo[$tempKey]);
                $filteredTruckInfo[$tempKey]["other_box_load_info"] = [
                    "arrTempKey" => $tempKey,
                    "box_dimension" => $this->cargoInfo[$cargoBoxkey]['box_dimension'],
                    "box_contain_per_row" => $boxContainPerRow,
                    "total_box_quantity" => $this->cargoInfo[$cargoBoxkey]['quantity'],
                    "total_filled_box_quantity" => $filledQuantity,
                    "filled_quantity_on_prev_unoccupied_row_space" => $filledQuantityOnPrevUnoccupiedRowSpace,
                    "fillable_row_in_each_truck" => $availableTotalNoOfRow
                ];
                // dump($boxQuantity);
            }

            // if ($boxWidth <= $truckWidth) {
            //     $selectedTruckWidth = $truckWidth;
            //     $selectedTruckType = $item["truck"];

            //     $boxContainPerRow = intval($selectedTruckWidth / $boxWidth);
            //     $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
            //     $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;
            //     $emptySpacePerRow = $selectedTruckWidth - ($boxWidth * $boxContainPerRow);

            //     // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowNeededForContainingBox");

            //     if (is_float($totalRowNeededForContainingBox)) {
            //         $totalRowNeededForContainingBox = intval($totalRowNeededForContainingBox) + 1;
            //     }
            //     // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowNeededForContainingBox");

            //     $totalTruck = $boxQuantity / (intval($truckLength / $boxLength) * $boxContainPerRow);
            //     if (is_float($totalTruck)) {
            //         $totalTruck = intval($totalTruck) + 1;
            //     }

            //     $truckInfo[] = [
            //         "truck" => $selectedTruckType,
            //         "total_truck" => $totalTruck,
            //         "truck_dimension" => $truckDimension,
            //         "box_dimension" => $boxDimension,
            //         "empty_space_per_row" => $emptySpacePerRow,
            //         "box_contain_per_row" => $boxContainPerRow,
            //         "total_row_for_containing_box" => $totalRowNeededForContainingBox,
            //         "total_box_length" => $totalRowNeededForContainingBox * $boxLength,
            //         "total_box_quantity" => $boxQuantity,
            //         "fillable_box_quantity_in_each_truck" => intval($truckLength / $boxLength) * $boxContainPerRow,
            //         "fillable_row_in_each_truck" => intval($truckLength / $boxLength)
            //     ];
            // }
        }

        // $this->getFilteredTruckDataKey1($filteredTruckInfo, $cargoBoxkey);
        dd($filteredTruckInfo, $boxQuantity);

        // dump($boxQuantity);
        dump($truckInfo);

        foreach ($truckInfo as $truckData) {
            $totalTruck = $truckData["total_truck"];
            $totalBoxQuantity = $truckData["total_box_quantity"];
            $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
            // if ($totalTruck < $lowestTotalTruck && $fillableBoxQuantityInEachTruck > $highestFillableBoxInEachTruck) {
            if ($totalTruck < $lowestTotalTruck) {
                $lowestTotalTruck = $totalTruck;
                $highestFillableBoxInEachTruck = $fillableBoxQuantityInEachTruck;
            }
        }

        $filteredTruckInfo = [];

        foreach ($truckInfo as $truckData) {
            // if ($truckData["total_truck"] == $lowestTotalTruck && $truckData["fillable_box_quantity_in_each_truck"] == $highestFillableBoxInEachTruck) {
            if ($truckData["total_truck"] == $lowestTotalTruck) {
                $filteredTruckInfo[] = $truckData;
            }
        }

        dd($filteredTruckInfo);
        return $filteredTruckInfo;
    }

    private function getFilteredTruckData($uniqueTrucksArray, $boxDimension, $boxQuantity)
    {
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $truckInfo = [];
        $boxDim = explode('*', $boxDimension);
        // $boxLength = $boxDim[0];
        // $boxWidth = $boxDim[1];
        // dump($boxDimension);
        // dump($boxQuantity);

        foreach ($uniqueTrucksArray as $count => $item) {
            $tmpBoxQuantity = $boxQuantity;
            $boxLength = $boxDim[0];
            $boxWidth = $boxDim[1];
            $boxHeight = $boxDim[2];
            $truckLength = $item['length'];
            $truckWidth = $item['width'];
            $truckDimension = $truckLength . "*" . $truckWidth . "*" . $item['height'];
            // $truckFilledBoxQuantity = 0;

            $selectedTruckWidth = $truckWidth;
            $selectedTruckType = $item["truck_type"];
            dump($selectedTruckType);
            $ifBoxCanFit = false;

            // if (($boxWidth <= $truckWidth && $boxLength > $truckWidth && $boxLength <= $truckLength)) { //deafult
            // }
            // if (($boxWidth <= $truckWidth && $boxWidth > $truckLength && $boxLength <= $truckLength)) { //deafult
            // }
            // if (($boxWidth <= $truckWidth && $boxLength <= $truckLength && $boxWidth == $boxLength)) {//deafult
            // }
            // if (($boxWidth <= $truckWidth && $boxLength <= $truckLength)) {//deafult
            // }
            // if (($boxWidth > $truckWidth && $boxLength <= $truckWidth) && $boxWidth <= $truckLength) { //shuffle boxwidth and boxlength
            // }

            if (($boxWidth > $truckWidth && $boxLength <= $truckWidth) && $boxWidth <= $truckLength) {
                $ifBoxCanFit = true;
                $tmpBoxWidth = $boxLength;
                $boxLength = $boxWidth;
                $boxWidth = $tmpBoxWidth;
                // dump("yo");

                $boxContainPerRow = $selectedTruckWidth / $boxWidth;
            } else if (($boxWidth <= $truckWidth && $boxLength <= $truckWidth) && ($boxWidth <= $truckLength && $boxLength <= $truckLength) && $boxLength != $boxWidth) {
                $ifBoxCanFit = true;
                // dump("yo-1");

                $boxContainPerRowForWidth = $selectedTruckWidth / $boxWidth;
                if (is_float($boxContainPerRowForWidth)) {
                    $arr = explode('.', $boxContainPerRowForWidth);
                    $boxContainPerRowForWidth = $arr[0];
                }
                // dump($boxContainPerRowForWidth);
                $boxContainPerRowForLength = $selectedTruckWidth / $boxLength;
                if (is_float($boxContainPerRowForLength)) {
                    $arr = explode('.', $boxContainPerRowForLength);
                    $boxContainPerRowForLength = $arr[0];
                }
                // dump($boxContainPerRowForLength);
                $fillableBoxQuantityForEachTruckForWidth = intval($truckLength / $boxLength) * $boxContainPerRowForWidth;
                $fillableBoxQuantityForEachTruckForLength = intval($truckLength / $boxWidth) * $boxContainPerRowForLength;
                // dump($fillableBoxQuantityForEachTruckForWidth);
                // dump($fillableBoxQuantityForEachTruckForLength);

                if ($fillableBoxQuantityForEachTruckForWidth >= $fillableBoxQuantityForEachTruckForLength) {
                    $boxContainPerRow = $boxContainPerRowForWidth;
                } else {
                    $boxContainPerRow = $boxContainPerRowForLength;
                    $tmpBoxWidth = $boxLength;
                    $boxLength = $boxWidth;
                    $boxWidth = $tmpBoxWidth;
                }
            } else if (($boxWidth <= $truckWidth && $boxLength > $truckWidth && $boxLength <= $truckLength) || ($boxWidth <= $truckWidth && $boxWidth > $truckLength && $boxLength <= $truckLength) || ($boxWidth <= $truckWidth && $boxLength <= $truckLength && $boxWidth == $boxLength) || ($boxWidth <= $truckWidth && $boxLength <= $truckLength)) {
                $ifBoxCanFit = true;
                // dump("yo-2");
                // dump($boxWidth);
                // dump($truckWidth);

                $boxContainPerRow = $selectedTruckWidth / $boxWidth;
            }
            // dump($boxLength);
            // dump($boxWidth);
            // dump($boxContainPerRow);

            if ($ifBoxCanFit) {
                if (is_float($boxContainPerRow)) {
                    $arr = explode('.', $boxContainPerRow);
                    $boxContainPerRow = $arr[0];
                }
                // dump("selectedTruckWidth $selectedTruckWidth : boxWidth $boxWidth : boxContainPerRow $boxContainPerRow");
                $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;
                // $emptySpacePerRow = $selectedTruckWidth - ($boxWidth * $boxContainPerRow);
                $emptySpacePerRow = (($selectedTruckWidth - ($boxWidth * $boxContainPerRow)) > 0) ? $selectedTruckWidth - ($boxWidth * $boxContainPerRow) : 0;

                // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowNeededForContainingBox");

                if (is_float($totalRowNeededForContainingBox)) {
                    $totalRowNeededForContainingBox = intval($totalRowNeededForContainingBox) + 1;
                }
                // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowNeededForContainingBox");

                // if ((intval($truckLength / $boxLength) * $boxContainPerRow) <= 0) {
                //     dump("selectedTruckType : $selectedTruckType");
                //     dump($truckLength);
                //     dump($boxLength);
                //     dd($boxContainPerRow);
                // }
                $totalTruck = $boxQuantity / (intval($truckLength / $boxLength) * $boxContainPerRow);
                if (is_float($totalTruck)) {
                    $totalTruck = intval($totalTruck) + 1;
                }

                $truckInfo[$count] = [
                    "truck" => $selectedTruckType,
                    "total_truck" => $totalTruck,
                    "truck_dimension" => $truckDimension,
                    "box_dimension" => $boxDimension,
                    "used_box_dimension" => $boxLength . "*" . $boxWidth . "*" . $boxHeight,
                    "empty_space_per_row" => $emptySpacePerRow,
                    "empty_space_of_last_filled_row" => null,
                    "box_contain_per_row" => $boxContainPerRow,
                    "total_row_for_containing_box" => $totalRowNeededForContainingBox,
                    "total_box_length" => $totalRowNeededForContainingBox * $boxLength,
                    "total_box_quantity" => $boxQuantity,
                    "fillable_box_quantity_in_each_truck" => intval($truckLength / $boxLength) * $boxContainPerRow,
                    "fillable_row_in_each_truck" => intval($truckLength / $boxLength),
                ];

                for ($i = 1; $i <= $truckInfo[$count]['total_truck']; $i++) {
                    if ($i == $truckInfo[$count]['total_truck']) {
                        if ($truckInfo[$count]['total_truck'] == 1) {
                            $truckFilledBoxQuantity = ($truckInfo[$count]['fillable_box_quantity_in_each_truck'] > $boxQuantity) ? $boxQuantity : $truckInfo[$count]['fillable_box_quantity_in_each_truck'];
                            // $truckFilledBoxQuantity = $truckInfo[$count]['total_box_quantity'] - $truckInfo[$count]['fillable_box_quantity_in_each_truck'];
                        } else {
                            $truckFilledBoxQuantity = $truckInfo[$count]['total_box_quantity'] - (($truckInfo[$count]['total_truck'] - 1) * $truckInfo[$count]['fillable_box_quantity_in_each_truck']);
                        }
                        $tempOccupiedRow = $truckFilledBoxQuantity / $truckInfo[$count]['box_contain_per_row'];
                        $tempOccupiedRow = is_float($tempOccupiedRow) ? intval($tempOccupiedRow) + 1 : $tempOccupiedRow;
                        $tempLastRowFilledQuantity = ($tempOccupiedRow == 1) ? $truckFilledBoxQuantity : $truckFilledBoxQuantity - (($tempOccupiedRow - 1) * $truckInfo[$count]['box_contain_per_row']);
                        $emptySpaceOfLastFilledRow  = floatval($truckWidth) - floatval($tempLastRowFilledQuantity * floatval($boxWidth));
                        $emptySpaceOfLastFilledRow  = ($emptySpaceOfLastFilledRow > 0) ? $emptySpaceOfLastFilledRow : 0;
                        // $emptySpaceOfLastFilledRow  = ($truckInfo[$count]['box_contain_per_row'] - $tempLastRowFilledQuantity) * $boxWidth;
                        // dd($emptySpaceOfLastFilledRow);
                        $truckInfo[$count]["empty_space_of_last_filled_row"] = $emptySpaceOfLastFilledRow;
                    } else {
                        $truckFilledBoxQuantity = $truckInfo[$count]['fillable_box_quantity_in_each_truck'];
                    }
                    $truckOccupiedRow = $truckFilledBoxQuantity / $truckInfo[$count]['box_contain_per_row'];
                    $truckOccupiedRow = is_float($truckOccupiedRow) ? intval($truckOccupiedRow) + 1 : $truckOccupiedRow;
                    $truckOccupiedLength = $truckOccupiedRow * $boxLength;
                    $truckUnoccupiedLength = $truckLength - $truckOccupiedLength;

                    $truckInfo[$count]["individual_truck"][] = [
                        "truck" => $selectedTruckType,
                        "truck_dimension" => $truckDimension,
                        "box_dimension" => $boxDimension,
                        "used_box_dimension" => $boxLength . "*" . $boxWidth . "*" . $boxHeight,
                        "box_contain_per_row" => $boxContainPerRow,
                        // "empty_space_per_row" => $emptySpacePerRow,
                        "empty_space_by_length" => $truckUnoccupiedLength,
                        "total_box_quantity" => ($i == 0) ? $boxQuantity : (($tmpBoxQuantity <= 0) ? $truckFilledBoxQuantity : $tmpBoxQuantity),
                        // "remaining_box_quantity" => $boxQuantity - $truckFilledBoxQuantity,
                        "total_filled_box_quantity" => $truckFilledBoxQuantity,
                        "fillable_row_in_each_truck" => intval($truckLength / $boxLength)
                    ];

                    // dump($tmpBoxQuantity);
                    // dump($truckFilledBoxQuantity);
                    $tmpBoxQuantity -= $truckFilledBoxQuantity;
                    // dump($tmpBoxQuantity);
                }
                // dump($truckInfo[$count]);
            }
        }

        // dump($boxQuantity);
        dump($truckInfo);

        foreach ($truckInfo as $truckData) {
            $totalTruck = $truckData["total_truck"];
            $totalBoxQuantity = $truckData["total_box_quantity"];
            $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
            // if ($totalTruck < $lowestTotalTruck && $fillableBoxQuantityInEachTruck > $highestFillableBoxInEachTruck) {
            if ($totalTruck < $lowestTotalTruck) {
                $lowestTotalTruck = $totalTruck;
                $highestFillableBoxInEachTruck = $fillableBoxQuantityInEachTruck;
            }
        }

        $filteredTruckInfo = [];

        foreach ($truckInfo as $truckData) {
            // if ($truckData["total_truck"] == $lowestTotalTruck && $truckData["fillable_box_quantity_in_each_truck"] == $highestFillableBoxInEachTruck) {
            if ($truckData["total_truck"] == $lowestTotalTruck) {
                $filteredTruckInfo[] = $truckData;
            }
        }

        dump($filteredTruckInfo);
        return $filteredTruckInfo;
    }

    public function getData1(Request $request)
    {
        $cargo_id = $request->cargo_id;
        // dd($cargo_id);
        // return response()->json(['status' => 200]);
        // Retrieve cargo information
        $cargo = Cargo::find($cargo_id);
        // $cargoInfo = $cargo->CargoInformation;
        $cargoInfo = CargoInformation::where('cargo_id', $cargo_id)->get()->toArray();
        // dd($cargoInfo);

        // Retrieve available trucks
        // $trucks = Trucks::select("*")->get()->toArray();
        $trucks = Trucks::select("*")->get();

        $uniqueTrucks = collect();

        // Create an array to keep track of the calculated values.
        $calculatedValues = [];

        foreach ($trucks as $truck) {
            $width = $truck->width;
            $length = $truck->length;
            $calculatedValue = $width * $length;

            // Check if the calculated value is already in the array.
            if (!in_array($calculatedValue, $calculatedValues)) {
                // If it's not in the array, add it and add the truck to the uniqueTrucks collection.
                $calculatedValues[] = $calculatedValue;
                $uniqueTrucks->push($truck);
            }
        }

        // $uniqueTrucks now contains unique trucks based on the width * length value.
        // dd($uniqueTrucks);
        $uniqueTrucksArray = $uniqueTrucks->toArray();

        // Initialize variables
        $this->consolidatedCargo = [];
        $this->remainingCargo = [];
        $this->truckCargoInfoAfterLoad = [];
        $this->truckBoxContainCapacity = [];
        // dump($cargoInfo);

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        $dimensions = [];
        $quantities = [];

        foreach ($cargoInfo as $key => $cargo) {
            $dim = explode('*', $cargo['box_dimension']);
            $volume = $dim[0] * $dim[1];
            $dimensions[$key] = $volume;
            $quantities[$key] = $cargo['quantity'];
        }
        array_multisort($dimensions, SORT_DESC, $quantities, SORT_DESC, $cargoInfo);

        // usort($cargoInfo, function ($a, $b) {
        //     $dimA = explode('*', $a['box_dimension']);
        //     $dimB = explode('*', $b['box_dimension']);
        //     $volA = $dimA[0] * $dimA[1];
        //     $volB = $dimB[0] * $dimB[1];

        //     // Debugging
        //     var_dump($volA, $volB); // Output volume values for debugging

        //     if ($volA === $volB) {
        //         return $b['quantity'] - $a['quantity'];
        //     }
        //     return $volB - $volA;
        // });

        // dd($cargoInfo);

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        usort($uniqueTrucksArray, function ($a, $b) {
            $volA = $a['length'] * $a['width'];
            $volB = $b['length'] * $b['width'];
            if ($volA === $volB) {
                return $b['max_weight'] - $a['max_weight'];
            }
            return $volB - $volA;
        });

        // dd($uniqueTrucksArray);


        $truckInfo = $filteredTruckInfo = $chosenTrucks = [];
        $boxTotalVolumeWithoutHeight = [];
        $minValueTruckType = $totalBoxLength = $totalRowForContainingBox =  $emptySpacePerRow = null;

        $smallestValue = PHP_INT_MAX; // Initialize to a high value.
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $highestFillableBoxInEachTruck = PHP_INT_MIN;
        $minDiff = PHP_INT_MAX;
        $maxDiff = PHP_INT_MAX;
        $closestMin = null;
        $closestMax = null;

        foreach ($cargoInfo as $cargokey => $box) {
            // $this->truckBoxContainCapacity = [];
            dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            // $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxVolumeWithoutHeight = $boxDim[0] * $boxDim[1];
            $boxQuantity = $box['quantity'];
            // dump($boxVolume);

            $boxLength = $boxDim[0];
            $boxWidth = $boxDim[1];

            $boxTotalVolumeWithoutHeight[] = $boxVolumeWithoutHeight * $boxQuantity;

            if (array_key_exists(0, $filteredTruckInfo)) {
                $minDifference = PHP_INT_MAX;
                $maxDifference = PHP_INT_MIN;
                $minDifferenceKey = PHP_INT_MIN;
                foreach ($filteredTruckInfo as $key => $truckData) {
                    // check if the empty width is greater than the new box width and if it is than store the storeable boxes
                    if ($truckData['empty_space_per_row'] >= $boxWidth) {
                        $dimension = explode('*', $truckData['truck_dimension']);
                        $fillableLengthInTruck = $dimension[0] / $boxLength;
                        $boxLengthNeedsToBeFilled = $boxLength * $boxQuantity;
                        if ($fillableLengthInTruck > $boxLengthNeedsToBeFilled) {
                            $fillDifference = $fillableLengthInTruck - $boxLengthNeedsToBeFilled;
                            // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
                            if ($fillDifference < $minDifference) {
                                $minDifference = $fillDifference;
                                $minDifferenceKey = $key;
                            }
                        } else {
                            $fillDifference = $fillableLengthInTruck;
                            if ($fillDifference > $maxDifference) {
                                $maxDifference = $fillDifference;
                                $minDifferenceKey = $key;
                            }
                        }
                    }
                }

                // dump($boxQuantity);

                // dd($minDifferenceKey);
                if ($minDifferenceKey >= 0) { // when truck empty width > new box width
                    for ($i = 1; $i <= $filteredTruckInfo[$minDifferenceKey]['total_truck']; $i++) {
                        if ($i == $filteredTruckInfo[$minDifferenceKey]['total_truck']) {
                            if (($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] * $filteredTruckInfo[$minDifferenceKey]['total_truck']) > $filteredTruckInfo[$minDifferenceKey]['total_box_quantity']) {
                                $lastTruckFilledBoxQuantity = $filteredTruckInfo[$minDifferenceKey]['total_box_quantity'] - (($filteredTruckInfo[$minDifferenceKey]['total_truck'] - 1) * $filteredTruckInfo[$minDifferenceKey]['fillable_row_in_each_truck']);
                                $boxDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['box_dimension']);
                                $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                                // dump($lastTruckFilledBoxQuantity);
                                $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $filteredTruckInfo[$minDifferenceKey]['box_contain_per_row'];
                                // dump($lastTruckOccupiedRow);
                                $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                                // dump($lastTruckOccupiedLength);
                                $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                                // $lastTruckUnoccupiedLength = ($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] - $lastTruckOccupiedRow) * $boxDimension[0];
                                // dump($lastTruckUnoccupiedLength);

                                $boxContainPerRowInEmptySpace = intval($filteredTruckInfo[$minDifferenceKey]['empty_space_per_row'] / $boxWidth);
                                $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                                $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                                // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                                $boxQuantity -= $filledQuantity;

                                if ($lastTruckUnoccupiedLength >= $boxLength) {
                                    // dump("lastTruckUnoccupiedLength : $lastTruckUnoccupiedLength , boxLength : $boxLength");
                                    $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                                    // dump($boxContainPerRow);
                                    $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                                    // dump($totalRowForContainingBox);
                                    $totalBoxLength += $totalRowNeededForContainingBox * $boxLength;
                                    // dump($totalBoxLength);

                                    $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                                    // dump($availableTotalNoOfRow);
                                    if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                                        $filledQuantity = $totalRowNeededForContainingBox *  $boxContainPerRow;
                                        // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                                        $boxQuantity -= $filledQuantity;
                                        // dump("boxQuantity : $boxQuantity");
                                    } else {
                                        $filledQuantity = $availableTotalNoOfRow *  $boxContainPerRow;
                                        // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                                        $boxQuantity -= $filledQuantity;
                                        // dump("boxQuantity : $boxQuantity");
                                    }
                                }
                            }
                        } else {
                            $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                            $boxContainPerRowInEmptySpace = intval($filteredTruckInfo[$minDifferenceKey]['empty_space_per_row'] / $boxWidth);
                            $totalNoOfRow = intval($truckDimension[0] / $boxLength);
                            $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                            // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                            $boxQuantity -= $filledQuantity;
                        }
                        // dump($boxQuantity);
                    }
                    // if (($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] * $filteredTruckInfo[$minDifferenceKey]['total_truck']) > $filteredTruckInfo[$minDifferenceKey]['total_box_quantity']) {
                    //     $lastTruckFilledBoxQuantity = $filteredTruckInfo[$minDifferenceKey]['total_box_quantity'] - (($filteredTruckInfo[$minDifferenceKey]['total_truck'] - 1) * $filteredTruckInfo[$minDifferenceKey]['fillable_row_in_each_truck']);
                    //     $boxDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['box_dimension']);
                    //     $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                    //     // dump($lastTruckFilledBoxQuantity);
                    //     $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $filteredTruckInfo[$minDifferenceKey]['box_contain_per_row'];
                    //     // dump($lastTruckOccupiedRow);
                    //     $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                    //     // dump($lastTruckOccupiedLength);
                    //     $lastTruckUnoccupiedLength = ($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] - $lastTruckOccupiedRow) * $boxDimension[0];
                    //     // dump($lastTruckUnoccupiedLength);

                    //     if ($lastTruckUnoccupiedLength >= $boxLength) {
                    //         $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                    //         dump($boxContainPerRow);
                    //         $totalRowForContainingBox = $boxQuantity / $boxContainPerRow;
                    //         dump($totalRowForContainingBox);
                    //         $totalBoxLength += $totalRowForContainingBox * $boxLength;
                    //         dump($totalBoxLength);
                    //         $emptySpacePerRow = $truckDimension[1] - ($boxWidth * $boxContainPerRow);
                    //         dump($emptySpacePerRow);

                    //         $totalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                    //         $filledQuantity = $totalNoOfRow *  $boxContainPerRow;
                    //         $boxQuantity -= $filledQuantity;
                    //     }
                    // }
                    // $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                    // $boxContainPerRowInEmptySpace = intval($filteredTruckInfo[$minDifferenceKey]['empty_space_per_row'] / $boxWidth);
                    // $totalNoOfRow = intval($truckDimension[0] / $boxLength);
                    // $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace * $filteredTruckInfo[$minDifferenceKey]['total_truck'];
                    // $boxQuantity -= $filledQuantity;
                    // // dump("totalNoOfRow : $totalNoOfRow , boxContainPerRowInEmptySpace : $boxContainPerRowInEmptySpace , filledQuantity: $filledQuantity , remainingQuantity: $boxQuantity");
                    dump($filteredTruckInfo[$minDifferenceKey]);
                    $chosenTrucks [] = $filteredTruckInfo[$minDifferenceKey];
                } else { // when truck empty width < new box width
                    dump("working on logic");
                    // dump($boxQuantity);
                    // needs to fill the logic here
                    $minDifference = PHP_INT_MAX;
                    $maxDifference = PHP_INT_MIN;
                    $minDifferenceKey = PHP_INT_MIN;
                    foreach ($filteredTruckInfo as $key => $truckData) {
                        $truckDimension = explode('*', $truckData['truck_dimension']);
                        $boxDimension = explode('*', $truckData['box_dimension']);
                        $fillableLengthInTruck = $truckData['fillable_row_in_each_truck'] * $boxDimension[0];
                        $boxLengthNeedsToBeFilled = $boxDimension[0] * $truckData['total_box_quantity'];
                        // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
                        if ($fillableLengthInTruck > $boxLengthNeedsToBeFilled) {
                            $fillDifference = $fillableLengthInTruck - $boxLengthNeedsToBeFilled;
                            // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
                            if ($fillDifference < $minDifference) {
                                $minDifference = $fillDifference;
                                $minDifferenceKey = $key;
                            }
                        } else {
                            $fillDifference = $fillableLengthInTruck;
                            if ($fillDifference > $maxDifference) {
                                $maxDifference = $fillDifference;
                                $minDifferenceKey = $key;
                            }
                        }
                    }
                    $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                    if (($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] * $filteredTruckInfo[$minDifferenceKey]['total_truck']) > $filteredTruckInfo[$minDifferenceKey]['total_box_quantity']) {
                        $lastTruckFilledBoxQuantity = $filteredTruckInfo[$minDifferenceKey]['total_box_quantity'] - (($filteredTruckInfo[$minDifferenceKey]['total_truck'] - 1) * $filteredTruckInfo[$minDifferenceKey]['fillable_row_in_each_truck']);
                        // dump($lastTruckFilledBoxQuantity);
                        $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $filteredTruckInfo[$minDifferenceKey]['box_contain_per_row'];
                        // dump($lastTruckOccupiedRow);
                        $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                        // dump($lastTruckOccupiedLength);
                        $lastTruckUnoccupiedLength = ($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] - $lastTruckOccupiedRow) * $boxDimension[0];
                        // dump($lastTruckUnoccupiedLength);

                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength += $totalRowForContainingBox * $boxLength;
                        $emptySpacePerRow = $truckDimension[1] - ($boxWidth * $boxContainPerRow);
                        // dump($emptySpacePerRow);
                        // dump($boxQuantity);

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        // dump($availableTotalNoOfRow);
                        if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                            $filledQuantity = $totalRowNeededForContainingBox *  $boxContainPerRow;
                            // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                            $boxQuantity -= $filledQuantity;
                            // dump("boxQuantity : $boxQuantity");
                        } else {
                            $filledQuantity = $availableTotalNoOfRow *  $boxContainPerRow;
                            // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                            $boxQuantity -= $filledQuantity;
                            // dump("boxQuantity : $boxQuantity");
                        }
                        // dump($boxQuantity);
                    }


                    // $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                    // $totalRowForContainingBox = $boxQuantity / $boxContainPerRow;
                    // $totalBoxLength += $totalRowForContainingBox * $boxLength;
                    // $emptySpacePerRow = $selectedTruckWidth - ($boxWidth * $boxContainPerRow);

                    // if (is_float($totalRowForContainingBox)) {
                    //     $totalRowForContainingBox = intval($totalRowForContainingBox) + 1;
                    // }

                    // $totalTruck = ($totalRowForContainingBox * $boxLength) / $truckLength;
                    // if (is_float($totalTruck)) {
                    //     $totalTruck = intval($totalTruck) + 1;
                    // }


                    // $boxContainPerRowInEmptySpace = intval($filteredTruckInfo[$minDifferenceKey]['empty_space_per_row'] / $boxWidth);
                    // $totalNoOfRow = intval($truckDimension[0] / $boxLength);
                    // $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace * $filteredTruckInfo[$minDifferenceKey]['total_truck'];
                    // $boxQuantity -= $filledQuantity;
                    dump($filteredTruckInfo[$minDifferenceKey]);
                    $chosenTrucks [] = $filteredTruckInfo[$minDifferenceKey];
                }
            }

            // if ($emptySpacePerRow != null && $emptySpacePerRow >= $boxWidth) {
            //     $boxContainPerRowInEmptySpace = intval($emptySpacePerRow / $boxWidth);
            //     $filledQuantity = $boxContainPerRowInEmptySpace * $totalRowForContainingBox;
            //     $boxQuantity -= $filledQuantity;
            // }

            // dump($boxQuantity);

            if ($boxQuantity == 0) {
                continue;
            } else {
                $truckInfo = [];
                foreach ($uniqueTrucksArray as $item) {
                    $truckLength = $item['length'];
                    $truckWidth = $item['width'];
                    $truckDimension = $truckLength . "*" . $truckWidth . "*" . $item['height'];

                    // if($closestMin == null) {
                    //     if ($boxWidth <= $truckWidth) {
                    //         $minDiffCurrent = $truckWidth - $boxWidth;
                    //         if ($minDiffCurrent < $minDiff) {
                    //             $minDiff = $minDiffCurrent;
                    //             $closestMin = $truckWidth;
                    //             $minValueTruckType = $item["truck_type"];
                    //             $minValueTruckDimension = $truckDimension;
                    //             // $minValueTruckVolume = $item["truck_volume"];
                    //             // dump("1");
                    //         }
                    //     }
                    // }

                    if ($boxWidth <= $truckWidth) {
                        $selectedTruckWidth = $truckWidth;
                        $selectedTruckType = $item["truck_type"];

                        $boxContainPerRow = intval($selectedTruckWidth / $boxWidth);
                        $totalRowForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength += $totalRowForContainingBox * $boxLength;
                        $emptySpacePerRow = $selectedTruckWidth - ($boxWidth * $boxContainPerRow);

                        // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowForContainingBox");

                        if (is_float($totalRowForContainingBox)) {
                            $totalRowForContainingBox = intval($totalRowForContainingBox) + 1;
                        }
                        // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowForContainingBox");

                        $totalTruck = ($totalRowForContainingBox * $boxLength) / $truckLength;
                        if (is_float($totalTruck)) {
                            $totalTruck = intval($totalTruck) + 1;
                        }

                        $truckInfo[] = [
                            "truck" => $selectedTruckType,
                            "total_truck" => $totalTruck,
                            "truck_dimension" => $truckDimension,
                            "box_dimension" => $box['box_dimension'],
                            "empty_space_per_row" => $emptySpacePerRow,
                            "box_contain_per_row" => $boxContainPerRow,
                            "total_row_for_containing_box" => $totalRowForContainingBox,
                            "total_box_length" => $totalRowForContainingBox * $boxLength,
                            "total_box_quantity" => $boxQuantity,
                            "fillable_box_quantity_in_each_truck" => intval($truckLength / $boxLength) * $boxContainPerRow,
                            "fillable_row_in_each_truck" => intval($truckLength / $boxLength)
                        ];
                    }

                    // if ($boxWidth >= $truckWidth) {
                    //     $maxDiffCurrent = $boxWidth - $truckWidth;
                    //     if ($maxDiffCurrent < $maxDiff) {
                    //         $maxDiff = $maxDiffCurrent;
                    //         $closestMax = $truckWidth;
                    //         $maxValueTruckType = $item["truck_type"];
                    //         // $maxValueTruckDimension = $item["truck_dimension"];
                    //         // $maxValueTruckVolume = $item["truck_volume"];
                    //         // $maxValueBoxContainCapacity = $item["box_contain_capacity"];
                    //     }
                    // }
                }

                // dump($boxQuantity);
                dump($truckInfo);

                foreach ($truckInfo as $truckData) {
                    $totalTruck = $truckData["total_truck"];
                    $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
                    // if ($totalTruck < $lowestTotalTruck && $fillableBoxQuantityInEachTruck > $highestFillableBoxInEachTruck) {
                    if ($totalTruck < $lowestTotalTruck) {
                        $lowestTotalTruck = $totalTruck;
                        $highestFillableBoxInEachTruck = $fillableBoxQuantityInEachTruck;
                    }
                }

                $filteredTruckInfo = [];

                foreach ($truckInfo as $truckData) {
                    // if ($truckData["total_truck"] == $lowestTotalTruck && $truckData["fillable_box_quantity_in_each_truck"] == $highestFillableBoxInEachTruck) {
                    if ($truckData["total_truck"] == $lowestTotalTruck) {
                        $filteredTruckInfo[] = $truckData;
                    }
                }

                dump($filteredTruckInfo);

                // if (!Arr::exists($cargoInfo, ++$cargokey)) {
                if (!array_key_exists(++$cargokey, $cargoInfo)) {
                    // dd("yo");
                    $smallestKey = null;

                    foreach ($filteredTruckInfo as $key => $truckData) {
                        $dimension = explode('*', $truckData['truck_dimension']);
                        // $calculatedValue = $truckData["total_truck"] * $dimension[0] - $truckData["total_box_length"];
                        $calculatedValue = $truckData["fillable_box_quantity_in_each_truck"] - $truckData["total_box_quantity"];
                        dump($calculatedValue);
                        if ($calculatedValue < $smallestValue) {
                            $smallestValue = $calculatedValue;
                            $smallestKey = $key;
                        }
                    }

                    $smallestArrayElement = $filteredTruckInfo[$smallestKey];
                    dump("last box");
                    dump($smallestArrayElement);
                    $chosenTrucks [] = $smallestArrayElement;
                }
                // dump($filteredTruckInfo);

                // if ($emptySpacePerRow != null && $emptySpacePerRow >= $boxWidth) {
                //     $boxContainPerRowInEmptySpace = intval($emptySpacePerRow / $boxWidth);
                //     $filledQuantity = $boxContainPerRowInEmptySpace * $totalRowForContainingBox;
                //     $boxQuantity -= $filledQuantity;
                // }

                // if ($minValueTruckType != null) {
                //     $boxContainPerRow = intval($closestMin / $boxWidth);
                //     $totalRowForContainingBox = $boxQuantity / $boxContainPerRow;
                //     $totalBoxLength += $totalRowForContainingBox * $boxLength;
                //     $emptySpacePerRow = $closestMin - ($boxWidth * $boxContainPerRow);

                //     if (is_float($totalRowForContainingBox)) {
                //         $totalRowForContainingBox = intval($totalRowForContainingBox) + 1;
                //     }

                //     dump("box length*width: $boxLength*$boxWidth Quantity: $boxQuantity");
                //     dump("$minValueTruckType : $minValueTruckDimension");
                //     dump("truck width: $closestMin");
                //     dump("boxContainPerRow: $boxContainPerRow");
                //     dump("emptySpacePerRow: $emptySpacePerRow");
                //     dump("totalRowForContainingBox: $totalRowForContainingBox");
                //     dump("box total length: " . $totalRowForContainingBox * $boxLength);
                // }

                // dump("box length*width: $boxLength*$boxWidth Quantity: $boxQuantity");
                // dump("$minValueTruckType : $minValueTruckDimension");
                // dump("truck width: $closestMin");
                // dump("boxContainPerRow: $boxContainPerRow");
                // dump("totalRowForContainingBox: $totalRowForContainingBox");
                // dump("box total length: " . $totalRowForContainingBox*$boxLength);



                // dump($closestMax);
                // dump($maxValueTruckType);
                // dump($maxValueBoxContainCapacity);
                // dd("");
            }
        }

        // dump($totalBoxLength);
        dd($chosenTrucks);

        $this->totalBoxVolumeWithoutHeight = array_reduce($boxTotalVolumeWithoutHeight, function ($carry, $item) {
            return $carry + $item;
        }, 1);

        // Call the function for the initial cargo
        $this->assignBoxesToTrucks($trucks);

        // dump($this->remainingCargo);

        // If there's remaining cargo, call the function again
        while ($this->totalBoxVolumeWithoutHeight > 0) {
            // dd("Remaining cargo not empty!");
            $this->assignBoxesToTrucks($trucks);
        }

        $result = [
            'status' => 200,
            'consolidatedCargo' => $this->consolidatedCargo,
        ];

        // dump($result);

        // dd("finish for now");

        dd($result);

        // $this->consolidatedCargo now contains cargo boxes, their corresponding trucks, and quantities
        // $this->remainingCargo has been assigned to other available trucks

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
    }

    public function getOptimizedData(Request $request)
    {
        $cargo_id = $request->cargo_id;
        // dd($cargo_id);
        // return response()->json(['status' => 200]);
        // Retrieve cargo information
        $cargo = Cargo::find($cargo_id);
        // $cargoInfo = $cargo->CargoInformation;
        $cargoInfo = CargoInformation::where('cargo_id', $cargo_id)->get()->toArray();
        // dd($cargoInfo);

        // Retrieve available trucks
        $trucks = Trucks::all();

        // Initialize variables
        $this->consolidatedCargo = [];
        $this->remainingCargo = [];
        $this->truckCargoInfoAfterLoad = [];
        $this->truckBoxContainCapacity = [];
        // dump($cargoInfo);

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        usort($cargoInfo, function ($a, $b) {
            $dimA = explode('*', $a['box_dimension']);
            $dimB = explode('*', $b['box_dimension']);
            $volA = $dimA[0] * $dimA[1] * $dimA[2];
            $volB = $dimB[0] * $dimB[1] * $dimB[2];
            if ($volA === $volB) {
                return $b['quantity'] - $a['quantity'];
            }
            return $volB - $volA;
        });

        // dd($cargoInfo);


        $boxTotalVolumeWithoutHeight = [];

        foreach ($cargoInfo as $box) {
            // $this->truckBoxContainCapacity = [];
            // dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            // $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxVolumeWithoutHeight = $boxDim[0] * $boxDim[1];
            $boxQuantity = $box['quantity'];
            // dump($boxVolume);

            $boxTotalVolumeWithoutHeight[] = $boxVolumeWithoutHeight * $boxQuantity;
        }

        $this->totalBoxVolumeWithoutHeight = array_reduce($boxTotalVolumeWithoutHeight, function ($carry, $item) {
            return $carry + $item;
        }, 1);

        // Call the function for the initial cargo
        $this->assignBoxesToTrucks($trucks);

        // dump($this->remainingCargo);

        // If there's remaining cargo, call the function again
        while ($this->totalBoxVolumeWithoutHeight > 0) {
            // dd("Remaining cargo not empty!");
            $this->assignBoxesToTrucks($trucks);
        }

        $result = [
            'status' => 200,
            'consolidatedCargo' => $this->consolidatedCargo,
        ];

        // dump($result);

        // dd("finish for now");

        dd($result);

        // $this->consolidatedCargo now contains cargo boxes, their corresponding trucks, and quantities
        // $this->remainingCargo has been assigned to other available trucks

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
    }

    private function assignBoxesToTrucks($trucks)
    {
        foreach ($trucks as $truck) {
            // $truckVolume = $truck->length * $truck->width * $truck->height;
            $truckVolumeWithoutHeight = $truck->length * $truck->width;
            $truck_dimension = $truck->length . "*" . $truck->width . "*" . $truck->height;
            // dump($truck->truck_type);
            // dump($truckVolume);

            // Calculate how many boxes can fit in the truck, considering quantity
            $maxBoxes = floor($truckVolumeWithoutHeight / $this->totalBoxVolumeWithoutHeight);
            // dump($maxBoxes);

            $this->truckBoxContainCapacity[] = [
                "truck_type" => $truck->truck_type,
                "truck_dimension" => $truck_dimension,
                "truck_volume" => $truckVolumeWithoutHeight,
                "box_contain_capacity" => $maxBoxes,
            ];
        }

        $minDiff = PHP_INT_MAX;
        $maxDiff = PHP_INT_MAX;
        $closestMin = null;
        $closestMax = null;

        foreach ($this->truckBoxContainCapacity as $item) {
            $capacity = $item["truck_volume"];

            if ($capacity <= $this->totalBoxVolumeWithoutHeight) {
                $minDiffCurrent = $this->totalBoxVolumeWithoutHeight - $capacity;
                if ($minDiffCurrent < $minDiff) {
                    $minDiff = $minDiffCurrent;
                    $closestMin = $capacity;
                    $minValueTruckType = $item["truck_type"];
                    $minValueTruckDimension = $item["truck_dimension"];
                    $minValueTruckVolume = $item["truck_volume"];
                    $minValueBoxContainCapacity = $item["box_contain_capacity"];
                }
            }

            if ($capacity >= $this->totalBoxVolumeWithoutHeight) {
                $maxDiffCurrent = $capacity - $this->totalBoxVolumeWithoutHeight;
                if ($maxDiffCurrent < $maxDiff) {
                    $maxDiff = $maxDiffCurrent;
                    $closestMax = $capacity;
                    $maxValueTruckType = $item["truck_type"];
                    $maxValueTruckDimension = $item["truck_dimension"];
                    $maxValueTruckVolume = $item["truck_volume"];
                    $maxValueBoxContainCapacity = $item["box_contain_capacity"];
                }
            }
        }

        // dump($closestMin);
        // dump($closestMax);
        // // dump($maxValueBoxContainCapacity);
        // dd("");

        if (!empty($closestMin) && empty($closestMax)) {
            $boxContainCapacity = $minValueBoxContainCapacity;
            $truckType = $minValueTruckType;
            $truck_dimension = $minValueTruckDimension;
            $truck_volume = $minValueTruckVolume;
            // $remainingSpaceOnTruck = "";
            // // $totalLoadedBoxVolume = $boxVolume * $minValueBoxContainCapacity;
            // $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
            // $loadedBoxQuantity = $boxContainCapacity;
        } elseif ((empty($closestMin) && !empty($closestMax))) {
            $boxContainCapacity = $maxValueBoxContainCapacity;
            $truckType = $maxValueTruckType;
            $truck_dimension = $maxValueTruckDimension;
            $truck_volume = $maxValueTruckVolume;
            // // $totalLoadedBoxVolume = $boxVolume * $maxValueBoxContainCapacity;
            // $remainingboxQuantity = 0;
            // $loadedBoxQuantity = $boxQuantity;
        } elseif (!empty($closestMin) && !empty($closestMax)) {
            $boxContainCapacity = $maxValueBoxContainCapacity;
                $truckType = $maxValueTruckType;
                $truck_dimension = $maxValueTruckDimension;
                $truck_volume = $maxValueTruckVolume;
                // $remainingboxQuantity = 0;
                // $loadedBoxQuantity = $boxQuantity;
        }
        // dd("");

        $bestFittingTruck = $truckType;
        $maxBoxesToLoad = $boxContainCapacity;
        // dump($bestFittingTruck);
        // dump($maxBoxesToLoad);
        // dump($remainingboxQuantity);
        // // dump($remainingSpaceOnTruck);
        // dd("");

        // If a fitting truck is found, add the boxes to it; otherwise, save the boxes for later

        $this->consolidatedCargo[] = [
            'truck_type' => $bestFittingTruck,
            'truck_dimension' => $truck_dimension,
            'truck_volume' => $truck_volume,
            'total_box_volume' => $this->totalBoxVolumeWithoutHeight,
            // 'can_load_max_box_quantity' => $maxBoxesToLoad,
            // 'box_dimension' => $box['box_dimension'],
            // 'single_box_volume' => $boxVolume,
            // 'total_box_quantity' => $boxQuantity,
            // 'loaded_box_quantity' => $loadedBoxQuantity,
            // 'remaining_box_quantity' => $remainingboxQuantity,
            // 'loaded_box_volume' => $boxVolume * $loadedBoxQuantity,
            // 'remaining_space_on_truck' => $truck_volume - ($boxVolume * $loadedBoxQuantity),
        ];

        // if ($this->totalBoxVolumeWithoutHeight - $truck_volume > 0) {
        //     // Reduce the box quantity by the loaded quantity
        //     $this->totalBoxVolumeWithoutHeight -= $truck_volume;
        // }
        $this->totalBoxVolumeWithoutHeight -= $truck_volume;

        // dd($box['quantity']);

        // If there are remaining boxes of this type, save them for later
        // if ($this->totalBoxVolumeWithoutHeight > 0) {
        //     $this->assignRemainingCargoBoxesToTrucks($trucks);
        // } else {
        //     $this->remainingCargo = [];
        //     $this->truckCargoInfoAfterLoad = [];
        // }

        // dump($this->consolidatedCargo);
        // dump($this->remainingCargo);
    }
}
