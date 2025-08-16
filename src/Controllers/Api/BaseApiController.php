<?php

namespace Ophim\Core\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ophim\Core\Controllers\Controller;

class BaseApiController extends Controller
{
    /**
     * Success response format
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        return response()->json($response, $code);
    }

    /**
     * Error response format
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response format
     */
    protected function paginatedResponse($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl()
            ]
        ]);
    }

    /**
     * Get pagination parameters from request with optional max limit
     */
    protected function getPaginationParams(Request $request, int $maxPerPage = 100): array
    {
        return [
            'page' => max(1, (int) $request->get('page', 1)),
            'per_page' => min($request->get('per_page', 20), $maxPerPage),
        ];
    }

    /**
     * Get sorting parameters from request
     */
    protected function getSortingParams(Request $request, array $allowedFields = []): array
    {
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort field
        if (!empty($allowedFields) && !in_array($sortBy, $allowedFields)) {
            $sortBy = $allowedFields[0] ?? 'updated_at';
        }

        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ];
    }

    /**
     * Apply filters to query builder
     */
    protected function applyFilters($query, Request $request, array $filterMap = []): void
    {
        foreach ($filterMap as $param => $column) {
            if ($request->has($param) && $request->get($param) !== null) {
                $value = $request->get($param);
                
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }
    }

    /**
     * Apply search to query builder
     */
    protected function applySearch($query, Request $request, array $searchFields = []): void
    {
        $search = $request->get('search');
        
        if ($search && !empty($searchFields)) {
            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }
    }
}