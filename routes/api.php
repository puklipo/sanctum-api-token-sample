<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        $token = Auth::user()->createToken('user')->plainTextToken;
        return response()->json(['message' => 'Login', 'token' => $token]);
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
})->middleware('guest');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::delete('logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout']);
    });
});
