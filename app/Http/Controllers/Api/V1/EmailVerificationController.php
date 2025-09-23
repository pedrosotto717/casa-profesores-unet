<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class EmailVerificationController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Verify user's email address.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ]);

        $result = $this->userService->verifyEmail(
            $request->integer('id'),
            $request->string('hash')->toString()
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'meta' => [
                'version' => 'v1',
            ],
        ], $statusCode);
    }

}
