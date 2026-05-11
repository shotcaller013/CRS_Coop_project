<?php
// app/Http/Controllers/Api/BeneficiaryController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Beneficiary\StoreBeneficiaryRequest;
use App\Http\Requests\Beneficiary\UpdateBeneficiaryRequest;
use App\Http\Resources\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Models\Member;
use App\Services\BeneficiaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function __construct(
        private readonly BeneficiaryService $service
    ) {}

    // GET /api/v1/members/{member}/beneficiaries
    public function index(Member $member): JsonResponse
    {
        $this->authorize('viewAny', Beneficiary::class);

        $primary   = $member->primaryBeneficiaries()->withoutTrashed()->get();
        $secondary = $member->secondaryBeneficiaries()->withoutTrashed()->get();
        $status    = $this->service->getCompletionStatus($member);

        return response()->json([
            'data' => [
                'primary'   => BeneficiaryResource::collection($primary),
                'secondary' => BeneficiaryResource::collection($secondary),
                'status'    => $status,
            ],
        ]);
    }

    // POST /api/v1/members/{member}/beneficiaries
    public function store(StoreBeneficiaryRequest $request, Member $member): JsonResponse
    {
        // Validate share won't exceed 100%
        if ($request->input('type') === 'primary') {
            $remaining = $this->service->getRemainingShare($member->id);
            if ($request->input('share_percentage') > $remaining) {
                return response()->json([
                    'message' => "Only {$remaining}% share is available. Primary beneficiary shares cannot exceed 100%.",
                    'errors'  => ['share_percentage' => ["Only {$remaining}% remaining."]],
                ], 422);
            }
        }

        $validated            = $request->validated();
        $validated['member_id'] = $member->id;
        $beneficiary = $this->service->store($validated);

        return response()->json([
            'data'    => new BeneficiaryResource($beneficiary),
            'message' => 'Beneficiary added.',
        ], 201);
    }

    // GET /api/v1/beneficiaries/{beneficiary}
    public function show(Beneficiary $beneficiary): JsonResponse
    {
        $this->authorize('view', $beneficiary);
        return response()->json(['data' => new BeneficiaryResource($beneficiary)]);
    }

    // PUT /api/v1/beneficiaries/{beneficiary}
    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        // Validate share won't exceed 100% on update
        if ($request->has('share_percentage') && $beneficiary->type === 'primary') {
            $remaining = $this->service->getRemainingShare($beneficiary->member_id, $beneficiary->id);
            if ($request->input('share_percentage') > $remaining) {
                return response()->json([
                    'message' => "Only {$remaining}% share is available.",
                    'errors'  => ['share_percentage' => ["Only {$remaining}% remaining."]],
                ], 422);
            }
        }

        $updated = $this->service->update($beneficiary, $request->validated());

        return response()->json([
            'data'    => new BeneficiaryResource($updated),
            'message' => 'Beneficiary updated.',
        ]);
    }

    // DELETE /api/v1/beneficiaries/{beneficiary}
    public function destroy(Beneficiary $beneficiary): JsonResponse
    {
        $this->authorize('delete', $beneficiary);
        $this->service->delete($beneficiary);

        return response()->json(['message' => 'Beneficiary removed.']);
    }

    // POST /api/v1/members/{member}/beneficiaries/reorder
    public function reorder(Request $request, Member $member): JsonResponse
    {
        $this->authorize('update', new Beneficiary(['member_id' => $member->id]));
        $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|integer',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        $this->service->reorder($member->id, $request->input('items'));

        return response()->json(['message' => 'Order saved.']);
    }

    // GET /api/v1/members/{member}/beneficiaries/status
    public function status(Member $member): JsonResponse
    {
        $this->authorize('viewAny', Beneficiary::class);
        return response()->json([
            'data' => $this->service->getCompletionStatus($member),
        ]);
    }

    // GET /api/v1/members/{member}/beneficiaries/declaration.pdf
    public function declaration(Member $member)
    {
        $this->authorize('viewAny', Beneficiary::class);
        $path = $this->service->generateDeclarationPdf($member);

        return response()->download(
            $path,
            "Beneficiary_Declaration_{$member->member_no}.pdf",
            ['Content-Type' => 'application/pdf']
        )->deleteFileAfterSend(true);
    }
}
