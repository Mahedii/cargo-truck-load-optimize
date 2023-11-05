<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\v1\Truck\{AddDataController as TruckListAddDataController, FetchDataController as TruckListFetchDataController};
use App\Http\Controllers\Admin\v1\Cargo\CargoList\{AddDataController as CargoListAddDataController, FetchDataController as CargoListFetchDataController, DeleteDataController as CargoListDeleteDataController};
use App\Http\Controllers\Admin\v1\Cargo\CargoInfo\{AddDataController as CargoInfoAddDataController, FetchDataController as CargoInfoFetchDataController, UpdateDataController as CargoInfoUpdateDataController, DeleteDataController as CargoInfoDeleteDataController};
use App\Http\Controllers\Admin\v1\Cargo\DistributeCargo\{AddDataController as DistributeCargoAddDataController, FetchDataController as DistributeCargoFetchDataController};
use App\Http\Controllers\Admin\v1\VisitorInfo\VisitorUserController;
use App\Http\Controllers\Admin\v1\User\UserProfile\{UpdateDataController as UserProfileUpdateDataController, FetchDataController as UserProfileFetchDataController};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $iframeHtml = '<iframe
                        src="https://carbon.now.sh/embed?bg=rgba%28171%2C+184%2C+195%2C+1%29&t=cobalt&wt=none&l=auto&width=680&ds=true&dsyoff=20px&dsblur=68px&wc=true&wa=true&pv=11px&ph=2px&ln=false&fl=1&fm=Hack&fs=14px&lh=133%25&si=false&es=4x&wm=false&code=%255B%250A%2520%2520%2520%2520%2520%2520%2522scenario_1%2522%2520%253D%253E%2520%25221*1*1.70%252031%2520pallets%2520answer%253A%25201*15.6m%2520trailers%2520%2526%25201*3%2520Ton%2520Pickup%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522scenario_2%2522%2520%253D%253E%2520%25221*1.2*1.70%252020%2520pallets%2520%2526%25200.60*1.2*1.7%252010%2520pallets%2520answer%253A%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522scenario_3%2522%2520%253D%253E%2520%25222*2*1.50%252010%2520pallets%2520answer%253A%25201*15.60%2520%252B%25201*7%2520Ton%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522scenario_4%2522%2520%253D%253E%2520%25221.5*1.5*1.45%25208%2520pallets%2520answer%253A%252013.6m%2520vechile%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522scenario_5%2522%2520%253D%253E%2520%25220.60*1*1.65%252030%2520pallets%2520answer%253A%252012m%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522scenario_6%2522%2520%253D%253E%2520%25220.60*0.60*1.50%25208%2520pallets%2520%2526%25201*1.2*1.50%25209%2520pallets%2520answer%253A%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522scenario_7%2522%2520%253D%253E%2520%25222*2*1.50%25205%2520pallets%2520%2526%25201*0.60*1.45%252022%2520pallets%2520answer%253A%252022p%25206length%252C%25205pc%252010length.%2520Length16%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_1%2522%2520%253D%253E%2520%25221%2520Ton%2520Side%2520grill%25201%25202*1.1*1%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_2%2522%2520%253D%253E%2520%25221%2520Ton%2520Box%25201%25202*1.1*1.10%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_3%2522%2520%253D%253E%2520%25223%2520Ton%2520Side%2520grill%25203%25204*1.85*1.80%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_4%2522%2520%253D%253E%2520%25223%2520Ton%2520Box%25203%25204*1.85*1.70%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_5%2522%2520%253D%253E%2520%25227%2520Ton%2520Side%2520grill%25207%25206*2.40*1.85%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_6%2522%2520%253D%253E%2520%25227%2520Ton%2520Box%25207%25206*2.40*1.70%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_7%2522%2520%253D%253E%2520%252210%2520Ton%2520Side%2520grill%252010%25207*2.4*1.85%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_8%2522%2520%253D%253E%2520%252210%2520Ton%2520Box%252010%25207*2.4*1.75%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_9%2522%2520%253D%253E%2520%252212m%2520Flatbed%2520Box%252021%252011.50*2.45*1.75%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_10%2522%2520%253D%253E%2520%252212m%2520Flatbed%2520Open%252021%252011.50*2.45*1.85%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_11%2522%2520%253D%253E%2520%252212m%2520Flatbed%2520Side%2520grill%252021%252011.50*2.45*1.85%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_12%2522%2520%253D%253E%2520%252213.60m%2520Flatbed%2520Side%2520grill%252021%252013*2.45*1.85%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_13%2522%2520%253D%253E%2520%252213.60m%2520Side%2520curtain%252018%252013*2.45*1.75%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_14%2522%2520%253D%253E%2520%252213.60m%2520Box%252018%252013*2.45*1.75%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_15%2522%2520%253D%253E%2520%252215.60m%2520Side%2520curtain%252018%252014.25*2.45*1.75%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_16%2522%2520%253D%253E%2520%252215.60m%2520Box%252018%252014.25*2.45*1.75%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_17%2522%2520%253D%253E%2520%252215.60m%2520Side%2520grill%252018%252014.25*2.45*1.85%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_18%2522%2520%253D%253E%2520%252214m%2520Low%2520Bed%2520%252050%252014*3*4.5%2522%252C%250A%2520%2520%2520%2520%2520%2520%2522truck_19%2522%2520%253D%253E%2520%252212m%2520Double%2520Axle%2520Heavy%2520Duty%252028%2520Tons%252028%252014.25*2.45*1.75%2522%250A%255D"
                        style="width: 969px; height: 751px; border:0; transform: scale(1); overflow:hidden;"
                        sandbox="allow-scripts allow-same-origin">
                    </iframe>';

    return response($iframeHtml)->header('Content-Type', 'text/html');
    return response()->json([
        "scenario_1" => "1*1*1.70 31 pallets answer: 1*15.6m trailers & 1*3 Ton Pickup",
        "scenario_2" => "1*1.2*1.70 20 pallets & 0.60*1.2*1.7 10 pallets answer:",
        "scenario_3" => "2*2*1.50 10 pallets answer: 1*15.60 + 1*7 Ton",
        "scenario_4" => "1.5*1.5*1.45 8 pallets answer: 13.6m vechile",
        "scenario_5" => "0.60*1*1.65 30 pallets answer: 12m",
        "scenario_6" => "0.60*0.60*1.50 8 pallets & 1*1.2*1.50 9 pallets answer:",
        "scenario_7" => "2*2*1.50 5 pallets & 1*0.60*1.45 22 pallets answer: 22p 6length, 5pc 10length. Length16",
        "truck_1" => "1 Ton Side grill 1 2*1.1*1",
        "truck_2" => "1 Ton Box 1 2*1.1*1.10",
        "truck_3" => "3 Ton Side grill 3 4*1.85*1.80",
        "truck_4" => "3 Ton Box 3 4*1.85*1.70",
        "truck_5" => "7 Ton Side grill 7 6*2.40*1.85",
        "truck_6" => "7 Ton Box 7 6*2.40*1.70",
        "truck_7" => "10 Ton Side grill 10 7*2.4*1.85",
        "truck_8" => "10 Ton Box 10 7*2.4*1.75",
        "truck_9" => "12m Flatbed Box 21 11.50*2.45*1.75",
        "truck_10" => "12m Flatbed Open 21 11.50*2.45*1.85",
        "truck_11" => "12m Flatbed Side grill 21 11.50*2.45*1.85",
        "truck_12" => "13.60m Flatbed Side grill 21 13*2.45*1.85",
        "truck_13" => "13.60m Side curtain 18 13*2.45*1.75",
        "truck_14" => "13.60m Box 18 13*2.45*1.75",
        "truck_15" => "15.60m Side curtain 18 14.25*2.45*1.75",
        "truck_16" => "15.60m Box 18 14.25*2.45*1.75",
        "truck_17" => "15.60m Side grill 18 14.25*2.45*1.85",
        "truck_18" => "14m Low Bed  50 14*3*4.5",
        "truck_19" => "12m Double Axle Heavy Duty 28 Tons 28 14.25*2.45*1.75"
    ]);
});

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|
| ------- Admin Routes Starts Here -------
|
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


