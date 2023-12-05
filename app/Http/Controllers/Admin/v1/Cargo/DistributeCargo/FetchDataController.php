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
            // dump($this->cargoInfo[$cargokey]);
            $box = $this->cargoInfo[$cargokey];
            // dump($cargokey);
            // $this->truckBoxContainCapacity = [];
            dump($box);
            $boxDim = explode('*', $box['box_dimension']);
            // $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxVolumeWithoutHeight = $boxDim[0] * $boxDim[1];
            $boxQuantity = $box['quantity'];
            dump($boxQuantity);

            $boxLength = $boxDim[0];
            $boxWidth = $boxDim[1];

            $boxTotalVolumeWithoutHeight[] = $boxVolumeWithoutHeight * $boxQuantity;

            if (array_key_exists(0, $finalTrucks)) {
                $this->fillBoxInPrevLoadedTrucksEmptySpace($finalTrucks);
            }

            if (array_key_exists(0, $filteredTruckInfo)) {
                $minDifference = PHP_INT_MAX;
                $maxDifference = PHP_INT_MIN;
                $minDifferenceKey = PHP_INT_MIN;
                // $highestFiilableBoxQuantityInEachTruck = $highestFiilableBoxQuantityInEachTruckKey = PHP_INT_MIN;
                // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                $selectedTempTruck = $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                // dd($selectedTempTruck);

                if ($selectedTempTruck['total_truck'] > 1) {
                    // dd($selectedTempTruck);
                    $finalTrucks[] = $selectedTempTruck;
                    $finalTrucks[sizeof($finalTrucks) - 1]['total_truck'] = ($selectedTempTruck['total_truck'] - 1);
                    unset($finalTrucks[sizeof($finalTrucks) - 1]['individual_truck'][$selectedTempTruck['total_truck'] - 1]);
                    unset($finalTrucks[sizeof($finalTrucks) - 1]['other_box_load_info'][$selectedTempTruck['total_truck'] - 1]);
                    unset($finalTrucks[sizeof($finalTrucks) - 1]['truck_space'][$selectedTempTruck['total_truck'] - 1]);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck', $box['box_dimension']);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info', $box['box_dimension']);

                    // dd($this->cargoInfo);

                    $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                    // dump($partiallyLoadedTruckBoxQuantity);
                    dump("ye-1");
                    $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
                    // dd($filteredTruckInfo);
                    // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                    $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension']);
                    // dd($filteredTruckInfoKey);
                    $finalTrucks[] = $filteredTruckInfoKey;
                    // dd($finalTrucks);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck', $box['box_dimension']);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info', $box['box_dimension']);
                } else {
                    $finalTrucks[] = $selectedTempTruck;
                    // dd($finalTrucks);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck', $box['box_dimension']);

                    $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info', $box['box_dimension']);
                }
                dump($finalTrucks);
                // dump($boxQuantity);
            }
            // dump($boxQuantity);

            if ($boxQuantity <= 0) {
                continue;
            } else {
                dump("ye-2");
                $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $box['box_dimension'], $boxQuantity);

                // dump($filteredTruckInfo);

                // if (!Arr::exists($this->cargoInfo, ++$cargokey)) {
                if (!array_key_exists(++$cargokey, $this->cargoInfo)) {
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
                        unset($finalTrucks[sizeof($finalTrucks) - 1]['truck_space'][$selectedTempTruck['total_truck'] - 1]);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck', $box['box_dimension']);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info', $box['box_dimension']);



                        $partiallyLoadedTruckBoxQuantity = $selectedTempTruck['total_box_quantity'] - (($selectedTempTruck['total_truck'] - 1) * $selectedTempTruck['fillable_row_in_each_truck'] * $selectedTempTruck['box_contain_per_row']);
                        // dd($partiallyLoadedTruckBoxQuantity);
                        dump("ye-3");
                        $filteredTruckInfo = $this->getFilteredTruckData($uniqueTrucksArray, $selectedTempTruck['box_dimension'], $partiallyLoadedTruckBoxQuantity);
                        // dd($filteredTruckInfo);
                        // $this->getFilteredTruckData1($filteredTruckInfo, $cargokey);
                        $filteredTruckInfoKey = $this->getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $box['box_dimension'], true);
                        $finalTrucks[] = $filteredTruckInfoKey;
                        // dd($finalTrucks);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck', $box['box_dimension']);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info', $box['box_dimension']);

                        // dd($filteredTruckInfo[$highestFiilableBoxQuantityInEachTruckKey]);
                    } else {
                        $finalTrucks[] = $selectedTempTruck;
                        // dd($finalTrucks);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'individual_truck', $box['box_dimension']);

                        $boxQuantity = $this->reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, 'other_box_load_info', $box['box_dimension']);
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

        // dd("finish for now");

        // Return the consolidated cargo and any remaining cargo to the view
        // return response()->json($result);
        return redirect()->back()->with('finalTrucksData', $finalTrucks);
        // return view('admin.v1.cargo.distribute-cargo.index', $finalTrucks);
    }

    private function getSelectedBoxForTrucks($trucks)
    {
        foreach ($this->cargoInfo as $cargokey => $cargoInfo) {
            $boxDim = explode('*', $cargoInfo['box_dimension']);
            // $boxVolume = $boxDim[0] * $boxDim[1] * $boxDim[2];
            $boxVolumeWithoutHeight = $boxDim[0] * $boxDim[1];
            $boxQuantity = $cargoInfo['quantity'];
            dump($boxQuantity);

            $boxLength = $boxDim[0];
            $boxWidth = $boxDim[1];

            $maxFilledRowTotalEmptyLengthSpace = $trucks['fully_filled_row_total_length_empty_space'];
            $maxFilledRowEmptyWidthSpace = $trucks['fully_filled_row_empty_space'];
            $minFilledRowEmptyLengthSpace = $trucks['partially_filled_row_length_empty_space'];
            $minFilledRowEmptyWidthSpace = $trucks['partially_filled_row_empty_space'];
            $fullyUnfilledRowEmptyLengthSpace = $trucks['fully_unfilled_total_empty_space_by_length'];
            $fullyUnfilledRowEmptyWidthSpace = $trucks['fully_unfilled_empty_space_by_width'];

            if (($boxWidth > $maxFilledRowEmptyWidthSpace && $boxLength <= $maxFilledRowEmptyWidthSpace) && $boxWidth <= $maxFilledRowTotalEmptyLengthSpace && $boxQuantity > 0) {
                $ifBoxCanFit = true;
                $tmpBoxWidth = $boxLength;
                $boxLength = $boxWidth;
                $boxWidth = $tmpBoxWidth;
                $boxContainPerRow = $truckDimension[1] / $boxWidth;
            } else if (($boxWidth <= $maxFilledRowEmptyWidthSpace && $boxLength <= $maxFilledRowEmptyWidthSpace) && ($boxWidth <= $maxFilledRowTotalEmptyLengthSpace && $boxLength <= $maxFilledRowTotalEmptyLengthSpace) && $boxLength != $boxWidth && $boxQuantity > 0) {
                $ifBoxCanFit = true;
                $boxContainPerRowForWidth = $this->getIntegerFromFloatingPoint($truckDimension[1] / $boxWidth);
                $boxContainPerRowForLength = $this->getIntegerFromFloatingPoint($truckDimension[1] / $boxLength);
                $fillableBoxQuantityForEachTruckForWidth = intval($maxFilledRowTotalEmptyLengthSpace / $boxLength) * $boxContainPerRowForWidth;
                $fillableBoxQuantityForEachTruckForLength = intval($maxFilledRowTotalEmptyLengthSpace / $boxWidth) * $boxContainPerRowForLength;

                if ($fillableBoxQuantityForEachTruckForWidth >= $fillableBoxQuantityForEachTruckForLength) {
                    $boxContainPerRow = $boxContainPerRowForWidth;
                } else {
                    $boxContainPerRow = $boxContainPerRowForLength;
                    $tmpBoxWidth = $boxLength;
                    $boxLength = $boxWidth;
                    $boxWidth = $tmpBoxWidth;
                }
            } else if ((($boxWidth <= $maxFilledRowEmptyWidthSpace && $boxLength > $maxFilledRowEmptyWidthSpace && $boxLength <= $maxFilledRowTotalEmptyLengthSpace) || ($boxWidth <= $maxFilledRowEmptyWidthSpace && $boxWidth > $maxFilledRowTotalEmptyLengthSpace && $boxLength <= $maxFilledRowTotalEmptyLengthSpace) || ($boxWidth <= $maxFilledRowEmptyWidthSpace && $boxLength <= $maxFilledRowTotalEmptyLengthSpace && $boxWidth == $boxLength) || ($boxWidth <= $maxFilledRowEmptyWidthSpace && $boxLength <= $maxFilledRowTotalEmptyLengthSpace)) && $boxQuantity > 0) {
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
                    // dump($lastTruckUnoccupiedLength);
                    // dump($filledLength);
                    $emptySpaceByLength = $lastTruckUnoccupiedLength - $filledLength;
                    $emptySpaceByWidth = ($emptySpaceByLength > 0) ? $truckWidth : 0;
                }
            } else {
                $emptySpaceByLength = $lastTruckUnoccupiedLength;
                $emptySpaceByWidth = ($emptySpaceByLength > 0) ? $truckWidth : 0;
            }
        }
    }

    private function fillBoxInPrevLoadedTrucksEmptySpace($finalTrucks)
    {
        foreach ($finalTrucks as $tempKey => $trucksItem) {
            foreach ($trucksItem['truck_space'] as $trucksKey => $trucks) {
                $this->getSelectedBoxForTrucks($trucks);
            }
        }
        return '';
    }

    private function reduceFilledCargoBoxQuantity($finalTrucks, $boxQuantity, $boxType, $boxDimension)
    {
        foreach ($finalTrucks[sizeof($finalTrucks) - 1][$boxType] as $tmpBoxKey => $tmpBox) {
            $searchedDimension = $tmpBox["box_dimension"];
            $key = array_search($searchedDimension, array_column($this->cargoInfo, 'box_dimension'));
            $this->cargoInfo[$key]['quantity'] = $this->cargoInfo[$key]['quantity'] - $tmpBox['total_filled_box_quantity'];
            if ($boxType == 'other_box_load_info' && $boxDimension == $searchedDimension) {
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
        $newEmptySpacePerRow = 0;

        $spaceInfo = [];

        if ($totalNoOfRow == 1) {
            $boxContainInLastRowEmptySpace = $this->getboxContainQuantityInLastRowEmptySpace($boxWidth, $boxLength, $usedBoxLength, $emptySpaceOfLastFilledRow);
        } else {
            $boxContainPerRowInEmptySpace = $this->getIntegerFromFloatingPoint($emptySpacePerRow / $boxWidth);
            $newEmptySpacePerRow = ($boxContainPerRowInEmptySpace > 0) ? $emptySpacePerRow - $boxContainPerRowInEmptySpace * $boxWidth : $emptySpacePerRow;
            $fillableQuantity = ($totalNoOfRow - 1) *  $boxContainPerRowInEmptySpace;
            $boxContainInLastRowEmptySpace = $this->getboxContainQuantityInLastRowEmptySpace($boxWidth, $boxLength, $usedBoxLength, $emptySpaceOfLastFilledRow);
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

        $spaceInfo = [
            "empty_space" => [
                "fully_filled_row_total_length_empty_space" => ($newEmptySpacePerRow > 0) ? ($totalNoOfRow - 1) * $boxLength : $totalNoOfRow * $boxLength,
                "fully_filled_row_empty_space" => $newEmptySpacePerRow,
                "partially_filled_row_length_empty_space" => ($boxContainInLastRowEmptySpace > 0) ? $usedBoxLength - $boxLength : $usedBoxLength,
                "partially_filled_row_empty_space" => ($boxContainInLastRowEmptySpace > 0) ? $emptySpaceOfLastFilledRow - $boxContainInLastRowEmptySpace * $boxWidth : $emptySpaceOfLastFilledRow,
            ],
            "fillableQuantity" => $fillableQuantity
        ];

        // dump($fillableQuantity);
        // return $fillableQuantity;
        return $spaceInfo;
    }

    private function getboxContainQuantityInLastRowEmptySpace($boxWidth, $boxLength, $usedBoxLength, $emptySpaceOfLastFilledRow)
    {
        $ifBoxCanFit = false;
        $boxContainInLastRowEmptySpace = 0;
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

        return $boxContainInLastRowEmptySpace;
    }

    private function getIntegerFromFloatingPoint($floatingPoint)
    {
        if (is_float($floatingPoint)) {
            $arr = explode('.', $floatingPoint);
            $floatingPoint = $arr[0];
        }
        return $floatingPoint;
    }

    private function getFilteredTruckDataKey($uniqueTrucksArray, $filteredTruckInfo, $cargoBoxDimension, $lasCargoBox = false)
    {
        dump($filteredTruckInfo);
        $excludedBoxDimension = $filteredTruckInfo[0]['box_dimension'];
        $tmpSelectedTempTruck = [];

        foreach ($this->cargoInfo as $cargoBoxKey => $cargoInfo) {
            if ($cargoInfo['box_dimension'] == $excludedBoxDimension) {
                continue;
            }
            dump($cargoInfo['box_dimension']);

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
                // dump($item['total_truck']);

                if ($cargoInfo['quantity'] <= 0) {
                    for ($i = 1; $i <= $item['total_truck']; $i++) {
                        $boxDimension = explode('*', $item['used_box_dimension']);
                        $truckDimension = explode('*', $item['truck_dimension']);

                        $emptySpacePerRow = $item['empty_space_per_row'];
                        $emptySpaceOfLastFilledRow = $item['empty_space_of_last_filled_row'];

                        if ($i == $item['total_truck']) {
                            if ($item['total_truck'] == 1) {
                                $lastTruckFilledBoxQuantity = ($item['fillable_box_quantity_in_each_truck'] > $item['total_box_quantity']) ? $item['total_box_quantity'] : $item['fillable_box_quantity_in_each_truck'];
                            } else {
                                $lastTruckFilledBoxQuantity = $item['total_box_quantity'] - (($item['total_truck'] - 1) * $item['fillable_box_quantity_in_each_truck']);
                            }
                            $lastTruckOccupiedRow = $lastTruckFilledBoxQuantity / $item['box_contain_per_row'];
                            $lastTruckOccupiedRow = is_float($lastTruckOccupiedRow) ? intval($lastTruckOccupiedRow) + 1 : $lastTruckOccupiedRow;
                            $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                            $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                        } else {
                            $lastTruckFilledBoxQuantity = $item['fillable_box_quantity_in_each_truck'];
                            $lastTruckOccupiedRow = $item['fillable_row_in_each_truck'];
                            $lastTruckOccupiedLength = $lastTruckOccupiedRow * $boxDimension[0];
                            $lastTruckUnoccupiedLength = $truckDimension[0] - $lastTruckOccupiedLength;
                        }

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
                        $tmpFilteredTruckInfo[$tempKey]["truck_space"][] = [
                            "fully_filled_row_total_length_empty_space" => $lastTruckOccupiedLength,
                            "fully_filled_row_empty_space" => $emptySpacePerRow,
                            "partially_filled_row_length_empty_space" => ($i == $item['total_truck']) ? $emptySpaceOfLastFilledRow : null,
                            "partially_filled_row_empty_space" => ($i == $item['total_truck']) ? $boxLength : null,
                            "fully_unfilled_total_empty_space_by_length" => $lastTruckUnoccupiedLength,
                            "fully_unfilled_empty_space_by_width" => $truckWidth,
                        ];
                    }
                } else {
                    for ($i = 1; $i <= $item['total_truck']; $i++) {
                        // dump($cargoInfo['quantity']);
                        $fillableQuantity = $filledQuantity = $boxContainPerRow = $filledQuantityOnPrevUnoccupiedRowSpace = $boxQuantityOnFullyUnfilledRow = $boxQuantityOnPartiallyFilledRow = 0;
                        $availableTotalNoOfRow = 0;
                        $emptySpaceByLength = $emptySpaceByWidth = 0;
                        $boxDimension = explode('*', $item['used_box_dimension']);
                        $truckDimension = explode('*', $item['truck_dimension']);

                        $emptySpacePerRow = $item['empty_space_per_row'];
                        $emptySpaceOfLastFilledRow = $item['empty_space_of_last_filled_row'];
                        $ifBoxCanFit = false;
                        $tmpBoxLength = $boxLength;
                        $tmpBoxWidth = $boxWidth;
                        $spaceInfo = null;

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

                                $getFillableQuantity = $this->getFillableQuantity($totalNoOfRow, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $item['used_box_dimension']);
                                $fillableQuantity += $getFillableQuantity["fillableQuantity"];
                                $spaceInfo = $getFillableQuantity;
                                $filledQuantityOnPrevUnoccupiedRowSpace = $fillableQuantity;
                            } else if ((($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength > $emptySpaceOfLastFilledRow) || ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength == $boxWidth)) && $boxQuantity > 0) {
                                // $ifBoxCanFit = true;

                                $totalNoOfRow = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxLength);

                                $getFillableQuantity = $this->getFillableQuantity($totalNoOfRow, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $item['used_box_dimension']);
                                $fillableQuantity += $getFillableQuantity["fillableQuantity"];
                                $spaceInfo = $getFillableQuantity;
                                $filledQuantityOnPrevUnoccupiedRowSpace = $fillableQuantity;
                            } else if ($boxWidth <= $emptySpaceOfLastFilledRow && $boxLength <= $emptySpaceOfLastFilledRow && $boxLength != $boxWidth && $boxQuantity > 0) {
                                // $ifBoxCanFit = true;

                                $totalNoOfRowForLength = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxWidth);
                                $getFillableQuantityForLength = $this->getFillableQuantity($totalNoOfRowForLength, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxWidth, $boxLength, $item['used_box_dimension']);
                                $fillableQuantityForLength = $getFillableQuantityForLength["fillableQuantity"];

                                $totalNoOfRowForWidth = $this->getIntegerFromFloatingPoint($lastTruckOccupiedLength / $boxLength);
                                $getFillableQuantityForWidth = $this->getFillableQuantity($totalNoOfRowForWidth, $emptySpaceOfLastFilledRow, $emptySpacePerRow, $boxLength, $boxWidth, $item['used_box_dimension']);
                                $fillableQuantityForWidth = $getFillableQuantityForWidth["fillableQuantity"];

                                if ($fillableQuantityForWidth >= $fillableQuantityForLength) {
                                    $fillableQuantity = $fillableQuantityForWidth;
                                    $spaceInfo = $getFillableQuantityForWidth;
                                } else {
                                    $boxLength = $tmpBoxWidth;
                                    $boxWidth = $tmpBoxLength;
                                    $fillableQuantity = $fillableQuantityForLength;
                                    $spaceInfo = $getFillableQuantityForLength;
                                }
                                $filledQuantityOnPrevUnoccupiedRowSpace = $fillableQuantity;
                            } else {
                                $spaceInfo = [
                                    "empty_space" => [
                                        "fully_filled_row_total_length_empty_space" => $lastTruckOccupiedLength,
                                        "fully_filled_row_empty_space" => $emptySpacePerRow,
                                        "partially_filled_row_length_empty_space" => $emptySpaceOfLastFilledRow,
                                        "partially_filled_row_empty_space" => $boxLength,
                                    ]
                                ];
                            }

                            if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                                $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                            }


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
                                    // dump($lastTruckUnoccupiedLength);
                                    // dump($filledLength);
                                    $emptySpaceByLength = $lastTruckUnoccupiedLength - $filledLength;
                                    $emptySpaceByWidth = ($emptySpaceByLength > 0) ? $truckWidth : 0;
                                }
                            } else {
                                $emptySpaceByLength = $lastTruckUnoccupiedLength;
                                $emptySpaceByWidth = ($emptySpaceByLength > 0) ? $truckWidth : 0;
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
                            $newEmptySpacePerRow = ($boxContainPerRowInEmptySpace > 0) ? $emptySpacePerRow - $boxContainPerRowInEmptySpace * $boxWidth : $emptySpacePerRow;
                            dump("totalNoOfRow: $totalNoOfRow boxLength: $boxLength");
                            $spaceInfo = [
                                "empty_space" => [
                                    "fully_filled_row_total_length_empty_space" => ($fillableQuantity > 0) ? $totalNoOfRow * $boxLength : $lastTruckOccupiedLength,
                                    "fully_filled_row_empty_space" => $newEmptySpacePerRow,
                                    "partially_filled_row_length_empty_space" => null,
                                    "partially_filled_row_empty_space" => null,
                                ],
                                "fillableQuantity" => $fillableQuantity
                            ];
                            // dd("1111");

                            $filledQuantityOnPrevUnoccupiedRowSpace += $totalNoOfRow *  $boxContainPerRowInEmptySpace;
                            if ($filledQuantityOnPrevUnoccupiedRowSpace > 0) {
                                $boxQuantityOnPartiallyFilledRow = $filledQuantity = ($fillableQuantity <= $boxQuantity) ? $fillableQuantity : $boxQuantity;
                            }

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
                                    // dump($lastTruckUnoccupiedLength);
                                    // dump($filledLength);
                                    $emptySpaceByLength = $lastTruckUnoccupiedLength - $filledLength;
                                    $emptySpaceByWidth = ($emptySpaceByLength > 0) ? $truckWidth : 0;
                                }
                            } else {
                                $emptySpaceByLength = $lastTruckUnoccupiedLength;
                                $emptySpaceByWidth = ($emptySpaceByLength > 0) ? $truckWidth : 0;
                            }

                            // dd($filledQuantity);
                            $totalFilledBoxQuantity = ($boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow > $boxQuantity) ? $boxQuantity : $boxQuantityOnFullyUnfilledRow + $boxQuantityOnPartiallyFilledRow;
                            $boxQuantity -= $filledQuantity;
                        }
                        // dd($filteredTruckInfo[$tempKey]);
                        if ($lasCargoBox == true || $filledQuantity == 0) {
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
                        // dump("printing truck space");
                        $tmpFilteredTruckInfo[$tempKey]["truck_space"][] = [
                            "fully_filled_row_total_length_empty_space" => ($spaceInfo != null) ? $spaceInfo['empty_space']['fully_filled_row_total_length_empty_space'] : null,
                            "fully_filled_row_empty_space" => ($spaceInfo != null) ? $spaceInfo['empty_space']['fully_filled_row_empty_space'] : null,
                            "partially_filled_row_length_empty_space" => ($spaceInfo != null) ? $spaceInfo['empty_space']['partially_filled_row_length_empty_space'] : null,
                            "partially_filled_row_empty_space" => ($spaceInfo != null) ? $spaceInfo['empty_space']['partially_filled_row_empty_space'] : null,
                            "fully_unfilled_total_empty_space_by_length" => $emptySpaceByLength,
                            "fully_unfilled_empty_space_by_width" => $emptySpaceByWidth,
                        ];
                        $tmpBoxQuantity = $boxQuantity;
                        // dump($boxQuantity);
                    }
                }
            }
            // }
            dump($tmpFilteredTruckInfo);

            $maxFilledBoxQuantity = $maxFilledBoxTruckKey = null;
            foreach ($tmpFilteredTruckInfo as $tempKey => $item) {
                // if (!array_key_exists('individual_truck', $item)) {
                //     dd($item);
                // }
                $filledBoxQuantity = $item['individual_truck'][0]['total_filled_box_quantity'] + $item['other_box_load_info'][0]['total_filled_box_quantity'];
                // dump($filledBoxQuantity);
                if ($filledBoxQuantity >= $maxFilledBoxQuantity) {
                    $maxFilledBoxQuantity = $filledBoxQuantity;
                    $maxFilledBoxTruckKey = $tempKey;
                }
            }
            $selectedTempTruck = $tmpFilteredTruckInfo[$maxFilledBoxTruckKey];
            dump($selectedTempTruck);
            $tmpSelectedTempTruck[] = $selectedTempTruck;
        }
        // log::info(json_encode($tmpSelectedTempTruck));
        // dump($tmpSelectedTempTruck);

        $maxIndex = null;
        $maxTotalFilledBox = 0;
        $tmpSelectedTempTruckCount = count($tmpSelectedTempTruck);
        $finalSelectedTempTruck = [];

        if ($tmpSelectedTempTruckCount > 1) {
            foreach ($tmpSelectedTempTruck as $index => $item) {
                $truckItem = $item;
                $count = $truckItem['total_truck'];
                if ($count > 1) {
                    $excludedIndexes = [$count - 1]; // Replace with the indexes you want to exclude
                    foreach ($excludedIndexes as $excludeIndex) {
                        unset($truckItem['individual_truck'][$excludeIndex]);
                        unset($truckItem['other_box_load_info'][$excludeIndex]);
                    }
                }
                $individualTruckTotal = array_sum(array_column($truckItem['individual_truck'], 'total_filled_box_quantity'));
                $otherBoxTotal = array_sum(array_column($truckItem['other_box_load_info'], 'total_filled_box_quantity'));

                $totalFilledBox = $individualTruckTotal + $otherBoxTotal;

                // $truckSpaceTotal = [
                //     'fully_filled_row_total_length_empty_space' => $truckItem['truck_space']['fully_filled_row_total_length_empty_space'],
                //     'fully_filled_row_empty_space' => $truckItem['truck_space']['fully_filled_row_empty_space'],
                //     'partially_filled_row_length_empty_space' => $truckItem['truck_space']['partially_filled_row_length_empty_space'],
                //     'partially_filled_row_empty_space' => $truckItem['truck_space']['partially_filled_row_empty_space'],
                //     'fully_unfilled_total_empty_space_by_length' => $truckItem['truck_space']['fully_unfilled_total_empty_space_by_length'],
                //     'fully_unfilled_empty_space_by_width' => $truckItem['truck_space']['fully_unfilled_empty_space_by_width'],
                // ];

                // and find the highest total_filled_box value and its array index
                if ($totalFilledBox > $maxTotalFilledBox) {
                    $maxTotalFilledBox = $totalFilledBox;
                    $maxIndex = $index;
                }
                dump($maxIndex);
                dump("individualTruckTotal: $individualTruckTotal otherBoxTotal: $otherBoxTotal totalFilledBox: $totalFilledBox maxTotalFilledBox: $maxTotalFilledBox");
            }
            $finalSelectedTempTruck = $tmpSelectedTempTruck[$maxIndex];
        } else {
            $finalSelectedTempTruck = $tmpSelectedTempTruck;
        }

        dump($finalSelectedTempTruck);

        $boxInfo = [];
        // dd(count($finalSelectedTempTruck['individual_truck']));
        // log::debug(json_encode($finalSelectedTempTruck));

        $boxInfo = $this->getBoxFilledInfo($finalSelectedTempTruck, $boxInfo);
        dump($boxInfo);

        $finalSelectedTempTruck["box_info"] = [];
        $i = 1;
        foreach ($boxInfo as $boxDimension => $quantity) {
            $finalSelectedTempTruck["box_info"]["box $i"] = $boxDimension;
            $finalSelectedTempTruck["box_info"]["quantity $i"] = $quantity;
            $i++;
        }
        log::debug(json_encode($finalSelectedTempTruck));
        // dd($finalSelectedTempTruck);

        return $finalSelectedTempTruck;
        return $selectedTempTruck;
    }

    private function getBoxFilledInfo($finalSelectedTempTruck, $boxInfo)
    {
        foreach ($finalSelectedTempTruck['individual_truck'] as $key => $truck) {
            $count = count($finalSelectedTempTruck['individual_truck']);
            if ($count > 1 && $count == $key + 1) {
                continue;
            } else {
                $boxDimension = $truck['box_dimension'];
                $totalFilledBoxQuantity = $truck['total_filled_box_quantity'];
                // dump($totalFilledBoxQuantity);

                if ($totalFilledBoxQuantity > 0) {
                    if (!isset($boxInfo[$boxDimension])) {
                        $boxInfo[$boxDimension] = 0;
                    }
                    $boxInfo[$boxDimension] += $totalFilledBoxQuantity;
                }
            }
        }


        foreach ($finalSelectedTempTruck['other_box_load_info'] as $key => $otherBox) {
            $count = count($finalSelectedTempTruck['other_box_load_info']);
            if ($count > 1 && $count == $key + 1) {
                continue;
            } else {
                $boxDimension = $otherBox['box_dimension'];
                $totalFilledBoxQuantity = $otherBox['total_filled_box_quantity'];
                dump($totalFilledBoxQuantity);

                if ($totalFilledBoxQuantity > 0) {
                    if (!isset($boxInfo[$boxDimension])) {
                        $boxInfo[$boxDimension] = 0;
                    }
                    $boxInfo[$boxDimension] += $totalFilledBoxQuantity;
                }
            }
        }

        return $boxInfo;
    }

    private function getFilteredTruckData($uniqueTrucksArray, $boxDimension, $boxQuantity)
    {
        $lowestTotalTruck = PHP_INT_MAX; // Initialize to a high value.
        $truckInfo = [];
        $boxDim = explode('*', $boxDimension);
        // $boxLength = $boxDim[0];
        // $boxWidth = $boxDim[1];
        dump($boxDimension);
        dump($boxQuantity);

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
            // dump($selectedTruckType);
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
        // dump($truckInfo);

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
