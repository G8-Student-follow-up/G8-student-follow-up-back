<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected function successResponse(
        mixed $data = null,
        string $message = "Success",
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorReponse(
        string $message = "Something went wrong",
        int $status = 400,
        mixed $errors = null
    ): JsonResponse{
        return response() -> json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
