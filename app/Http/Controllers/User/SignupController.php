<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use App\Notifications\VerificationCodeNotification;

class SignupController extends Controller
{
    /**
     * Register an user to the system
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => 'bail|required|max:191|string',
            'user_name' => 'bail|required|min:4|max:20',
            'email' => 'bail|required|max:191',
            'password' => 'bail|required|min:8|max:32|confirmed',
        ];

        if ($validation = apiValidation($request, $rules)) {
            return $validation;
        }

        $allData = $request->all();
        $allData['password'] = Hash::make($request->password);
        $allData['status'] = User::INACTIVE;

        $user = User::firstOrCreate(
            [
                'user_name' => $allData['user_name'],
                'email' => $allData['email']
            ],
            $allData
        );

        $random_number = random_int(100000, 999999);
        $user->verification_code = $random_number;
        $user->save();

        // Notify user with the verification code
        $user->notify(new VerificationCodeNotification($random_number));

        return apiJsonResponse(Response::HTTP_CREATED, ['user' => new UserResource($user)], "An email has been sent to " . $user->email . ' with a verification code');
    }

    /**
     * Confirmation of verification and change status
     *
     * @param Request $request
     * @return void
     */
    public function verifyVerificationCode(Request $request)
    {
        $rules = [
            'email' => 'bail|required|max:191',
            'verification_code' => 'bail|required|numeric',
        ];

        if ($validation = apiValidation($request, $rules)) {
            return $validation;
        }

        $user = User::where([
            ['email', $request->email],
            ['verification_code', $request->verification_code]
        ])->first();

        if (!$user) {
            return apiJsonResponse(Response::HTTP_NOT_ACCEPTABLE, ['status' => false], "Invalid email or verification code provided!");
        }

        $user->status = User::ACTIVE;
        $user->save();
        return apiJsonResponse(Response::HTTP_OK, ['status' => true], "You are successfully authenticated. Try log in now.");
    }

    public function updateProfile(Request $request)
    {
        $rules = [
            'name' => 'bail|nullable|max:191|string',
            'user_name' => ['bail', 'nullable', 'min:4', 'max:20', Rule::unique('users')->ignore(auth()->id()),],
            'email' => ['bail', 'nullable', 'max:191', Rule::unique('users')->ignore(auth()->id()),],
            'password' => 'bail|nullable|min:8|max:32|confirmed',
            'avatar' => 'bail|nullable|image|dimensions:width=256,height=256'
        ];

        $messages = [
            'avatar.dimensions' => 'Only allows 256*256 dimensions.'
        ];

        if ($validation = apiValidation($request, $rules, $messages)) {
            return $validation;
        }

        $allData = $request->all();
        if ($request->password) {
            $allData['password'] = Hash::make($request->password);
        }

        // Upload avatar
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $name = time() . Str::random(60) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('user_images', $name);
            $allData['avatar'] = 'user_images/' . $name;
        }

        $user = User::updateOrCreate(['id' => auth()->id()], $allData);

        return apiJsonResponse(Response::HTTP_ACCEPTED, ['user' => new UserResource($user)], "Account created successfully");
    }
}