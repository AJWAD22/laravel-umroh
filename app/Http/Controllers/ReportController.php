<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exports\ReportExport;
use App\Http\Requests\ReportRequest;
use App\Models\Branch;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportRequest $request, string $type): View
    {
        $dataset = $this->reports->generate($type, $request->validated());

        return view('reports.index', [
            ...$dataset,
            'type' => $type,
            'previewRows' => $dataset['rows']->take(100),
            'branches' => $request->user()->hasRole(UserRole::SuperAdmin->value)
                ? Branch::orderBy('name')->get(['id', 'name'])
                : collect(),
            'canFilterBranches' => $request->user()->hasRole(UserRole::SuperAdmin->value),
        ]);
    }

    public function download(ReportRequest $request, string $type, string $format): Response|BinaryFileResponse
    {
        $dataset = $this->reports->generate($type, $request->validated());
        $filename = "{$type}-{$request->validated('date_from')}-{$request->validated('date_to')}";

        if ($format === 'xlsx') {
            return Excel::download(new ReportExport($dataset['rows'], $dataset['headings']), "{$filename}.xlsx");
        }

        abort_unless($format === 'pdf', 404);

        return Pdf::loadView('reports.pdf', $dataset)
            ->setPaper('a4', count($dataset['headings']) > 7 ? 'landscape' : 'portrait')
            ->download("{$filename}.pdf");
    }
}
