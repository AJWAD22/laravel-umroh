<?php

namespace App\Http\Controllers;

use App\Models\Departure;
use App\Services\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LandingPageController extends Controller
{
    public function __construct(private readonly SystemSettingService $settings) {}

    public function __invoke(): View
    {
        return view('public.landing', [
            'travel' => $this->travelProfile(),
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
}
