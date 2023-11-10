<?php

namespace App\Http\Controllers\Admin\v1\Cargo\DistributeCargo;

use Exception;
use App\Models\Cargo\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Trucks\Trucks;
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

        $truckInfo = $filteredTruckInfo = $chosenTrucks = $cargoBoxLoadInfo = [];
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
                $filteredTruckInfoKey = $this->getFilteredTruckDataKey($filteredTruckInfo, $cargokey);
                dump($filteredTruckInfoKey);
                // dump($highestFiilableBoxQuantityInEachTruckKey);
                if (array_key_exists($filteredTruckInfoKey, $filteredTruckInfo)) {
                    $selectedTempTruck = $filteredTruckInfo[$filteredTruckInfoKey];
                    $truckDimension = explode('*', $selectedTempTruck['truck_dimension']);
                    $boxDimension = explode('*', $selectedTempTruck['box_dimension']);

                    $index = sizeof($cargoBoxLoadInfo);
                    dump($selectedTempTruck);

                    $cargoBoxLoadInfo[$index] = [
                        "truck" => $selectedTempTruck['truck'],
                        "truck_dimension" => $selectedTempTruck['truck_dimension'],
                        "truck_length" => $truckDimension[0],
                        "truck_width" => $truckDimension[1],
                        "box_dimension" => $selectedTempTruck['box_dimension'],
                        "box_length" => $boxDimension[0],
                        "box_width" => $boxDimension[1],
                        "empty_space_per_row" => $selectedTempTruck['empty_space_per_row'],
                        "box_contain_per_row" => $selectedTempTruck['box_contain_per_row'],
                        "total_row_for_containing_box" => $selectedTempTruck['total_row_for_containing_box'],
                        "total_box_length" => $selectedTempTruck['total_box_length'],
                        "total_box_quantity" => $selectedTempTruck['total_box_quantity'],
                        "fillable_box_quantity_in_each_truck" => $selectedTempTruck['fillable_box_quantity_in_each_truck'],
                        "fillable_row_in_each_truck" => $selectedTempTruck['fillable_row_in_each_truck'],
                    ];

                    $totalTruck = ($selectedTempTruck['total_row_for_containing_box'] * $boxDimension[0]) / $truckDimension[0];
                    if (is_float($totalTruck)) {
                        $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (intval($totalTruck) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                        $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
                        // dd($filteredTruckInfo);
                        $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                        $filteredTruckInfoKey = $this->getFilteredTruckDataKey($filteredTruckInfo, $cargokey);
                        dd($filteredTruckInfoKey);
                        // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                    }

                    // check if the empty width is greater than the new box width and if it is than store the storeable boxes
                    if ($selectedTempTruck['empty_space_per_row'] >= $boxWidth) {
                        $boxContainPerRowInEmptySpace = intval($selectedTempTruck['empty_space_per_row'] / $boxWidth);
                        $totalNoOfRow = intval($selectedTempTruck['total_box_length'] / $boxLength);
                        $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $boxQuantity -= $filledQuantity;

                        $cargoBoxLoadInfo[$index] = [
                            "other_box_in_empty_space" => [
                                "box_dimension" => $box['box_dimension'],
                                "box_length" => $dim[0],
                                "box_width" => $dim[1],
                                "box_contain_per_row" => $boxContainPerRowInEmptySpace,
                                "total_row_for_containing_box" => $totalNoOfRow,
                                // "total_filled_box_length" => $selectedTempTruck['total_box_length'],
                                "total_box_quantity" => $boxQuantity,
                                "fillable_box_quantity_in_each_truck_empty_space" => $filledQuantity,
                                "remaining_box_quantity" => $boxQuantity - $filledQuantity,
                                // "fillable_row_in_each_truck_empty_space" => $selectedTempTruck['fillable_row_in_each_truck'],
                            ]
                        ];
                    }
                }

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

                    $selectedTempTruck = $filteredTruckInfo[$smallestKey];
                    $truckDimension = explode('*', $selectedTempTruck['truck_dimension']);
                    $boxDimension = explode('*', $selectedTempTruck['box_dimension']);

                    $index = sizeof($cargoBoxLoadInfo);

                    $cargoBoxLoadInfo[$index] = [
                        "truck" => $selectedTempTruck['truck'],
                        "truck_dimension" => $selectedTempTruck['truck_dimension'],
                        "truck_length" => $truckDimension[0],
                        "truck_width" => $truckDimension[1],
                        "box_dimension" => $selectedTempTruck['box_dimension'],
                        "box_length" => $boxDimension[0],
                        "box_width" => $boxDimension[1],
                        "empty_space_per_row" => $selectedTempTruck['empty_space_per_row'],
                        "box_contain_per_row" => $selectedTempTruck['box_contain_per_row'],
                        "total_row_for_containing_box" => $selectedTempTruck['total_row_for_containing_box'],
                        "total_box_length" => $selectedTempTruck['total_box_length'],
                        "total_box_quantity" => $selectedTempTruck['total_box_quantity'],
                        "fillable_box_quantity_in_each_truck" => $selectedTempTruck['fillable_box_quantity_in_each_truck'],
                        "fillable_row_in_each_truck" => $selectedTempTruck['fillable_row_in_each_truck'],
                    ];
                    // $smallestArrayElement = $filteredTruckInfo[$smallestKey];
                    dump("last box");
                    // dump($smallestArrayElement);
                    // $chosenTrucks [] = $smallestArrayElement;
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
        dd($cargoBoxLoadInfo);

        // dd("finish for now");

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
    }

    private function getFilteredTruckDataKey($filteredTruckInfo, $cargoBoxkey)
    {
        $highestFiilableBoxQuantityInEachTruck = $highestFiilableBoxQuantityInEachTruckKey = null;
        $lowestFiilableBoxQuantityInEachTruck = $lowestFiilableBoxQuantityInEachTruckKey = null;
        $redFlag = null;
        foreach ($filteredTruckInfo as $key => $truckData) {
            $totalTruck = $truckData["total_truck"];
            $totalBoxQuantity = $truckData["total_box_quantity"];
            $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
            if ($fillableBoxQuantityInEachTruck <= $totalBoxQuantity && $fillableBoxQuantityInEachTruck > $highestFiilableBoxQuantityInEachTruck) {
                $highestFiilableBoxQuantityInEachTruck = $fillableBoxQuantityInEachTruck;
                $highestFiilableBoxQuantityInEachTruckKey = $key;
                // dump("uo $key");
            }
            $redFlag = ($fillableBoxQuantityInEachTruck > $totalBoxQuantity) ? true : false;
            // // check if the empty width is greater than the new box width and if it is than store the storeable boxes
            // if ($truckData['empty_space_per_row'] >= $boxWidth) {
            //     $dimension = explode('*', $truckData['truck_dimension']);
            //     $fillableLengthInTruck = $dimension[0] / $boxLength;
            //     $boxLengthNeedsToBeFilled = $boxLength * $boxQuantity;
            //     if ($fillableLengthInTruck > $boxLengthNeedsToBeFilled) {
            //         $fillDifference = $fillableLengthInTruck - $boxLengthNeedsToBeFilled;
            //         // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
            //         if ($fillDifference < $minDifference) {
            //             $minDifference = $fillDifference;
            //             $minDifferenceKey = $key;
            //         }
            //     } else {
            //         $fillDifference = $fillableLengthInTruck;
            //         if ($fillDifference > $maxDifference) {
            //             $maxDifference = $fillDifference;
            //             $minDifferenceKey = $key;
            //         }
            //     }
            // }
            // dd($highestFiilableBoxQuantityInEachTruckKey);
        }
        if ($redFlag == true) {
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
                        $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - (($item['total_truck'] - 1) * $item['fillable_box_quantity_in_each_truck']);
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
                        $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - $item['fillable_box_quantity_in_each_truck'];
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
                        "truckArrTempKey" => $tempKey,
                        "cargoArrTempKey" => "",
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
        }
        dd($filteredTruckInfo);
        foreach ($filteredTruckInfo as $truckData) {
            $boxDimension = explode('*', $item['box_dimension']);
            $truckDimension = explode('*', $item['truck_dimension']);
            $lastTruckFilledBoxQuantity = intval($truckDimension[0] / $boxDimension[0]) * $item['box_contain_per_row'];
            // $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - $item['fillable_box_quantity_in_each_truck'];
            $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
            $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
            $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
        }
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
        $boxQuantity = $boxQuantity;
        $boxLength = $boxDim[0];
        $boxWidth = $boxDim[1];
        // dump($boxVolume);

        foreach ($uniqueTrucksArray as $item) {
            $truckLength = $item['length'];
            $truckWidth = $item['width'];
            $truckDimension = $truckLength . "*" . $truckWidth . "*" . $item['height'];

            if ($boxWidth <= $truckWidth) {
                $selectedTruckWidth = $truckWidth;
                $selectedTruckType = $item["truck_type"];

                $boxContainPerRow = intval($selectedTruckWidth / $boxWidth);
                $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;
                $emptySpacePerRow = $selectedTruckWidth - ($boxWidth * $boxContainPerRow);

                // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowNeededForContainingBox");

                if (is_float($totalRowNeededForContainingBox)) {
                    $totalRowNeededForContainingBox = intval($totalRowNeededForContainingBox) + 1;
                }
                // dump("selectedTruckType : $selectedTruckType , totalRowForContainingBox : $totalRowNeededForContainingBox");

                $totalTruck = $boxQuantity / (intval($truckLength / $boxLength) * $boxContainPerRow);
                if (is_float($totalTruck)) {
                    $totalTruck = intval($totalTruck) + 1;
                }

                $truckInfo[] = [
                    "truck" => $selectedTruckType,
                    "total_truck" => $totalTruck,
                    "truck_dimension" => $truckDimension,
                    "box_dimension" => $boxDimension,
                    "empty_space_per_row" => $emptySpacePerRow,
                    "box_contain_per_row" => $boxContainPerRow,
                    "total_row_for_containing_box" => $totalRowNeededForContainingBox,
                    "total_box_length" => $totalRowNeededForContainingBox * $boxLength,
                    "total_box_quantity" => $boxQuantity,
                    "fillable_box_quantity_in_each_truck" => intval($truckLength / $boxLength) * $boxContainPerRow,
                    "fillable_row_in_each_truck" => intval($truckLength / $boxLength)
                ];
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
