<?php

namespace App\Http\Controllers;

use App\Models\Departure;
use App\Models\PilgrimRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicRegistrationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'departure_id' => ['required', 'integer', 'exists:departures,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:20'],
            'passport_number' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $departure = Departure::query()
            ->whereKey($data['departure_id'])
            ->where('is_public', true)
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', today())
            ->firstOrFail();

        PilgrimRegistration::query()->create([
            ...$data,
            'branch_id' => $departure->branch_id,
            'status' => 'submitted',
        ]);

        return redirect()
            ->route('packages.show', $departure)
            ->with('success', 'Registrasi berhasil dikirim. Admin cabang akan menghubungi jamaah untuk konfirmasi keberangkatan.');
    }
}
