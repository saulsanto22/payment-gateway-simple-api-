<?php

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $code = 200)
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($data instanceof LengthAwarePaginator || $data instanceof Paginator) {
            $payload['meta'] = [
                'current_page' => method_exists($data, 'currentPage') ? $data->currentPage() : null,
                'per_page' => method_exists($data, 'perPage') ? $data->perPage() : null,
                'total' => method_exists($data, 'total') ? $data->total() : null,
                'last_page' => method_exists($data, 'lastPage') ? $data->lastPage() : null,
            ];
            // pastikan data hanya berisi items, bukan obj paginator penuh
            $payload['data'] = $data->items();
        }

        return response()->json($payload, $code);
    }

    public static function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
