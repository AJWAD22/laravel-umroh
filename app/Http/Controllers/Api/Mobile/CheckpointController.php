<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CheckpointResource;
use App\Models\Checkpoint;
use Illuminate\Http\Request;

class CheckpointController extends Controller
{
    public function index(Request $request)
    {
        $checkpoints = Checkpoint::query()
            ->where('branch_id', $request->user()->branch_id)
            ->where('is_active', true)
            ->orderBy('city')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return CheckpointResource::collection($checkpoints);
    }
}
