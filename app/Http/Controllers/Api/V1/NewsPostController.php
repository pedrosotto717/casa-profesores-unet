<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsPostRequest;
use App\Http\Requests\UpdateNewsPostRequest;
use App\Http\Resources\NewsPostResource;
use App\Services\NewsPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class NewsPostController extends Controller
{
    public function __construct(
        private NewsPostService $newsPostService
    ) {}

    /**
     * Get published news posts for public viewing (paginated).
     */
    public function publicIndex(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 12);
            $posts = $this->newsPostService->publicGetPublished($perPage);

            return response()->json([
                'success' => true,
                'data' => NewsPostResource::collection($posts),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching published news posts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las noticias.',
            ], 500);
        }
    }

    /**
     * Get a single published news post by UUID or slug (public).
     */
    public function publicShow(Request $request, string $identifier): JsonResponse
    {
        try {
            $post = $this->newsPostService->publicFindByUuidOrSlug($identifier);
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new NewsPostResource($post),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching news post', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la noticia.',
            ], 500);
        }
    }

    /**
     * Get all news posts for admin panel (paginated).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $posts = $this->newsPostService->adminGetAll($perPage);

            return response()->json([
                'success' => true,
                'data' => NewsPostResource::collection($posts),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching news posts for admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las noticias.',
            ], 500);
        }
    }

    /**
     * Create a new news post (admin only).
     */
    public function store(StoreNewsPostRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $featuredImage = $request->file('featured_image');
            $userId = $request->user()->id;

            $post = $this->newsPostService->create($validated, $featuredImage, $userId);

            return response()->json([
                'success' => true,
                'data' => new NewsPostResource($post),
                'message' => 'Noticia creada exitosamente.',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating news post', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la noticia.',
            ], 500);
        }
    }

    /**
     * Get a single news post by ID for admin editing.
     */
    public function adminShow(Request $request, int $id): JsonResponse
    {
        try {
            $post = $this->newsPostService->adminFindById($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new NewsPostResource($post),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching news post for admin', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la noticia.',
            ], 500);
        }
    }

    /**
     * Update an existing news post (admin only).
     * Note: Uses POST with _method=PUT to handle multipart/form-data with images.
     */
    public function update(UpdateNewsPostRequest $request, int $id): JsonResponse
    {
        try {
            $post = $this->newsPostService->adminFindById($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $validated = $request->validated();
            $featuredImage = $request->file('featured_image');
            $clearFeaturedImage = (bool) $request->input('clear_featured_image', false);
            $userId = $request->user()->id;

            $updatedPost = $this->newsPostService->update(
                $post,
                $validated,
                $featuredImage,
                $clearFeaturedImage,
                $userId
            );

            return response()->json([
                'success' => true,
                'data' => new NewsPostResource($updatedPost),
                'message' => 'Noticia actualizada exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating news post', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la noticia.',
            ], 500);
        }
    }

    /**
     * Delete a news post (admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $post = $this->newsPostService->adminFindById($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $userId = $request->user()->id;
            $this->newsPostService->delete($post, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Noticia eliminada exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting news post', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la noticia.',
            ], 500);
        }
    }
}

