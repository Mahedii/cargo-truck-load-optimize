<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\v1\Truck\{AddDataController as TruckListAddDataController, FetchDataController as TruckListFetchDataController};
use App\Http\Controllers\Admin\v1\Cargo\CargoList\{AddDataController as CargoListAddDataController, FetchDataController as CargoListFetchDataController};
use App\Http\Controllers\Admin\v1\Cargo\CargoInfo\{AddDataController as CargoInfoAddDataController, FetchDataController as CargoInfoFetchDataController};
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

Route::group(['middleware' => ['auth']], function () {

    Route::prefix('cargo')->group(function () {
        Route::get('/list/data', [CargoListFetchDataController::class, 'index'])->name('cargoList.load.allData');
        Route::post('/list/add', CargoListAddDataController::class)->name('cargoList.addData');
        // Route::get('/list/load/{slug}', [CargoListFetchDataController::class, 'fetchData'])->name('cargoList.load.selectedData');

        Route::get('/info/data', [CargoInfoFetchDataController::class, 'index'])->name('cargoInfo.load.allData');
        Route::post('/info/add', CargoInfoAddDataController::class)->name('cargoInfo.addData');
        Route::get('/info/fetch/{cargo_id}', [CargoInfoFetchDataController::class, 'fetchData'])->name('cargoInfo.load.selectedData');

        Route::get('/distribute/data', [DistributeCargoFetchDataController::class, 'index'])->name('distributeCargo.load.allData');
        Route::post('/distribute/add', DistributeCargoAddDataController::class)->name('distributeCargo.addData');
        Route::get('/distribute/{cargo_id}/optimize', [DistributeCargoFetchDataController::class, 'getOptimizedData'])->name('distributeCargo.fetch.optimizedData');
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
