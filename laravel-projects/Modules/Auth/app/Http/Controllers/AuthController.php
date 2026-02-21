<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\CheckUserAuth;
use Modules\User\Models\User;

class AuthController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function checkUserAuthentication(CheckUserAuth $request)
    {
        $contact = $request->contact;
        $user = User::where('email', $contact)->orWhere('phone', $contact)->first();

        if( $user ){
            return response()->json([
                'data' => $user,
                'message' => 'User authenticated successfully',
                'status' => true,
                'status_code' => 200,
            ]);
        }else{
            return response()->json([
                'message' => 'User not found',
                'status' => false,
                'status_code' => 404,
            ]);
        }
    }
}
