<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Mail\SignupInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    public function inviteForSignup(Request $request)
    {
        $rules = [
            'email' => 'bail|required',
        ];

        if ($validation = apiValidation($request, $rules)) {
            return $validation;
        }

        if (auth()->user()->user_role !== 'admin') {
            return apiJsonResponse(Response::HTTP_FORBIDDEN, ['status' => false], "You are not allowed to invite others.");
        }

        try {
            Mail::to($request->user())->send(new SignupInvitation());
            return apiJsonResponse(Response::HTTP_OK, ['status' => true], 'Successfully send email invitation!');
        } catch (\Throwable $th) {
            return apiJsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, ['status' => false], $th->getMessage());
        }
    }
}