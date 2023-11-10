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

    public function getData(Request $request)
    {
        $cargo_id = $request->cargo_id;
        // dd($cargo_id);
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

        $truckInfo = $filteredTruckInfo = $chosenTrucks = $cargoBoxLoadInfo = [];
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
                $highestFiilableBoxQuantityInEachTruck = $highestFiilableBoxQuantityInEachTruckKey = PHP_INT_MIN;
                foreach ($filteredTruckInfo as $key => $truckData) {
                    $totalTruck = $truckData["total_truck"];
                    $totalBoxQuantity = $truckData["total_box_quantity"];
                    $fillableBoxQuantityInEachTruck = $truckData["fillable_box_quantity_in_each_truck"];
                    if ($fillableBoxQuantityInEachTruck < $totalBoxQuantity && $fillableBoxQuantityInEachTruck > $highestFiilableBoxQuantityInEachTruck) {
                        $highestFiilableBoxQuantityInEachTruck = $fillableBoxQuantityInEachTruck;
                        $highestFiilableBoxQuantityInEachTruckKey = $key;
                    }
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
                }

                if (array_key_exists($highestFiilableBoxQuantityInEachTruckKey, $filteredTruckInfo)) {
                    $selectedTempTruck = $filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey];
                    $truckDimension = explode('*', $selectedTempTruck['truck_dimension']);
                    $boxDimension = explode('*', $selectedTempTruck['box_dimension']);

                    $index = sizeof($cargoBoxLoadInfo);
                    // dd($index);

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

                    // check if the empty width is greater than the new box width and if it is than store the storeable boxes
                    if ($selectedTempTruck['empty_space_per_row'] >= $boxWidth) {
                        $boxContainPerRowInEmptySpace = intval($selectedTempTruck['empty_space_per_row'] / $boxWidth);
                        $totalNoOfRow = intval($selectedTempTruck['total_box_length'] / $boxLength);
                        $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $boxQuantity -= $filledQuantity;

                        $cargoBoxLoadInfo[$index] = [
                            "other_box_in_empty_space" => [
                                "box_dimension" => $cargo['box_dimension'],
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

                // // dd($minDifferenceKey);
                // if ($minDifferenceKey >= 0) { // when truck empty width > new box width
                //     for ($i = 1; $i <= $filteredTruckInfo[$minDifferenceKey]['total_truck']; $i++) {
                //         if ($i == $filteredTruckInfo[$minDifferenceKey]['total_truck']) {
                //             if (($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] * $filteredTruckInfo[$minDifferenceKey]['total_truck']) > $filteredTruckInfo[$minDifferenceKey]['total_box_quantity']) {
                //                 $lastTruckFilledBoxQuantity = $filteredTruckInfo[$minDifferenceKey]['total_box_quantity'] - (($filteredTruckInfo[$minDifferenceKey]['total_truck'] - 1) * $filteredTruckInfo[$minDifferenceKey]['fillable_row_in_each_truck']);
                //                 $boxDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['box_dimension']);
                //                 $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                //                 // dump($lastTruckFilledBoxQuantity);
                //                 $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $filteredTruckInfo[$minDifferenceKey]['box_contain_per_row'];
                //                 // dump($lastTruckOccupiedRow);
                //                 $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                //                 // dump($lastTruckOccupiedLength);
                //                 $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                //                 // $lastTruckUnoccupiedLength = ($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] - $lastTruckOccupiedRow) * $boxDimension[0];
                //                 // dump($lastTruckUnoccupiedLength);

                //                 $boxContainPerRowInEmptySpace = intval($filteredTruckInfo[$minDifferenceKey]['empty_space_per_row'] / $boxWidth);
                //                 $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                //                 $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                //                 // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                //                 $boxQuantity -= $filledQuantity;

                //                 if ($lastTruckUnoccupiedLength >= $boxLength) {
                //                     // dump("lastTruckUnoccupiedLength : $lastTruckUnoccupiedLength , boxLength : $boxLength");
                //                     $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                //                     // dump($boxContainPerRow);
                //                     $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                //                     // dump($totalRowForContainingBox);
                //                     $totalBoxLength += $totalRowNeededForContainingBox * $boxLength;
                //                     // dump($totalBoxLength);

                //                     $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                //                     // dump($availableTotalNoOfRow);
                //                     if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                //                         $filledQuantity = $totalRowNeededForContainingBox *  $boxContainPerRow;
                //                         // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                //                         $boxQuantity -= $filledQuantity;
                //                         // dump("boxQuantity : $boxQuantity");
                //                     } else {
                //                         $filledQuantity = $availableTotalNoOfRow *  $boxContainPerRow;
                //                         // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                //                         $boxQuantity -= $filledQuantity;
                //                         // dump("boxQuantity : $boxQuantity");
                //                     }
                //                 }
                //             }
                //         } else {
                //             $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                //             $boxContainPerRowInEmptySpace = intval($filteredTruckInfo[$minDifferenceKey]['empty_space_per_row'] / $boxWidth);
                //             $totalNoOfRow = intval($truckDimension[0] / $boxLength);
                //             $filledQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                //             // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                //             $boxQuantity -= $filledQuantity;
                //         }
                //         // dump($boxQuantity);
                //     }
                //     dump($filteredTruckInfo[$minDifferenceKey]);
                //     $chosenTrucks [] = $filteredTruckInfo[$minDifferenceKey];
                // } else { // when truck empty width < new box width
                //     dump("working on logic");
                //     // dump($boxQuantity);
                //     // needs to fill the logic here
                //     $minDifference = PHP_INT_MAX;
                //     $maxDifference = PHP_INT_MIN;
                //     $minDifferenceKey = PHP_INT_MIN;
                //     foreach ($filteredTruckInfo as $key => $truckData) {
                //         $truckDimension = explode('*', $truckData['truck_dimension']);
                //         $boxDimension = explode('*', $truckData['box_dimension']);
                //         $fillableLengthInTruck = $truckData['fillable_row_in_each_truck'] * $boxDimension[0];
                //         $boxLengthNeedsToBeFilled = $boxDimension[0] * $truckData['total_box_quantity'];
                //         // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
                //         if ($fillableLengthInTruck > $boxLengthNeedsToBeFilled) {
                //             $fillDifference = $fillableLengthInTruck - $boxLengthNeedsToBeFilled;
                //             // dump("fillableLengthInTruck: $fillableLengthInTruck , boxLengthNeedsToBeFilled: $boxLengthNeedsToBeFilled");
                //             if ($fillDifference < $minDifference) {
                //                 $minDifference = $fillDifference;
                //                 $minDifferenceKey = $key;
                //             }
                //         } else {
                //             $fillDifference = $fillableLengthInTruck;
                //             if ($fillDifference > $maxDifference) {
                //                 $maxDifference = $fillDifference;
                //                 $minDifferenceKey = $key;
                //             }
                //         }
                //     }
                //     $truckDimension = explode('*', $filteredTruckInfo[$minDifferenceKey]['truck_dimension']);
                //     if (($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] * $filteredTruckInfo[$minDifferenceKey]['total_truck']) > $filteredTruckInfo[$minDifferenceKey]['total_box_quantity']) {
                //         $lastTruckFilledBoxQuantity = $filteredTruckInfo[$minDifferenceKey]['total_box_quantity'] - (($filteredTruckInfo[$minDifferenceKey]['total_truck'] - 1) * $filteredTruckInfo[$minDifferenceKey]['fillable_row_in_each_truck']);
                //         // dump($lastTruckFilledBoxQuantity);
                //         $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $filteredTruckInfo[$minDifferenceKey]['box_contain_per_row'];
                //         // dump($lastTruckOccupiedRow);
                //         $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                //         // dump($lastTruckOccupiedLength);
                //         $lastTruckUnoccupiedLength = ($filteredTruckInfo[$minDifferenceKey]['fillable_box_quantity_in_each_truck'] - $lastTruckOccupiedRow) * $boxDimension[0];
                //         // dump($lastTruckUnoccupiedLength);

                //         $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                //         $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                //         $totalBoxLength += $totalRowForContainingBox * $boxLength;
                //         $emptySpacePerRow = $truckDimension[1] - ($boxWidth * $boxContainPerRow);
                //         // dump($emptySpacePerRow);
                //         // dump($boxQuantity);

                //         $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                //         // dump($availableTotalNoOfRow);
                //         if ($availableTotalNoOfRow > $totalRowNeededForContainingBox) {
                //             $filledQuantity = $totalRowNeededForContainingBox *  $boxContainPerRow;
                //             // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                //             $boxQuantity -= $filledQuantity;
                //             // dump("boxQuantity : $boxQuantity");
                //         } else {
                //             $filledQuantity = $availableTotalNoOfRow *  $boxContainPerRow;
                //             // dump("filledQuantity : $filledQuantity , boxQuantity : $boxQuantity");
                //             $boxQuantity -= $filledQuantity;
                //             // dump("boxQuantity : $boxQuantity");
                //         }
                //         // dump($boxQuantity);
                //     }


                //     dump($filteredTruckInfo[$minDifferenceKey]);
                //     $chosenTrucks [] = $filteredTruckInfo[$minDifferenceKey];
                // }
            }

            // dump($boxQuantity);

            if ($boxQuantity == 0) {
                continue;
            } else {
                $truckInfo = [];
                foreach ($uniqueTrucksArray as $item) {
                    $truckLength = $item['length'];
                    $truckWidth = $item['width'];
                    $truckDimension = $truckLength . "*" . $truckWidth . "*" . $item['height'];

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

                // dump("box length*width: $boxLength*$boxWidth Quantity: $boxQuantity");
                // dump("$minValueTruckType : $minValueTruckDimension");
                // dump("truck width: $closestMin");
                // dump("boxContainPerRow: $boxContainPerRow");
                // dump("totalRowForContainingBox: $totalRowForContainingBox");
                // dump("box total length: " . $totalRowForContainingBox*$boxLength);

                // dd("");
            }
        }

        dump($cargoBoxLoadInfo);
        dd($chosenTrucks);

        // dd("finish for now");

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
    }
}
