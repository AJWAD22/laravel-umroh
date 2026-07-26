<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Departure;
use App\Models\Pilgrim;
use App\Services\SystemSettingService;
use Illuminate\Contracts\View\View;

class LandingPageController extends Controller
{
    public function __construct(private readonly SystemSettingService $settings) {}

    public function __invoke(): View
    {
        $packages = Departure::query()
            ->with([
                'branch:id,name,city',
                'hotels' => fn ($query) => $query->orderBy('departure_hotel.sequence'),
            ])
            ->withCount([
                'registrations as active_registrations_count' => fn ($query) => $query->whereIn('status', ['submitted', 'revision_requested', 'approved', 'in_group']),
            ])
            ->where('is_public', true)
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->orderBy('departure_date')
            ->limit(3)
            ->get()
            ->map(fn (Departure $departure, int $index): array => $this->packageCard($departure, $index));

        return view('public.landing', [
            'travel' => $this->travelProfile(),
            'packages' => $packages,
            'branches' => $this->activeBranches(),
            'stats' => $this->stats(),
        ]);
    }

    public function show(Departure $departure): View
    {
        abort_unless(
            $departure->is_public
                && $departure->status === 'scheduled'
                && $departure->departure_date?->gte(today()),
            404,
        );

        $departure->load(['branch', 'hotels', 'itineraries']);

        return view('public.package-show', [
            'package' => $departure,
            'travel' => $this->travelProfile(),
        ]);
    }

    /** @return array<string, mixed> */
    private function travelProfile(): array
    {
        return [
            'name' => $this->settings->get('company_name', 'Mantau Umroh Travel'),
            'tagline' => $this->settings->get('company_tagline', 'Perjalanan ibadah yang terencana, terpantau, dan penuh ketenangan.'),
            'about' => $this->settings->get('company_about', ''),
            'email' => $this->settings->get('support_email', ''),
            'phone' => $this->settings->get('support_phone', ''),
            'whatsapp' => $this->whatsappNumber(),
            'address' => $this->settings->get('company_address', ''),
            'license' => $this->settings->get('company_license', ''),
            'website' => $this->settings->get('company_website', ''),
            'office_hours' => $this->settings->get('office_hours', ''),
        ];
    }

    private function whatsappNumber(): string
    {
        $number = preg_replace(
            '/\D+/',
            '',
            (string) $this->settings->get('support_phone', ''),
        ) ?: '085947566363';

        if (str_starts_with($number, '0')) {
            return '62'.substr($number, 1);
        }

        return str_starts_with($number, '8') ? '62'.$number : $number;
    }

    /** @return array<string, mixed> */
    private function packageCard(Departure $departure, int $index): array
    {
        $hotels = $departure->hotels;
        $makkahHotel = $hotels->first(fn ($hotel): bool => str_contains(strtolower((string) $hotel->city), 'makkah'))
            ?? $hotels->first();
        $madinahHotel = $hotels->first(fn ($hotel): bool => str_contains(strtolower((string) $hotel->city), 'madinah'))
            ?? $hotels->skip(1)->first()
            ?? $makkahHotel;

        return [
            'name' => $departure->program_name,
            'duration' => $departure->duration_days ? $departure->duration_days.' hari' : 'Durasi menyusul',
            'hotel_class' => $this->hotelClassLabel($hotels->pluck('name')->join(' ')),
            'makkah_hotel' => $makkahHotel?->name ?: 'Hotel Makkah menyusul',
            'madinah_hotel' => $madinahHotel?->name ?: 'Hotel Madinah menyusul',
            'airline' => $departure->airline ?: 'Maskapai menyusul',
            'departure_city' => $departure->departure_airport ?: ($departure->branch?->city ?: 'Kota keberangkatan menyusul'),
            'departure_date' => $departure->departure_date?->translatedFormat('d F Y') ?: 'Jadwal menyusul',
            'price' => $departure->price ? 'Rp'.number_format($departure->price, 0, ',', '.') : 'Hubungi cabang',
            'quota' => $departure->remaining_quota === null ? 'Kuota tersedia' : $departure->remaining_quota.' kursi tersisa',
            'image' => $this->packageImages()[$index % count($this->packageImages())],
            'url' => route('packages.show', $departure),
        ];
    }

    private function hotelClassLabel(string $hotelNames): string
    {
        preg_match('/([3-5])\s*(?:bintang|star|\*)/i', $hotelNames, $match);

        return isset($match[1]) ? 'Bintang '.$match[1] : 'Hotel pilihan';
    }

    /** @return array<int, string> */
    private function packageImages(): array
    {
        return [
            'https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1580418827493-f2b22c0a76cb?auto=format&fit=crop&w=900&q=80',
        ];
    }

    private function activeBranches()
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(6)
            ->get(['name', 'city', 'phone', 'address']);
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        return [
            'active_branches' => Branch::query()->where('is_active', true)->count(),
            'public_packages' => Departure::query()
                ->where('is_public', true)
                ->where('status', 'scheduled')
                ->whereDate('departure_date', '>=', now()->toDateString())
                ->count(),
            'completed_pilgrims' => Pilgrim::query()->where('status', 'completed')->count(),
        ];
    }
}
