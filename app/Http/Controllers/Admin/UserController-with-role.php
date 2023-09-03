<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hash;
use Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller{


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | Role Permissions
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    function __construct(){
        
        $this->middleware('permission:user-list|see-user', ['only' => ['userListPage','customUserSignup']]);
        $this->middleware('permission:create-user', ['only' => ['addUserPage','customUserSignup','create']]);
        $this->middleware('permission:edit-user', ['only' => ['loadEditUserPage','editCustomUser']]);
        $this->middleware('permission:delete-user', ['only' => ['deleteUser']]);
        
    }


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | public function index(Request $request)
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function userListPage(){

        $userInformation = User::all();

        return view('admin.users.user-lists',compact('userInformation'));
    }


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | public function create()
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function addUserPage(){

        $roles = Role::pluck('name','name')->all();

        $userInformation = User::all();

        return view('admin.users.add-user',compact('userInformation','roles'));
    }


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | public function store(Request $request)
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function customUserSignup(Request $request){

        $request->validate([
            'nameInput' => 'required',
            'emailInput' => 'required|unique:users,email',
            'passwordInput' => 'required|min:8',
            'roles' => 'required',
        ],
        [
            'nameInput.required' => 'Please Enter Your Name',
            'emailInput.required' => 'Please Enter Your Email',
            'passwordInput.required' => 'Please Enter Your password',
        ]);

        $data = $request->all();
        $user = $this->create($data);

        $user->assignRole($request->input('roles'));

        return redirect()->route("add.user")->with('cus-user-add-msg','User Added Successfully');

    }

    public function create(array $data){

      return User::create([
        'name' => $data['nameInput'],
        'email' => $data['emailInput'],
        'password' => Hash::make($data['passwordInput'])
      ]);
    }


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | public function edit($id)
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
    
    public function loadEditUserPage($id){

        $editUser = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $editUser->roles->pluck('name','name')->all();

        //  //  Query Builder Find Method
        //$editUser = DB::table('User')->where('id',$id)->first();

        return view('admin.users.edit-user',compact('editUser','roles','userRole'));

    }



    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | public function update(Request $request, $id)
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function editCustomUser(Request $request,$id){

        $request->validate([
            'nameInput' => 'required',
            'passwordInput' => 'required|min:8',
        ],
        [
            'nameInput.required' => 'Please Enter Your Name',
            'passwordInput.required' => 'Please Enter Your password',
        ]);

        $input = $request->all();

        // $input['password'] = Hash::make($input['passwordInput']);
        // $user = User::find($id);
        // $user->update($input);

        $user = User::find($id);
        $user->name = $request->nameInput;
        $user->password = Hash::make($request->passwordInput);
        $user->update();

        DB::table('model_has_roles')->where('model_id',$id)->delete();
    
        $user->assignRole($request->input('roles'));
    

        return redirect()->back()->with('cus-user-edit-msg','User Updated Successfully');

    }


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | public function destroy($id)
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function deleteUser($id){

        $deleteUser = User::find($id)->Delete();
        return redirect()->route("user.lists")->with('user-del-msg','User Deleted Successfully');

    }


    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | Get user Role Name
    |
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
    
    public static function getUserRole($id){

        $user = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name')->all();

        //  //  Query Builder Find Method
        //$editUser = DB::table('User')->where('id',$id)->first();

        return $userRole;

    }

}
