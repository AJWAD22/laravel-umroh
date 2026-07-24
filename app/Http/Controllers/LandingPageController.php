<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Departure;
use App\Services\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
            'packages' => $packages->isNotEmpty() ? $packages : collect($this->fallbackPackageCards()),
            'branches' => $this->activeBranches(),
        ]);
    }

    public function show(Departure $departure): RedirectResponse
    {
        abort_unless($departure->is_public && $departure->status === 'scheduled', 404);

        return redirect()->route('portal.packages.show', $departure);
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
            'whatsapp' => $this->settings->get('company_whatsapp', ''),
            'address' => $this->settings->get('company_address', ''),
            'license' => $this->settings->get('company_license', ''),
            'website' => $this->settings->get('company_website', ''),
            'office_hours' => $this->settings->get('office_hours', ''),
        ];
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

    /** @return array<int, array<string, string>> */
    private function fallbackPackageCards(): array
    {
        return [
            [
                'name' => 'Umroh Hemat',
                'duration' => '9 hari',
                'hotel_class' => 'Bintang 3',
                'makkah_hotel' => 'Hotel Makkah bintang 3',
                'madinah_hotel' => 'Hotel Madinah bintang 3',
                'airline' => 'Lion Air',
                'departure_city' => 'Jakarta',
                'departure_date' => '12 September 2026',
                'price' => 'Rp25.900.000',
                'quota' => '18 kursi tersisa',
                'image' => $this->packageImages()[0],
                'url' => route('portal.register'),
            ],
            [
                'name' => 'Umroh Reguler',
                'duration' => '12 hari',
                'hotel_class' => 'Bintang 4',
                'makkah_hotel' => 'Hotel Makkah bintang 4',
                'madinah_hotel' => 'Hotel Madinah bintang 4',
                'airline' => 'Garuda Indonesia',
                'departure_city' => 'Makassar',
                'departure_date' => '4 Oktober 2026',
                'price' => 'Rp32.500.000',
                'quota' => '12 kursi tersisa',
                'image' => $this->packageImages()[1],
                'url' => route('portal.register'),
            ],
            [
                'name' => 'Umroh Premium',
                'duration' => '12 hari',
                'hotel_class' => 'Bintang 5',
                'makkah_hotel' => 'Hotel Makkah bintang 5',
                'madinah_hotel' => 'Hotel Madinah bintang 5',
                'airline' => 'Saudia Airlines',
                'departure_city' => 'Jakarta',
                'departure_date' => '18 November 2026',
                'price' => 'Rp42.900.000',
                'quota' => '8 kursi tersisa',
                'image' => $this->packageImages()[2],
                'url' => route('portal.register'),
            ],
        ];
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
}
