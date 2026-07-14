<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\MasterDataRequest;
use App\Models\Pilgrim;
use App\Exports\MasterDataTemplateExport;
use App\Services\MasterDataImportService;
use App\Services\MasterDataService;
use App\Services\MobileActivationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MasterDataController extends Controller
{
    /**
     * Controller pusat untuk halaman Data Master.
     * Service dipisahkan agar aturan bisnis tidak menumpuk di controller.
     */
    public function __construct(
        private readonly MasterDataService $masterData,
        private readonly MobileActivationService $activations,
        private readonly MasterDataImportService $imports,
    ) {}

    /** Menampilkan daftar resource seperti jamaah, rombongan, dan petugas. */
    public function index(Request $request, string $resource): View
    {
        $definition = $this->authorizeResource($request, $resource);
        $sort = in_array($request->string('sort')->toString(), $definition['sort'], true)
            ? $request->string('sort')->toString()
            : $definition['sort'][0];
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $query = $this->masterData->query($resource, $request->user())
            ->when($request->filled('search'), function ($query) use ($request, $definition) {
                $query->where(function ($nested) use ($request, $definition) {
                    foreach ($definition['search'] as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $nested->{$method}($column, 'like', '%'.$request->string('search')->toString().'%');
                    }
                });
            })
            ->when(
                $request->user()->hasRole(UserRole::SuperAdmin->value)
                    && $resource !== 'branches'
                    && $request->filled('branch_id'),
                fn ($query) => $query->where('branch_id', $request->integer('branch_id')),
            )
            ->when($request->filled('status') && in_array($resource, ['pilgrims', 'departures'], true),
                fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy($sort, $direction);

        return view('master-data.index', [
            'resource' => $resource,
            'definition' => $definition,
            'records' => $query->paginate(10)->withQueryString(),
            'options' => $this->masterData->options($resource, $request->user()),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(Request $request, string $resource): View
    {
        $definition = $this->authorizeResource($request, $resource);

        return view('master-data.form', [
            'resource' => $resource,
            'definition' => $definition,
            'record' => null,
            'options' => $this->masterData->options($resource, $request->user()),
        ]);
    }

    public function store(MasterDataRequest $request, string $resource): RedirectResponse
    {
        Gate::authorize('create', $this->masterData->definitionFor($resource)['model']);
        $model = $this->masterData->save($resource, $request->validated(), $request->user());
        $pin = $resource === 'pilgrims'
            ? $this->activations->generatePin($request->user(), $model)
            : null;

        $message = match (true) {
            $pin !== null => "Jamaah berhasil ditambahkan. PIN aktivasi: {$pin}",
            in_array($resource, ['tour-leaders', 'muthawwifs'], true) => "{$this->masterData->definitionFor($resource)['label']} dan akun login aplikasi berhasil dibuat.",
            default => "{$this->masterData->definitionFor($resource)['label']} berhasil ditambahkan.",
        };

        return redirect()->route('master-data.index', $resource)
            ->with('success', $message);
    }

    public function template(Request $request, string $resource): BinaryFileResponse
    {
        abort_unless($resource === 'pilgrims', 404);

        $definition = $this->authorizeResource($request, $resource);
        Gate::authorize('create', $definition['model']);

        return Excel::download(
            new MasterDataTemplateExport(
                $this->imports->headings($resource),
                $this->imports->exampleRows($resource),
            ),
            str($definition['label'])->slug('-').'-template.xlsx',
        );
    }

    public function import(Request $request, string $resource): RedirectResponse
    {
        abort_unless($resource === 'pilgrims', 404);

        $definition = $this->authorizeResource($request, $resource);
        Gate::authorize('create', $definition['model']);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $result = $this->imports->import($resource, $validated['file'], $request->user());

        $message = "{$definition['label']} berhasil diimport. "
            ."Data baru: {$result['created']}, diperbarui: {$result['updated']}.";

        if ($result['pins'] > 0) {
            $message .= " PIN jamaah dibuat: {$result['pins']}.";
        }

        return back()->with('success', $message);
    }

    public function edit(Request $request, string $resource, int $record): View
    {
        $definition = $this->authorizeResource($request, $resource);
        $model = $this->masterData->find($resource, $record, $request->user());
        Gate::authorize('update', $model);

        return view('master-data.form', [
            'resource' => $resource,
            'definition' => $definition,
            'record' => $model,
            'options' => $this->masterData->options($resource, $request->user()),
        ]);
    }

    public function update(MasterDataRequest $request, string $resource, int $record): RedirectResponse
    {
        $model = $this->masterData->find($resource, $record, $request->user());
        Gate::authorize('update', $model);
        $this->masterData->save($resource, $request->validated(), $request->user(), $model);
        if ($resource === 'pilgrims' && $model->activation_pin_generated_at === null) {
            $this->activations->generatePin($request->user(), $model);
        }

        return redirect()->route('master-data.index', $resource)
            ->with('success', "{$this->masterData->definitionFor($resource)['label']} berhasil diperbarui.");
    }

    public function regeneratePin(Request $request, Pilgrim $pilgrim): RedirectResponse
    {
        Gate::authorize('update', $pilgrim);
        $pin = $this->activations->generatePin($request->user(), $pilgrim);

        return back()->with('success', "PIN aktivasi {$pilgrim->full_name} diperbarui: {$pin}");
    }

    public function destroy(Request $request, string $resource, int $record): RedirectResponse
    {
        $this->authorizeResource($request, $resource);
        $model = $this->masterData->find($resource, $record, $request->user());
        Gate::authorize('delete', $model);

        try {
            $staffAccount = in_array($resource, ['tour-leaders', 'muthawwifs'], true)
                ? $model->user
                : null;
            $model->delete();
            if ($staffAccount) {
                $staffAccount->tokens()->delete();
                $staffAccount->forceFill(['is_active' => false])->save();
            }
        } catch (QueryException) {
            return back()->with('error', 'Data masih digunakan dan tidak dapat dihapus.');
        }

        return back()->with('success', 'Data berhasil dihapus.');
    }

    private function authorizeResource(Request $request, string $resource): array
    {
        $definition = $this->masterData->definitionFor($resource);
        Gate::authorize('viewAny', $definition['model']);

        return $definition;
    }
}
