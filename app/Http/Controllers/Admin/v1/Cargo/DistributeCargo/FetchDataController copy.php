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
                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');

                    $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                    // dd($partiallyLoadedTruckBoxQuantity);
                    $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity, $cargokey);
                    // dd($filteredTruckInfo);
                    // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                    $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                    // dd($filteredTruckInfoKey);
                    $finalTrucks[] = $filteredTruckInfoKey;
                    // dd($finalTrucks);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');

                    // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                } else {
                    $finalTrucks[] = $selectedTempTruck;
                    // dd($finalTrucks);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                }
                dump($finalTrucks);
            }
            // dump($boxQuantity);

            if ($boxQuantity == 0) {
                continue;
            } else {
                $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $box['box_dimension'], $boxQuantity, $cargokey);

                // dump($filteredTruckInfo);

                // if (!Arr::exists($this->cargoInfo, ++$cargokey)) {
                if (!array_key_exists(++$cargokey, $this->cargoInfo)) {
                    dump("last box");

                    $selectedTempTruck = $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension'], true);

                    if ($selectedTempTruck['total_truck'] > 1) {
                        $finalTrucks[] = $selectedTempTruck;
                        $finalTrucks[sizeof($finalTrucks) - 1]['total_truck'] = ($selectedTempTruck['total_truck'] - 1);
                        unset($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'][$selectedTempTruck['total_truck'] - 1]);
                        unset($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'][$selectedTempTruck['total_truck'] - 1]);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');

                        $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                        // dd($partiallyLoadedTruckBoxQuantity);
                        $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity, $cargokey);
                        // dd($filteredTruckInfo);
                        // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                        $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                        $finalTrucks[] = $filteredTruckInfoKey;
                        // dd($finalTrucks);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');

                        // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                    } else {
                        $finalTrucks[] = $selectedTempTruck;
                        // dd($finalTrucks);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck');
                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info');
                    }
                }
                // dump($filteredTruckInfo);
            }
        }

        // dump($cargoBoxLoadInfo);
        dd($finalTrucks);
        dd($cargoBoxLoadInfo);

        // dd("finish for now");

        // Return the consolidated cargo and any remaining cargo to the view
        return response()->json($result);
        return view('cargo.consolidation', compact('consolidatedCargo'));
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

    private function getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $cargoBoxDimension, $lasCargotBox = false)
    {
        $cargoBoxkey = array_search($cargoBoxDimension, array_column($this->cargoInfo, 'box_dimension'));
        $boxDim = explode('*', $this->cargoInfo[$cargoBoxkey]['box_dimension']);
        $boxLength = floatval($boxDim[0]);
        $boxWidth = $boxDim[1];
        // dump($boxVolume);
        foreach ($filteredTruckInfo as $tempKey => $item) {
            $prevBoxDim = explode('*', $item['box_dimension']);
            $truckDim = explode('*', $item['truck_dimension']);
            $truckLength = $truckDim[0];
            $truckWidth = $truckDim[1];
            $truckDimension = $truckLength . "*" . $truckWidth . "*" . $truckDim[2];
            $boxQuantity = $this->cargoInfo[$cargoBoxkey]['quantity'];

            for ($i = 1; $i <= $item['total_truck']; $i++) {
                $fillableQuantity = $filledQuantity = $boxContainPerRow = $filledQuantityOnPrevUnoccupiedRowSpace = $boxQuantityOnFullyUnfilledRow = $boxQuantityOnPartiallyFilledRow = 0;
                $availableTotalNoOfRow = 0;
                $boxDimension = explode('*', $item['box_dimension']);
                $truckDimension = explode('*', $item['truck_dimension']);

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

                    // dump($item['truck'] . " : $boxWidth : " . $item['empty_space_per_row']);
                    if (($item['empty_space_per_row'] >= $boxWidth || $item['empty_space_of_last_filled_row'] >= $boxWidth) && $boxQuantity > 0) {
                        $totalNoOfRow = $lastTruckOccupiedLength / $boxLength;
                        // dump($item['truck'] . " : $totalNoOfRow : $boxWidth : " . $item['empty_space_of_last_filled_row'] . " " . intval($item['empty_space_of_last_filled_row'] / $boxWidth));
                        // $totalNoOfRow = is_float($totalNoOfRow) ? intval($totalNoOfRow) + 1 : $totalNoOfRow;
                        if ($totalNoOfRow == 1 && $item['empty_space_of_last_filled_row'] >= $boxWidth) {
                            $boxContainPerRowInEmptySpace = intval($item['empty_space_of_last_filled_row'] / $boxWidth);
                        } else {
                            // $totalNoOfRow = $totalNoOfRow - 1;
                            $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                            // $boxContainPerRowInEmptySpace += ($item['empty_space_of_last_filled_row'] >= $boxWidth) ? intval($item['empty_space_of_last_filled_row'] / $boxWidth) : 0;
                        }
                        $fillableQuantity = ($totalNoOfRow - 1) *  $boxContainPerRowInEmptySpace;
                        $fillableQuantity += ($item['empty_space_of_last_filled_row'] >= $boxWidth) ? intval($item['empty_space_of_last_filled_row'] / $boxWidth) : $boxContainPerRowInEmptySpace;
                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                            // $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                            $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        }
                        // $lastTruckUnoccupiedLength = $truckDimension[0] - $totalNoOfRow * $boxLength;
                    }

                    if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        $fillableQuantity = ($availableTotalNoOfRow > $totalRowNeededForContainingBox) ? $totalRowNeededForContainingBox *  $boxContainPerRow : $availableTotalNoOfRow *  $boxContainPerRow;

                        // $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                        // if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                        //     $filledQuantity = ($filledQuantityOnPrevUnoccupiedRowSpace > 0) ? $filledQuantity + $filledQuantityOnPrevUnoccupiedRowSpace : $filledQuantity;
                        // }
                        $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        $filledQuantity = ($boxQuantityOnPartiallyFilledRow > 0) ? $filledQuantity + $boxQuantityOnPartiallyFilledRow : $filledQuantity;
                    }
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

                    if ($item['empty_space_per_row'] >= $boxWidth && $boxQuantity > 0) {
                        $boxContainPerRowInEmptySpace = intval($item['empty_space_per_row'] / $boxWidth);
                        // dump($boxContainPerRowInEmptySpace);
                        $totalNoOfRow = intval($lastTruckOccupiedLength / $boxLength);
                        $fillableQuantity = $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                        // dump($fillableQuantity);
                        // dump("shit");
                        if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                            // $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                            $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        }
                        // $lastTruckUnoccupiedLength = $truckDimension[0] - $totalNoOfRow * $boxLength;
                    }

                    if ($lastTruckUnoccupiedLength >= $boxLength && $boxQuantity > 0) {
                        // dump("yo");
                        $boxContainPerRow = intval($truckDimension[1] / $boxWidth);
                        $totalRowNeededForContainingBox = $boxQuantity / $boxContainPerRow;
                        $totalBoxLength = $totalRowNeededForContainingBox * $boxLength;

                        $availableTotalNoOfRow = intval($lastTruckUnoccupiedLength / $boxLength);
                        $fillableQuantity = ($availableTotalNoOfRow > $totalRowNeededForContainingBox) ? $totalRowNeededForContainingBox *  $boxContainPerRow : $availableTotalNoOfRow *  $boxContainPerRow;

                        // $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $filledQuantity += $fillableQuantity : $filledQuantity += $boxQuantity;
                        $boxQuantityOnFullyUnfilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                        $filledQuantity = ($boxQuantityOnPartiallyFilledRow > 0) ? $filledQuantity + $boxQuantityOnPartiallyFilledRow : $filledQuantity;
                        // dump($boxQuantityOnFullyUnfilledRow);
                    }
                    // dd($filledQuantity);
                    $totalFilledBoxQuantity = ($boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow > $boxQuantity) ? $boxQuantity : $boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow;
                    $boxQuantity -= $filledQuantity;
                }
                // dd($filteredTruckInfo[$tempKey]);
                if ($lasCargotBox == true) {
                    $filteredTruckInfo[$tempKey]["other_box_load_info"][] = [
                        "truckArrTempKey" => null,
                        "cargoArrTempKey" => null,
                        "box_dimension" => null,
                        "total_box_quantity" => null,
                        "can_contain_total_box_on_partially_filled_row" => null,
                        "can_contain_total_box_on_fully_unfilled_row" => null,
                        "can_contain_box_on_a_fully_unfilled_row" => null,
                        "total_filled_box_quantity" => null,
                        "fillable_row_in_each_truck" => null
                    ];
                } else {
                    $filteredTruckInfo[$tempKey]["other_box_load_info"][] = [
                        "truckArrTempKey" => $tempKey,
                        "cargoArrTempKey" => $cargoBoxkey,
                        "box_dimension" => ($filledQuantity != 0) ? $this->cargoInfo[$cargoBoxkey]['box_dimension'] : null,
                        "total_box_quantity" => ($filledQuantity != 0) ? (($i == 1) ? $this->cargoInfo[$cargoBoxkey]['quantity'] : $boxQuantity + $filledQuantity) : null,
                        "can_contain_total_box_on_partially_filled_row" => $boxQuantityOnPartiallyFilledRow,
                        "can_contain_total_box_on_fully_unfilled_row" => intval($availableTotalNoOfRow * $boxContainPerRow),
                        "can_contain_box_on_a_fully_unfilled_row" => $boxContainPerRow,
                        "total_filled_box_quantity" => $totalFilledBoxQuantity,
                        "fillable_row_in_each_truck" => $availableTotalNoOfRow
                    ];
                }
                // dump($boxQuantity);
            }
        }
        // }
        dump($filteredTruckInfo);

        $maxFilledBoxQuantity = $maxFilledBoxTruckKey = null;
        foreach ($filteredTruckInfo as $tempKey => $item) {
            $filledBoxQuantity = $item['individual_truck'][0]['total_filled_box_quantity'] + $item['other_box_load_info'][0]['total_filled_box_quantity'];
            if ($filledBoxQuantity >= $maxFilledBoxQuantity) {
                $maxFilledBoxQuantity = $filledBoxQuantity;
                $maxFilledBoxTruckKey = $tempKey;
            }
        }
        $selectedTempTruck = $filteredTruckInfo[$maxFilledBoxTruckKey];
        dump($selectedTempTruck);

        return $selectedTempTruck;
    }

    private function getFilteredTruckData($uniqueTrucksArray, $boxDimension, $boxQuantity, $cargoBoxkey)
    {
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $truckInfo = [];
        $boxDim = explode('*', $boxDimension);
        $boxQuantity = $boxQuantity;
        $boxLength = $boxDim[0];
        $boxWidth = $boxDim[1];
        // dump($boxDimension);
        // dump($boxQuantity);

        foreach ($uniqueTrucksArray as $count => $item) {
            $truckLength = $item['length'];
            $truckWidth = $item['width'];
            $truckDimension = $truckLength . "*" . $truckWidth . "*" . $item['height'];
            // $truckFilledBoxQuantity = 0;

            if ($boxWidth <= $truckWidth) {
                $selectedTruckWidth = $truckWidth;
                $selectedTruckType = $item["truck_type"];

                $boxContainPerRow = $selectedTruckWidth / $boxWidth;
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

                $totalTruck = $boxQuantity / (intval($truckLength / $boxLength) * $boxContainPerRow);
                if (is_float($totalTruck)) {
                    $totalTruck = intval($totalTruck) + 1;
                }

                $truckInfo[$count] = [
                    "truck" => $selectedTruckType,
                    "total_truck" => $totalTruck,
                    "truck_dimension" => $truckDimension,
                    "box_dimension" => $boxDimension,
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
                            $truckFilledBoxQuantity = ($truckInfo[$count]['fillable_box_quantity_in_each_truck'] > $boxQuantity) ? $boxQuantity : $boxQuantity - $truckInfo[$count]['fillable_box_quantity_in_each_truck'];
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
                        "box_contain_per_row" => $boxContainPerRow,
                        // "empty_space_per_row" => $emptySpacePerRow,
                        "empty_space_by_length" => $truckUnoccupiedLength,
                        "total_box_quantity" => $boxQuantity,
                        // "remaining_box_quantity" => $boxQuantity - $truckFilledBoxQuantity,
                        "total_filled_box_quantity" => $truckFilledBoxQuantity,
                        "fillable_row_in_each_truck" => intval($truckLength / $boxLength)
                    ];
                }
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
}
