<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/* ====================API======================= */

/* HELPER FOR API's */

if (!function_exists('apiJsonResponse')) {
    function apiJsonResponse($status_code = Response::HTTP_OK, $data = null, $message = '')
    {
        return response()
            ->json([
                'code' => $status_code,
                'data' => $data ?? [],
                'message' => $message
            ], Response::HTTP_OK);
    }
}

if (!function_exists('apiValidation')) {
    function apiValidation(Request $request, $rule = [], $message = [], $attributes = [])
    {
        $validator = Validator::make($request->all(), $rule, $message, $attributes);
        if ($validator->fails()) {
            $errors = json_decode(json_encode($validator->errors()));
            // $errors = $validator->errors();
            $data = [];
            foreach ($errors as $key => $value) {
                $data[$key] = $value[0];
            }
            // return apiJsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors()->messages(), __('validation.default_message'));
            return apiJsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY, $data, __('validation.default_message'));
        } else {
            return null;
        }
    }
}