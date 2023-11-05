<?php

namespace App\Http\Controllers\Admin\v1\Cargo\DistributeCargo;

use Exception;
use App\Models\Cargo\Cargo;
use Illuminate\Http\Request;
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
        // return response()->json(['status' => 200]);
        // Retrieve cargo information
        $cargo = Cargo::find($cargo_id);
        // $cargoInfo = $cargo->CargoInformation;
        $cargoInfo = CargoInformation::where('cargo_id', $cargo_id)->get()->toArray();
        // dd($cargoInfo);

        // Retrieve available trucks
        $trucks = Trucks::select("*")->get()->toArray();

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
            $volA = $dimA[0] * $dimA[1];
            $volB = $dimB[0] * $dimB[1];
            if ($volA === $volB) {
                return $b['quantity'] - $a['quantity'];
            }
            return $volB - $volA;
        });

        // dd($cargoInfo);

        // Sort cargo information by box dimensions (descending order) and quantity (descending order)
        usort($trucks, function ($a, $b) {
            $volA = $a['length'] * $a['width'];
            $volB = $b['length'] * $b['width'];
            if ($volA === $volB) {
                return $b['max_weight'] - $a['max_weight'];
            }
            return $volB - $volA;
        });

        // dd($trucks);


        $boxTotalVolumeWithoutHeight = [];
        $minValueTruckType = $totalBoxLength = $totalRowForContainingBox =  $emptySpacePerRow = null;

        $minDiff = PHP_INT_MAX;
        $maxDiff = PHP_INT_MAX;
        $closestMin = null;
        $closestMax = null;

        foreach ($cargoInfo as $box) {
            // $this->truckBoxContainCapacity = [];
            // dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            // $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxVolumeWithoutHeight = $boxDim[0] * $boxDim[1];
            $boxQuantity = $box['quantity'];
            // dump($boxVolume);

            $boxLength = $boxDim[0];
            $boxWidth = $boxDim[1];

            $boxTotalVolumeWithoutHeight[] = $boxVolumeWithoutHeight*$boxQuantity;


            foreach ($trucks as $item) {
                $truckLength = $item['length'];
                $truckWidth = $item['width'];
                $truck_dimension = $truckLength . "*" . $truckWidth . "*" . $item['height'];

                // if($closestMin == null) {
                    if ($boxWidth <= $truckWidth) {
                        $minDiffCurrent = $truckWidth - $boxWidth;
                        if ($minDiffCurrent < $minDiff) {
                            $minDiff = $minDiffCurrent;
                            $closestMin = $truckWidth;
                            $minValueTruckType = $item["truck_type"];
                            $minValueTruckDimension = $truck_dimension;
                            // $minValueTruckVolume = $item["truck_volume"];
                            // dump("1");
                        }
                    }
                // }

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

            if ($emptySpacePerRow != null && $emptySpacePerRow >= $boxWidth) {
                $boxContainPerRowInEmptySpace = intval($emptySpacePerRow/$boxWidth);
                $filledQuantity = $boxContainPerRowInEmptySpace * $totalRowForContainingBox;
                $boxQuantity -= $filledQuantity;
            }

            if($minValueTruckType != null) {
                $boxContainPerRow = intval($closestMin/$boxWidth);
                $totalRowForContainingBox = $boxQuantity/$boxContainPerRow;
                $totalBoxLength += $totalRowForContainingBox*$boxLength;
                $emptySpacePerRow = $closestMin - ($boxWidth*$boxContainPerRow);

                if (is_float($totalRowForContainingBox)){
                    $totalRowForContainingBox = $boxContainPerRow = intval($totalRowForContainingBox) + 1;
                }

                dump("box length*width: $boxLength*$boxWidth Quantity: $boxQuantity");
                dump("$minValueTruckType : $minValueTruckDimension");
                dump("truck width: $closestMin");
                dump("boxContainPerRow: $boxContainPerRow");
                dump("emptySpacePerRow: $emptySpacePerRow");
                dump("totalRowForContainingBox: $totalRowForContainingBox");
                dump("box total length: " . $totalRowForContainingBox*$boxLength);
            }

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

        dump($totalBoxLength);
        dd("");

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

            $boxTotalVolumeWithoutHeight[] = $boxVolumeWithoutHeight*$boxQuantity;
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
