<?php
// app/Http/Controllers/Api/UserManagementController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserManagementResource;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserManagementService $service
    ) {}

    // GET /api/v1/users
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->service->list($request->only(['is_active', 'role', 'search']));

        return response()->json([
            'data' => UserManagementResource::collection($users),
            'meta' => [
                'total'    => $users->count(),
                'active'   => $users->where('is_active', true)->count(),
                'inactive' => $users->where('is_active', false)->count(),
            ],
        ]);
    }

    // POST /api/v1/users
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return response()->json([
            'data'    => new UserManagementResource($user),
            'message' => "User {$user->name} created.",
        ], 201);
    }

    // GET /api/v1/users/{userAccount}
    public function show(User $userAccount): JsonResponse
    {
        $this->authorize('view', $userAccount);
        return response()->json(['data' => new UserManagementResource($userAccount->load('roles'))]);
    }

    // PUT /api/v1/users/{userAccount}
    public function update(UpdateUserRequest $request, User $userAccount): JsonResponse
    {
        $user = $this->service->update($userAccount, $request->validated());

        return response()->json([
            'data'    => new UserManagementResource($user),
            'message' => 'User updated.',
        ]);
    }

    // POST /api/v1/users/{userAccount}/toggle-active
    public function toggleActive(User $userAccount): JsonResponse
    {
        $this->authorize('deactivate', $userAccount);

        $user   = $this->service->toggleActive($userAccount);
        $action = $user->is_active ? 'reactivated' : 'deactivated';

        return response()->json([
            'data'    => new UserManagementResource($user),
            'message' => "User {$user->name} has been {$action}.",
        ]);
    }

    // POST /api/v1/users/{userAccount}/reset-password
    public function resetPassword(User $userAccount): JsonResponse
    {
        $this->authorize('resetPassword', $userAccount);

        $tempPassword = $this->service->resetPassword($userAccount);

        return response()->json([
            'message'          => "Password reset for {$userAccount->name}.",
            'temp_password'    => $tempPassword,
            'must_communicate' => 'Give this temporary password to the user. They will be required to change it on next login.',
        ]);
    }
}
