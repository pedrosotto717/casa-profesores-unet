<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\File;
use App\Support\R2Storage;
use App\Support\DebugLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
    public function store(Request $request): JsonResponse
    {
        // --- START DEBUG INSTRUMENTATION ---
        if ($request->boolean('debug') || $request->header('X-Debug-R2') === '1') {
            return $this->storeWithDebug($request);
        }
        // --- END DEBUG INSTRUMENTATION ---

        // Check if user is admin
        $user = Auth::user();
        if (!$user || $user->role->value !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Administrator privileges required.'
            ], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file_type' => ['required', 'string', 'in:document,image,receipt,other'],
            'visibility' => ['nullable', 'string', 'in:publico,privado,restringido'],
        ]);

        try {
            $file = $request->file('file');
            $userId = $user->id;
            $title = $request->input('title');
            $description = $request->input('description');
            $fileType = $request->input('file_type');
            $visibility = $request->input('visibility', 'privado');
            
            // Validate MIME type based on file_type
            $mimeType = $file->getMimeType();
            $allowedMimes = [
                'document' => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ],
                'image' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                'receipt' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf'
                ],
                'other' => [] // Allow any MIME type
            ];
            
            if (!empty($allowedMimes[$fileType]) && !in_array($mimeType, $allowedMimes[$fileType])) {
                return response()->json([
                    'success' => false,
                    'message' => "File type '{$fileType}' does not match the uploaded file format."
                ], 422);
            }
            
            $fileRecord = R2Storage::putPublicWithRecord(
                $file,
                $userId,
                $fileType,
                $title,
                $description
            );

            // Update visibility if different from default
            if ($visibility !== 'publico') {
                $fileRecord->update(['visibility' => $visibility]);
            }
            
            return response()->json([
                'success' => true,
                'data' => new FileResource($fileRecord),
                'message' => 'File uploaded successfully'
            ], 201);
            
        } catch (\Exception $e) {
            // Provide a more detailed error in a standard format
            $response = [
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage(),
            ];
            
            // Add previous exception message if available, which is common for Flysystem
            if ($e->getPrevious()) {
                $response['error_details'] = $e->getPrevious()->getMessage();
            }

            return response()->json($response, 500);
        }
    }

    /**
     * Upload a file to R2 storage with extensive debug instrumentation.
     * This method is triggered by ?debug=1 or X-Debug-R2: 1 header.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function storeWithDebug(Request $request): JsonResponse
    {
        $debugInfo = [];

        try {
            $request->validate(['file' => ['required', 'file', 'max:51200']]);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'error' => 'Validation failed', 'details' => $e->errors()], 422);
        }

        $file = $request->file('file');
        $r2Config = config('filesystems.disks.r2');
        
        // 1. Environment & Config Details
        $debugInfo['environment'] = [
            'app_env' => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'filesystem_default' => config('filesystems.default'),
        ];

        $debugInfo['r2_disk_config'] = collect($r2Config)->except(['key', 'secret'])->all();
        $debugInfo['incoming_file'] = [
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        // 2. R2 Probe using direct AWS SDK
        $probeResult = ['status' => 'pending', 'steps' => []];
        try {
            $s3Client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $r2Config['region'],
                'endpoint' => $r2Config['endpoint'],
                'use_path_style_endpoint' => $r2Config['use_path_style_endpoint'],
                'credentials' => [
                    'key' => env('R2_ACCESS_KEY_ID'),
                    'secret' => env('R2_SECRET_ACCESS_KEY'),
                ],
            ]);
            $probeResult['sdk_client_config'] = 'OK';

            // a) headBucket probe
            try {
                $s3Client->headBucket(['Bucket' => $r2Config['bucket']]);
                $probeResult['steps']['headBucket'] = 'SUCCESS';
            } catch (\Aws\Exception\AwsException $e) {
                $probeResult['steps']['headBucket'] = 'FAILED: ' . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage();
            }

            // b) putObject probe
            $probeKey = 'probe/' . uniqid() . '.txt';
            try {
                $s3Client->putObject([
                    'Bucket' => $r2Config['bucket'],
                    'Key' => $probeKey,
                    'Body' => 'This is a probe file from Laravel.',
                    'ContentType' => 'text/plain',
                ]);
                $probeResult['steps']['putObject'] = 'SUCCESS';

                // c) deleteObject probe
                $s3Client->deleteObject(['Bucket' => $r2Config['bucket'], 'Key' => $probeKey]);
                $probeResult['steps']['deleteObject'] = 'SUCCESS';

            } catch (\Aws\Exception\AwsException $e) {
                $probeResult['steps']['putObject'] = 'FAILED: ' . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage();
            }
            $probeResult['status'] = 'completed';

        } catch (\Throwable $e) {
            $probeResult['status'] = 'FATAL_ERROR';
            $probeResult['error'] = $e->getMessage();
        }
        $debugInfo['r2_probe'] = $probeResult;

        // 3. Flysystem Upload Attempt
        $uploadResult = [];
        try {
            $path = 'uploads/' . now()->format('Y/m/d') . '/' . $file->hashName();
            
            // Use stream for upload
            $stream = fopen($file->getRealPath(), 'r');
            Storage::disk('r2')->put($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $uploadResult['status'] = 'SUCCESS';
            $uploadResult['path'] = $path;
            $uploadResult['url'] = R2Storage::url($path);

            $debugInfo['flysystem_upload'] = $uploadResult;

            return response()->json(['ok' => true, 'data' => $uploadResult, 'debug' => $debugInfo], 201);

        } catch (\Throwable $e) {
            $uploadResult['status'] = 'FAILED';
            $uploadResult['exception_class'] = get_class($e);
            $uploadResult['message'] = $e->getMessage();

            $previous = $e->getPrevious();
            if ($previous) {
                $uploadResult['previous_exception_class'] = get_class($previous);
                $uploadResult['previous_message'] = $previous->getMessage();
                if ($previous instanceof \Aws\Exception\AwsException) {
                    $uploadResult['aws_error'] = [
                        'code' => $previous->getAwsErrorCode(),
                        'message' => $previous->getAwsErrorMessage(),
                        'type' => $previous->getAwsErrorType(),
                        'http_status_code' => $previous->getStatusCode(),
                    ];
                }
            }
            $debugInfo['flysystem_upload'] = $uploadResult;
            
            return response()->json(['ok' => false, 'error' => 'Upload failed.', 'debug' => $debugInfo], 500);
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

            // Check if user is admin
            $user = Auth::user();
            if (!$user || $user->role->value !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Administrator privileges required.'
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

            $user = Auth::user();
            
            // Check visibility access
            if (!$user) {
                // No auth: only public files
                if ($file->visibility->value !== 'publico') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. Authentication required.'
                    ], 401);
                }
            } elseif ($user->role->value !== 'administrador') {
                // Authenticated but not admin: reject restricted files
                if ($file->visibility->value === 'restringido') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. Insufficient privileges.'
                    ], 403);
                }
            }
            // Admin: can access all files
            
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
            $searchTerm = $request->input('q');
            $perPage = $request->input('per_page', 15);
            $user = Auth::user();
            
            // Get files with visibility filtering
            $query = File::query();
            
            if ($fileType) {
                $query->ofType($fileType);
            }
            
            if ($searchTerm) {
                $query->search($searchTerm);
            }
            
            // Apply visibility filters based on authentication and role
            if (!$user) {
                // No auth: only public files
                $query->where('visibility', 'publico');
            } elseif ($user->role->value !== 'administrador') {
                // Authenticated but not admin: public + private (exclude restricted)
                $query->whereIn('visibility', ['publico', 'privado']);
            }
            // Admin: no visibility filter (can see all)
            
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
                    ],
                    'user' => $user,
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

    /**
     * Get only public files (no authentication required).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function publicIndex(Request $request): JsonResponse
    {
        try {
            $fileType = $request->input('file_type');
            $searchTerm = $request->input('q');
            $perPage = $request->input('per_page', 15);
            
            // Get only public files
            $query = File::where('visibility', 'publico');
            
            if ($fileType) {
                $query->ofType($fileType);
            }
            
            if ($searchTerm) {
                $query->search($searchTerm);
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
                    ],
                ],
                'message' => 'Public files retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving public files: ' . $e->getMessage()
            ], 500);
        }
    }
}
