<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\File;
use App\Support\R2Storage;
use App\Support\DebugLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Controller for handling file upload operations to Cloudflare R2.
 * 
 * Provides endpoints for uploading files, deleting files, and generating
 * presigned URLs for direct browser uploads.
 */
final class UploadController extends Controller
{
    /**
     * Upload a file to R2 storage with debug instrumentation.
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @throws ValidationException
     */
    public function storeWithDebug(Request $request): JsonResponse
    {
        $dbg = new DebugLog();
        $debugEnabled = $request->boolean('debug') || $request->header('X-Debug-R2');

        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50 MB max
        ]);

        $disk = Storage::disk('r2');
        $conf = collect(config('filesystems.disks.r2'))
            ->except(['key', 'secret'])
            ->all();

        $file = $request->file('file');
        $path = 'uploads/' . now()->format('Y/m/d/') . $file->hashName();

        $dbg->add('env', [
            'app_env' => config('app.env'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'disk_conf' => $conf,
        ]);

        $dbg->add('incoming_file', [
            'original' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'path' => $path,
        ]);

        try {
            // IMPORTANT: no ACL/visibility param
            $disk->put($path, file_get_contents($file->getRealPath()));
            $url = Storage::disk('r2')->url($path);

            $dbg->add('put_ok', ['path' => $path, 'url' => $url]);

            return response()->json([
                'ok' => true,
                'path' => $path,
                'url' => $url,
                'debug' => $debugEnabled ? $dbg->all() : null,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            $prev = $e->getPrevious();
            $dbg->add('exception', [
                'msg' => $e->getMessage(),
                'prev' => $prev?->getMessage(),
                'class' => get_class($e),
            ]);

            // If it is an AWS exception, extract code/status if present
            if ($prev instanceof \Aws\Exception\AwsException) {
                $dbg->add('aws_exception', [
                    'aws_code' => $prev->getAwsErrorCode(),
                    'aws_msg' => $prev->getAwsErrorMessage(),
                    'aws_type' => $prev->getAwsErrorType(),
                    'status' => $prev->getStatusCode(),
                ]);
            }

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'debug' => $dbg->all(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload a file to R2 storage and create database record.
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file_type' => ['nullable', 'string', 'in:document,image,receipt,other'],
        ]);

        try {
            $file = $request->file('file');
            $userId = auth()->user()?->id;
            $title = $request->input('title');
            $description = $request->input('description');
            $fileType = $request->input('file_type', 'other');
            
            // Determine file type based on MIME type if not specified
            if (!$request->has('file_type')) {
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'image/')) {
                    $fileType = 'image';
                } elseif (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
                    $fileType = 'document';
                }
            }
            
            $fileRecord = R2Storage::putPublicWithRecord(
                $file,
                $userId,
                $fileType,
                $title,
                $description
            );
            
            return response()->json([
                'success' => true,
                'data' => new FileResource($fileRecord),
                'message' => 'File uploaded successfully'
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a file from R2 storage and database.
     * 
     * @param int $id The document ID to delete
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $file = File::find($id);
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Check if user can delete this file
            if ($file->uploaded_by !== auth()->user()?->id && !auth()->user()?->can('delete', $file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this file'
                ], 403);
            }

            $deleted = R2Storage::deleteFile($file);
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete file'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a presigned URL for direct browser upload to R2.
     * 
     * This endpoint creates a presigned PUT URL that allows the frontend
     * to upload files directly to R2 without going through the Laravel server.
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @throws ValidationException
     */
    public function presign(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|max:100',
        ]);

        try {
            // Validate file extension based on content type
            $allowedTypes = [
                'image/jpeg' => ['jpg', 'jpeg'],
                'image/png' => ['png'],
                'image/gif' => ['gif'],
                'image/webp' => ['webp'],
                'application/pdf' => ['pdf'],
                'text/plain' => ['txt'],
                'application/msword' => ['doc'],
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            ];

            $contentType = $request->input('content_type');
            $filename = $request->input('filename');
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!isset($allowedTypes[$contentType]) || !in_array($extension, $allowedTypes[$contentType])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File type not allowed'
                ], 400);
            }

            // Create S3 client for presigned URL generation
            $client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.r2.region', 'auto'),
                'endpoint' => config('filesystems.disks.r2.endpoint'),
                'use_path_style_endpoint' => config('filesystems.disks.r2.use_path_style_endpoint', false),
                'credentials' => new \Aws\Credentials\Credentials(
                    env('R2_ACCESS_KEY_ID'),
                    env('R2_SECRET_ACCESS_KEY')
                ),
            ]);

            // Generate organized key path
            $key = 'uploads/' . date('Y/m/d/') . $filename;
            
            // Create presigned PUT request
            $cmd = $client->getCommand('PutObject', [
                'Bucket' => env('R2_BUCKET'),
                'Key' => $key,
                'ContentType' => $contentType,
            ]);
            
            $presignedRequest = $client->createPresignedRequest($cmd, '+5 minutes');

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => (string) $presignedRequest->getUri(),
                    'method' => 'PUT',
                    'headers' => [
                        'Content-Type' => $contentType,
                    ],
                    'key' => $key,
                    'expires_in' => 300, // 5 minutes
                ],
                'message' => 'Presigned URL generated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating presigned URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file information from database.
     * 
     * @param int $id The document ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $file = File::find($id);
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // For public endpoints, allow access to all files
            // Authorization is handled at the route level
            return response()->json([
                'success' => true,
                'data' => new FileResource($file),
                'message' => 'File information retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving file information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all files regardless of uploader.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $fileType = $request->input('file_type');
            $perPage = $request->input('per_page', 15);
            
            // Get all files without filtering by user
            $query = File::query();
            
            if ($fileType) {
                $query->ofType($fileType);
            }
            
            $files = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => FileResource::collection($files),
                'meta' => [
                    'pagination' => [
                        'current_page' => $files->currentPage(),
                        'last_page' => $files->lastPage(),
                        'per_page' => $files->perPage(),
                        'total' => $files->total(),
                    ]
                ],
                'message' => 'Files retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving files: ' . $e->getMessage()
            ], 500);
        }
    }
}
