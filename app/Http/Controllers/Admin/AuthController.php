<?php

namespace App\Http\Controllers\Admin;

use Hash;
use Session;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
use App\Models\Page\VisitorInfo\VisitorInfo;

class AuthController extends Controller
{
    public function signInPage(Request $request)
    {
        $ip = $request->ip();
        // $currentUserInfo = Location::get($ip);

        // $visitorInfoInsert = VisitorInfo::create([
        //     'ip' => $ip,
        //     'countryName' => $currentUserInfo->countryName,
        //     'countryCode' => $currentUserInfo->countryCode,
        //     'regionName' => $currentUserInfo->regionName,
        //     'regionCode' => $currentUserInfo->regionCode,
        //     'cityName' => $currentUserInfo->cityName,
        //     'zipCode' => $currentUserInfo->zipCode,
        //     'isoCode' => $currentUserInfo->isoCode,
        //     'postalCode' => $currentUserInfo->postalCode,
        //     'latitude' => $currentUserInfo->latitude,
        //     'longitude' => $currentUserInfo->longitude,
        //     'metroCode' => $currentUserInfo->metroCode,
        //     'areaCode' => $currentUserInfo->areaCode
        // ]);

        if (Auth::check()) {
            return view('/admin.dashboard');
        } else {
            return view('admin.auth.signin');
        }
    }

    public function userSignin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'Please Enter Your Email',
            'password.required' => 'Please Enter Your password',
        ]);

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->intended('/cargo/distribute/data')->withSuccess("Successfully logged in");
            return redirect()->intended('/admin/dashboard')
                        ->withSuccess('Signed in');
        }

        return redirect()->back()->with('crudMsg', 'Login details are not valid');
        // return redirect()->route("signin-page")->withSuccess('Login details are not valid');
    }

    public function signUpPage()
    {
        return view('admin.auth.signup');
    }

    public function userSignup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required|min:8|max:12',
        ], [
            'name.required' => 'Please Enter Your Name',
            'email.required' => 'Please Enter Your Email',
            'password.required' => 'Please Enter Your password',
        ]);

        $data = $request->all();
        $check = $this->create($data);

        return redirect()->route("dashboard")->withSuccess('Successfully registered');
    }

    public function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
    }


    public function dashboard()
    {
        if (Auth::check()) {
            return view('/admin.dashboard');
        }

        return redirect()->route("signin-page")->withSuccess('You are not allowed to access');
    }

    public function signOut()
    {
        Session::flush();
        Auth::logout();

        return redirect()->route("signin-page");
    }
}
