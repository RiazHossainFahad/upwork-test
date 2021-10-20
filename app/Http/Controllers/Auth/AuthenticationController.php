<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\AddressUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController extends Controller
{
    /**
     * Register an user to the system and issue TOKEN
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => 'bail|required|max:191|string',
            'user_name' => 'bail|required|min:4|max:20|unique:users',
            'email' => 'bail|required|max:191|unique:users',
            'password' => 'bail|required|min:8|max:32|confirmed',
            'user_role' => ['bail', 'nullable', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
            'avatar' => 'bail|nullable|image|dimensions:width=256,height=256'
        ];

        if ($validation = apiValidation($request, $rules)) {
            return $validation;
        }

        $allData = $request->all();
        $allData['password'] = Hash::make($request->password);

        // Upload avatar
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $name = time() . Str::random(60) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('user_images', $name);
            $allData['avatar'] = 'user_images/' . $name;
        }

        $user = User::create($allData);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return apiJsonResponse(Response::HTTP_NOT_ACCEPTABLE, ['email' => 'The provided credentials are incorrect.'], 'Error! Credentials Mis-Matched');
        }

        $data = $this->respondWithToken($user);
        return apiJsonResponse(Response::HTTP_CREATED, $data, "Account created successfully");
    }

    /**
     * Login an existing user to the system and issue TOKEN
     *
     * @param Request $request
     * @return void
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'bail|required|max:191|exists:users',
            'password' => 'bail|required|max:191',
        ];

        if ($validation = apiValidation($request, $rules)) {
            return $validation;
        }

        $user = User::where('email', $request->email)->first();

        // if (!$user || !Hash::check($request->password, $user->password) || ($user->user_type != User::USER_TYPE_CUSTOMER)) {
        if (!$user || !Hash::check($request->password, $user->password)) {
            return apiJsonResponse(Response::HTTP_NOT_ACCEPTABLE, ['email' => 'The provided credentials are incorrect.'], 'Error! Credentials Mis-Matched');
        }

        $data = $this->respondWithToken($user);
        return apiJsonResponse(Response::HTTP_OK, $data);
    }

    /**
     * Get the logged in user profile
     *
     * @return void
     */
    public function getUserProfile()
    {
        return apiJsonResponse(Response::HTTP_OK, new UserResource(Auth::user()));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        // Revoke all tokens...
        Auth::user()->tokens()->delete();

        return apiJsonResponse(Response::HTTP_OK, ['status' => true], 'Successfully logged out!');
    }

    /**
     * Get the token with the response array.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($user)
    {
        // Revoke all tokens...
        $user->tokens()->delete();

        $token = $user->createToken($user->name)->plainTextToken;
        return [
            'access_token' => explode('|', $token)[1],
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ];
    }
}