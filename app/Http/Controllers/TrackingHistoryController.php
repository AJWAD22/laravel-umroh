<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\TrackingHistoryRequest;
use App\Models\Pilgrim;
use App\Services\TrackingHistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class TrackingHistoryController extends Controller
{
    public function __construct(
        private readonly TrackingHistoryService $tracking
    ) {
    }

    public function index(Request $request): View
    {
        Gate::authorize('tracking-history.view');

        $branchId = $request->user()->hasRole(UserRole::SuperAdmin->value)
            ? null
            : $request->user()->branch_id;

        return view('monitoring.tracking-history', [
            'pilgrims' => Pilgrim::query()
                ->with('branch:id,name')
                ->when(
                    $branchId,
                    fn (Builder $query) => $query->where('branch_id', $branchId)
                )
                ->whereIn('status', [
                    'registered',
                    'active',
                    'completed',
                ])
                ->orderBy('full_name')
                ->get([
                    'id',
                    'branch_id',
                    'registration_number',
                    'full_name',
                ]),
        ]);
    }

    public function data(TrackingHistoryRequest $request): JsonResponse
    {
        try {

            $pilgrim = Pilgrim::with('branch:id,name')
                ->findOrFail($request->integer('pilgrim_id'));

            $result = $this->tracking->history(
                $pilgrim,
                CarbonImmutable::parse(
                    $request->validated('date')
                )
            );

            return response()->json($result);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())
                    ->take(5)
                    ->values(),
            ], 500);

        }
    }
}