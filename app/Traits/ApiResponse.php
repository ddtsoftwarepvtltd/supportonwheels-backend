<?php

namespace App\Traits;

use Illuminate\Pagination\CursorPaginator;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    protected function paginated($paginator, ?string $resource = null, string $message = 'Success')
    {
        $rawItems = $paginator->items();
        $items = $resource
            ? $resource::collection(collect($rawItems))->resolve()
            : $rawItems;

        if ($paginator instanceof CursorPaginator) {
            $meta = [
                'type'        => 'cursor',
                'per_page'    => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more'    => $paginator->hasMorePages(),
            ];
        } else {
            $meta = [
                'type'         => 'page',
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'meta'    => $meta,
        ]);
    }
}
