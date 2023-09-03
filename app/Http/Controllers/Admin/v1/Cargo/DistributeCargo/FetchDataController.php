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
    public function getOptimizedData($cargo_id)
    {
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
        $consolidatedCargo = [];
        $remainingCargo = [];

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

        //
        //
        // Assign the remaining cargo to other available trucks using the same logic
        foreach ($remainingCargo as $box) {
            $boxDim = explode('*', $box['box_dimension']);
            $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxQuantity = $box['quantity'];

            $bestFittingTruck = null;
            $maxBoxesToLoad = 0;
            $bestWastedSpace = null;

            foreach ($trucks as $truck) {
                $truckVolume = $truck->length * $truck->width * $truck->height;
                dump($truck->truck_type);
                dump($truckVolume);

                // Calculate how many boxes can fit in the truck, considering quantity
                $maxBoxes = floor($truckVolume / $boxVolume);
                dump($maxBoxes);

                $truckBoxContainCapacity[] = [
                    "truck_type" => $truck->truck_type,
                    "truck_volume" => $truckVolume,
                    "box_contain_capacity" => $maxBoxes,
                ];
            }

            $minDiff = PHP_INT_MAX;
            $maxDiff = PHP_INT_MAX;
            $closestMin = null;
            $closestMax = null;

            foreach ($truckBoxContainCapacity as $item) {
                $capacity = $item["box_contain_capacity"];

                if ($capacity <= $boxQuantity) {
                    $minDiffCurrent = $boxQuantity - $capacity;
                    if ($minDiffCurrent < $minDiff) {
                        $minDiff = $minDiffCurrent;
                        $closestMin = $capacity;
                        $minValueTruckType = $item["truck_type"];
                        $minValueBoxContainCapacity = $item["box_contain_capacity"];
                    }
                }

                if ($capacity >= $boxQuantity) {
                    $maxDiffCurrent = $capacity - $boxQuantity;
                    if ($maxDiffCurrent < $maxDiff) {
                        $maxDiff = $maxDiffCurrent;
                        $closestMax = $capacity;
                        $maxValueTruckType = $item["truck_type"];
                        $maxValueTruckVolume = $item["truck_volume"];
                        $maxValueBoxContainCapacity = $item["box_contain_capacity"];
                    }
                }
            }

            dump($closestMin);
            dump($closestMax);
            // dump($maxValueBoxContainCapacity);
            // dd("");

            if(!empty($closestMin) && empty($closestMax)) {
                $boxContainCapacity = $minValueBoxContainCapacity;
                $truckType = $minValueTruckType;
                $remainingSpaceOnTruck = "";
                $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
                $loadedBoxQuantity = $boxContainCapacity;
            } elseif(!empty($closestMin) && !empty($closestMax) && $closestMax >= $closestMin) {
                $boxContainCapacity = $maxValueBoxContainCapacity;
                $truckType = $maxValueTruckType;
                $remainingSpaceOnTruck = $maxValueTruckVolume - ($boxVolume * $boxQuantity);
                $remainingboxQuantity = 0;
                $loadedBoxQuantity = $boxQuantity;
            }

            $bestFittingTruck = $truckType;
            $maxBoxesToLoad = $boxContainCapacity;
            // dd("");

            // If a fitting truck is found, add the boxes to it; otherwise, save the boxes for later
            if ($bestFittingTruck) {
                $consolidatedCargo[] = [
                    'truck_type' => $bestFittingTruck,
                    'box_dimension' => $box['box_dimension'],
                    'can_load_max_box_quantity' => $maxBoxesToLoad,
                    'loaded_box_quantity' => $loadedBoxQuantity,
                    'remaining_box_quantity' => $remainingboxQuantity,
                    'remaining_space_on_truck' => $remainingSpaceOnTruck,
                ];

                if($remainingboxQuantity >= 0) {
                    // Reduce the box quantity by the loaded quantity
                    $box['quantity'] -= $loadedBoxQuantity;
                }

                // If there are remaining boxes of this type, save them for later
                if ($box['quantity'] > 0) {
                    $remainingCargo[] = $box;
                } else {
                    $remainingCargo = [];
                }
            } else {
                $remainingCargo[] = [
                    'box_dimension' => $box['box_dimension'],
                    'quantity' => $boxQuantity,
                ];
            }
            dump($consolidatedCargo);
            dump($remainingCargo);
        }

        // Create a function to assign boxes to trucks
        $assignBoxesToTrucks = function ($cargoInfo, $trucks) {
            $truckBoxContainCapacity = [];
            // Iterate through each cargo box
            foreach ($cargoInfo as $box) {
                dump($box);
                $boxDim = explode('*', $box['box_dimension']);
                $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
                $boxQuantity = $box['quantity'];
                dump($boxVolume);

                // Initialize variables for the best-fitting truck and maximum quantity of boxes to load
                $bestFittingTruck = null;
                $maxBoxesToLoad = 0;

                foreach ($trucks as $truck) {
                    $truckVolume = $truck->length * $truck->width * $truck->height;
                    dump($truck->truck_type);
                    dump($truckVolume);

                    // Calculate how many boxes can fit in the truck, considering quantity
                    $maxBoxes = floor($truckVolume / $boxVolume);
                    dump($maxBoxes);

                    $truckBoxContainCapacity[] = [
                        "truck_type" => $truck->truck_type,
                        "truck_volume" => $truckVolume,
                        "box_contain_capacity" => $maxBoxes,
                    ];
                }


                $minDiff = PHP_INT_MAX;
                $maxDiff = PHP_INT_MAX;
                $closestMin = null;
                $closestMax = null;

                foreach ($truckBoxContainCapacity as $item) {
                    $capacity = $item["box_contain_capacity"];

                    if ($capacity <= $boxQuantity) {
                        $minDiffCurrent = $boxQuantity - $capacity;
                        if ($minDiffCurrent < $minDiff) {
                            $minDiff = $minDiffCurrent;
                            $closestMin = $capacity;
                            $minValueTruckType = $item["truck_type"];
                            $minValueBoxContainCapacity = $item["box_contain_capacity"];
                        }
                    }

                    if ($capacity >= $boxQuantity) {
                        $maxDiffCurrent = $capacity - $boxQuantity;
                        if ($maxDiffCurrent < $maxDiff) {
                            $maxDiff = $maxDiffCurrent;
                            $closestMax = $capacity;
                            $maxValueTruckType = $item["truck_type"];
                            $maxValueTruckVolume = $item["truck_volume"];
                            $maxValueBoxContainCapacity = $item["box_contain_capacity"];
                        }
                    }
                }

                dump($closestMin);
                dump($closestMax);
                // dump($maxValueBoxContainCapacity);
                // dd("");

                if(!empty($closestMin) && empty($closestMax)) {
                    $boxContainCapacity = $minValueBoxContainCapacity;
                    $truckType = $minValueTruckType;
                    $remainingSpaceOnTruck = "";
                    $remainingboxQuantity = $boxQuantity - $boxContainCapacity;
                    $loadedBoxQuantity = $boxContainCapacity;
                } elseif(!empty($closestMin) && !empty($closestMax) && $closestMax >= $closestMin) {
                    $boxContainCapacity = $maxValueBoxContainCapacity;
                    $truckType = $maxValueTruckType;
                    $remainingSpaceOnTruck = $maxValueTruckVolume - ($boxVolume * $boxQuantity);
                    $remainingboxQuantity = 0;
                    $loadedBoxQuantity = $boxQuantity;
                }
                // dd("");

                $bestFittingTruck = $truckType;
                $maxBoxesToLoad = $boxContainCapacity;
                dump($bestFittingTruck);
                dump($maxBoxesToLoad);
                dump($remainingboxQuantity);
                dump($remainingSpaceOnTruck);
                // dd("");

                // If a fitting truck is found, add the boxes to it; otherwise, save the boxes for later
                if ($bestFittingTruck) {
                    $consolidatedCargo[] = [
                        'truck_type' => $bestFittingTruck,
                        'box_dimension' => $box['box_dimension'],
                        'can_load_max_box_quantity' => $maxBoxesToLoad,
                        'loaded_box_quantity' => $loadedBoxQuantity,
                        'remaining_box_quantity' => $remainingboxQuantity,
                        'remaining_space_on_truck' => $remainingSpaceOnTruck,
                    ];

                    if($remainingboxQuantity >= 0) {
                        // Reduce the box quantity by the loaded quantity
                        $box['quantity'] -= $loadedBoxQuantity;
                    }

                    // dd($box['quantity']);

                    // If there are remaining boxes of this type, save them for later
                    if ($box['quantity'] > 0) {
                        $remainingCargo[] = $box;
                    } else {
                        $remainingCargo = [];
                    }
                } else {
                    $remainingCargo[] = [
                        'box_dimension' => $box['box_dimension'],
                        'quantity' => $boxQuantity,
                    ];
                }
                dump($consolidatedCargo);
            }
            dump($remainingCargo);
        };

        // Call the function for the initial cargo
        $assignBoxesToTrucks($cargoInfo, $trucks);

        // If there's remaining cargo, call the function again
        if (!empty($remainingCargo)) {
            $assignBoxesToTrucks($remainingCargo, $trucks);
        }

        // Iterate through each cargo box
        // foreach ($cargoInfo as $box) {
        //     dump($box);
        //     $boxDim = explode('*', $box['box_dimension']);
        //     $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
        //     $boxQuantity = $box['quantity'];
        //     dump($boxQuantity);

        //     // Initialize variables for this box
        //     $remainingQuantity = $boxQuantity;

        //     // Find the best-fitting truck
        //     while ($remainingQuantity > 0) {
        //         $bestFittingTruck = null;
        //         $bestWastedSpace = null;

        //         foreach ($trucks as $truck) {
        //             $truckVolume = $truck->length * $truck->width * $truck->height;
        //             dump($truck->truck_type);
        //             dump($truckVolume);

        //             // Calculate how many boxes can fit in the truck
        //             $maxBoxes = floor($truckVolume / $boxVolume);
        //             dump($maxBoxes);

        //             if ($maxBoxes > 0) {
        //                 // Calculate wasted space
        //                 $wastedSpace = $truckVolume - ($maxBoxes * $boxVolume);

        //                 // Check if this truck is the best fit so far
        //                 if ($bestFittingTruck === null || $wastedSpace < $bestWastedSpace) {
        //                     $bestFittingTruck = $truck;
        //                     $bestWastedSpace = $wastedSpace;
        //                 }
        //             }
        //         }

        //         // If a fitting truck is found, add the box to it
        //         if ($bestFittingTruck) {
        //             $consolidatedCargo[] = [
        //                 'truck_type' => $bestFittingTruck->truck_type,
        //                 'box_dimension' => $box['box_dimension'],
        //                 'quantity' => min($maxBoxes, $remainingQuantity),
        //             ];
        //             $remainingQuantity -= min($maxBoxes, $remainingQuantity);
        //             dump($remainingQuantity);
        //         } else {
        //             // No suitable truck found, save the remaining quantity for later
        //             $remainingCargo[] = [
        //                 'box_dimension' => $box['box_dimension'],
        //                 'quantity' => $remainingQuantity,
        //             ];
        //             break; // Exit the loop
        //         }
        //     }

        //     dump($consolidatedCargo);
        // }



        // Iterate through each cargo box
        // foreach ($cargoInfo as $box) {
        //     dump($box);
        //     $boxDim = explode('*', $box['box_dimension']);
        //     $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
        //     $boxQuantity = $box['quantity'];
        //     dump($boxVolume);

        //     // Find the best-fitting truck
        //     $bestFittingTruck = null;
        //     $bestWastedSpace = null;
        //     $bestRemainingSpace = null;

        //     foreach ($trucks as $truck) {
        //         $truckVolume = $truck->length * $truck->width * $truck->height;
        //         dump($truck->truck_type);
        //         dump($truckVolume);

        //         // Calculate how many boxes can fit in the truck
        //         $maxBoxes = floor($truckVolume / $boxVolume);
        //         // $quantityInTruck = min($maxBoxes, $boxQuantity);
        //         dump($maxBoxes);

        //         if ($maxBoxes > 0) {
        //             // Calculate wasted space considering quantity
        //             $totalWastedSpace = $truckVolume - ($maxBoxes * $boxVolume);
        //             $wastedSpacePerBox = $totalWastedSpace / $maxBoxes;
        //             dump($wastedSpacePerBox);

        //             // Check if this truck is the best fit so far
        //             if ($bestFittingTruck === null || $wastedSpacePerBox < $bestWastedSpace) {
        //                 $bestFittingTruck = $truck;
        //                 $bestWastedSpace = $wastedSpacePerBox;
        //             }
        //         }

        //         // if ($quantityInTruck > 0) {
        //         //     // Calculate wasted space for the quantity in this truck
        //         //     $wastedSpace = $truckVolume - ($quantityInTruck * $boxVolume);

        //         //     // Check if this truck is the best fit so far
        //         //     if ($bestFittingTruck === null || $wastedSpace < $bestWastedSpace) {
        //         //         $bestFittingTruck = $truck;
        //         //         $bestWastedSpace = $wastedSpace;
        //         //     }
        //         // }

        //         // if ($maxBoxes > 0 && $maxBoxes >= $boxQuantity) {
        //         //     // Calculate wasted space
        //         //     $wastedSpace = $truckVolume - ($maxBoxes * $boxVolume);
        //         //     dump($wastedSpace);
        //         //     dump($bestWastedSpace);

        //         //     // Check if this truck is the best fit so far
        //         //     if ($bestFittingTruck === null || $wastedSpace < $bestWastedSpace) {
        //         //         $bestFittingTruck = $truck;
        //         //         $bestWastedSpace = $wastedSpace;
        //         //     }
        //         // }
        //     }

        //     // // If a fitting truck is found, add the quantity of boxes to it; otherwise, save the box for later
        //     // if ($bestFittingTruck) {
        //     //     $consolidatedCargo[] = [
        //     //         'truck_type' => $bestFittingTruck->truck_type,
        //     //         'box_dimension' => $box['box_dimension'],
        //     //         'quantity' => $quantityInTruck,
        //     //     ];

        //     //     // Update remaining quantity for this box
        //     //     $boxQuantity -= $quantityInTruck;
        //     // }

        //     // // If there's remaining quantity for this box, add it to remaining cargo
        //     // if ($boxQuantity > 0) {
        //     //     $remainingCargo[] = [
        //     //         'box_dimension' => $box['box_dimension'],
        //     //         'quantity' => $boxQuantity,
        //     //     ];
        //     // }

        //     // If a fitting truck is found, add the box to it; otherwise, save the box for later
        //     if ($bestFittingTruck) {
        //         $consolidatedCargo[] = [
        //             'truck_type' => $bestFittingTruck->truck_type,
        //             'box_dimension' => $box['box_dimension'],
        //             'quantity' => $boxQuantity,
        //         ];
        //     } else {
        //         // $remainingCargo[] = [
        //         //     'box_dimension' => $box['box_dimension'],
        //         //     'quantity' => $boxQuantity,
        //         // ];
        //     }
        //     dump($consolidatedCargo);
        //     dump($remainingCargo);
        // }


        dd("finish for now");

        $result = [
            'status' => 200,
            'consolidatedCargo' => $consolidatedCargo,
        ];

        // dd($result);

        // $consolidatedCargo now contains cargo boxes, their corresponding trucks, and quantities
        // $remainingCargo has been assigned to other available trucks

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
    }
}
