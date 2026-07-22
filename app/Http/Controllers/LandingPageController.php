<?php

namespace App\Http\Controllers;

use App\Models\Departure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('public.landing', [
            'packages' => $this->publicPackages()->limit(6)->get(),
        ]);
    }

    public function show(Departure $departure): View
    {
        abort_unless($departure->is_public && $departure->status === 'scheduled', 404);

        return view('public.package-show', [
            'package' => $departure->load(['branch', 'hotels', 'itineraries']),
            'packages' => $this->publicPackages()->whereKeyNot($departure->id)->limit(3)->get(),
        ]);
    }

    private function publicPackages(): Builder
    {
        return Departure::query()
            ->with(['branch', 'hotels', 'itineraries'])
            ->where('is_public', true)
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', today())
            ->orderBy('departure_date');
    }
}
