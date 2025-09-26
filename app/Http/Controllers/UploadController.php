<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\File;
use App\Support\R2Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $userId = auth()->id();
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
            if ($file->uploaded_by !== auth()->id() && !auth()->user()->can('delete', $file)) {
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
                'ACL' => 'public-read',
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

            // Check if user can view this file
            if ($file->uploaded_by !== auth()->id() && !auth()->user()->can('view', $file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this file'
                ], 403);
            }

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
     * Get user's uploaded files.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $fileType = $request->input('file_type');
            $perPage = $request->input('per_page', 15);
            
            $query = File::byUser($userId);
            
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
