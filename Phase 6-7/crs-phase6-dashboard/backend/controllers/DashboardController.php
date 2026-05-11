<?php
// app/Http/Controllers/Api/DashboardController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service
    ) {}

    // GET /api/v1/dashboard
    public function index(): JsonResponse
    {
        // Anyone who is authenticated can view the dashboard.
        // Role-sensitive sections (overdue amounts, share capital)
        // are filtered on the frontend based on the user's role.
        return response()->json([
            'data' => $this->service->getData(),
        ]);
    }
}
