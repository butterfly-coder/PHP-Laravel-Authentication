<?php


namespace App\Http\Controllers\API;

use App\Models\Favorite;
use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationFormRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public $loginAfterSignUp = true;

    public function login(Request $request) {
        try {
            $input = $request->only('email', 'password');
            $token = null;
            $user = User::where(['email' => $request->email, 'is_active' => config('global.users.active')])->first();
            if( !$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account was deleted',
                ], 401);
            }
            if (!$token = JWTAuth::attempt($input)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Email or Password',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => auth()->user(),
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
            ]);
        }
    }

    public function register(RegistrationFormRequest $request)
    {

        $name = $request->name;

        $email = $request->email;
        $password = $request->password;
        $user = User::where(['email' => $email])->first();

        if(count(User::where(['email' => $email, 'is_active' => config('global.users.active')])->get())){
            return response()->json([
                'success'   =>  false,
                'message'   =>  'Email address is already existed'
            ], 300);
        }

        try {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = bcrypt($password);
            $user->is_active = config('global.users.active');
            $user->favorite_id = 1;
            $user->save();

            return $this->login($request);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user can not register'
            ], 500);
        }
    }

    public function setFavorite(Request $request) {
        try {
            $favorite_id = $request->favorite_id;
            $user = auth()->user();
            $user->update(['favorite_id'=>$favorite_id]);
            return response() -> json([
               'success' => true,
               'user'   => $user,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, favorite can not be selected'
            ], 500);
        }
    }

    public function getUserInfo(){
        try {
            $user = auth()->user();
            $favourite_list = Favorite::all();
            return response() -> json([
               'success'    => true,
               'user'       => $user,
               'favorite_list' => $favourite_list,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, cannot get user info'
            ], 500);
        }
    }

    public function deleteAccount(Request $request) {
        try {
            $user = auth()->user();
            $user->update(['is_active'=>config('global.users.deactive')]);
            return response() -> json([
                'success'    => true,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, cannot delete user'
            ], 500);
        }
    }
}