Route::get('/admin/signin', [AuthController::class, 'signInPage'])->name('signin-page');
Route::post('user-signin', [AuthController::class, 'userSignin'])->name('user.signin');
Route::get('user-signout', [AuthController::class, 'signOut'])->name('user.signout');

Route::get('/admin/dashboard', [AuthController::class, 'dashboard'])->middleware(['auth'])->name('dashboard');

Route::get('/cargo/distribute/{cargo_id}/optimize', [DistributeCargoFetchDataController::class, 'getOptimizedData'])->name('distributeCargo.fetch.optimizedData');

Route::group(['middleware' => ['auth']], function () {

    Route::prefix('cargo')->group(function () {
        Route::get('/list/data', [CargoListFetchDataController::class, 'index'])->name('cargoList.load.allData');
        Route::post('/list/add', CargoListAddDataController::class)->name('cargoList.addData');
        // Route::get('/list/load/{slug}', [CargoListFetchDataController::class, 'fetchData'])->name('cargoList.load.selectedData');
        Route::get('/list/delete/{slug}', CargoListDeleteDataController::class)->name('cargoList.deleteData');

        Route::get('/info/data', [CargoInfoFetchDataController::class, 'index'])->name('cargoInfo.load.allData');
        Route::post('/info/add', CargoInfoAddDataController::class)->name('cargoInfo.addData');
        Route::get('/info/fetch/{cargo_id}', [CargoInfoFetchDataController::class, 'fetchCargoData'])->name('cargoInfo.load.selectedData');
        Route::get('/box/info/fetch/{slug}', [CargoInfoFetchDataController::class, 'fetchBoxData'])->name('cargoBoxInfo.load.selectedData');
        Route::post('/info/update', CargoInfoUpdateDataController::class)->name('cargoInfo.updateData');
        Route::get('/info/delete/{slug}', CargoInfoDeleteDataController::class)->name('cargoInfo.deleteData');

        Route::get('/distribute/data', [DistributeCargoFetchDataController::class, 'index'])->name('distributeCargo.load.allData');
        Route::post('/distribute/add', DistributeCargoAddDataController::class)->name('distributeCargo.addData');
        Route::post('/distribute/get/optimized-data', [DistributeCargoFetchDataController::class, 'getData'])->name('distributeCargo.get.optimizedData');
        // Route::get('/distribute/{cargo_id}/optimize', [DistributeCargoFetchDataController::class, 'getOptimizedData'])->name('distributeCargo.fetch.optimizedData');
    });

    Route::prefix('truck')->group(function () {
        Route::get('/list/data', [TruckListFetchDataController::class, 'index'])->name('truckList.load.allData');
        Route::post('/list/add', TruckListAddDataController::class)->name('truckList.addData');
        // Route::get('/list/load/{slug}', [TruckListFetchDataController::class, 'fetchData'])->name('truckList.load.selectedData');
    });

    /**
     * user routes
     *
     */
    Route::controller(UserController::class)->group(function () {
        Route::get('/users/add-user', 'addUserPage')->name('add.user');
        Route::post('/users/add-custom-user', 'customUserSignup')->name('add.custom.user');
        Route::get('/users/user-lists', 'userListPage')->name('user.lists');
        Route::get('/users/user-delete/{id}', 'deleteUser')->name('user.delete');
        Route::get('/users/user-edit/{id}', 'loadEditUserPage')->name('user.edit');
        Route::post('/users/edit-custom-user/{id}', 'editCustomUser')->name('edit.custom.user');
    });

    /**
     * user profile routes
     *
     */
    Route::prefix('user-profile')->group(function () {
        Route::get('/data', [UserProfileFetchDataController::class, 'index'])->name('userProfile.load.allData');
        Route::post('/update', UserProfileUpdateDataController::class)->name('userProfile.updateData');
    });

    /**
     * visitor information routes
     *
     */
    Route::get('/visitor-informations', [VisitorUserController::class, 'index'])->name('visitor.infos');

    /**
     * roles & permission related routes
     *
     */
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
});
