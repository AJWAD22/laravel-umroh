<x-app-layout>

    <x-slot:title>
        Histori Tracking
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-2xl font-bold">
                Histori Tracking Jamaah
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Monitoring perjalanan jamaah berdasarkan histori GPS.
            </p>
        </div>
    </x-slot:header>

    <div class="space-y-5">

        <div class="bg-white rounded-xl shadow p-5">

            <div class="grid md:grid-cols-3 gap-4">

                <div>

                    <label class="font-semibold">
                        Jamaah
                    </label>

                    <select
                        id="tracking-pilgrim"
                        class="w-full rounded-lg border mt-2">

                        <option value="">
                            Pilih Jamaah
                        </option>

                        @foreach($pilgrims as $pilgrim)

                            <option value="{{ $pilgrim->id }}">

                                {{ $pilgrim->full_name }}

                                ({{ $pilgrim->registration_number }})

                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <label class="font-semibold">

                        Tanggal

                    </label>

                    <input

                        id="tracking-date"

                        type="date"

                        class="w-full rounded-lg border mt-2"

                        value="{{ now()->toDateString() }}">

                </div>

                <div class="flex items-end">

                    <button

                        id="tracking-load"

                        class="bg-blue-600 text-white rounded-lg px-5 py-2 w-full">

                        Tampilkan Tracking

                    </button>

                </div>

            </div>

        </div>

        <div class="grid lg:grid-cols-4 gap-4">

            <div class="bg-white rounded-xl p-4 shadow">

                <div class="text-gray-500">

                    Total Titik

                </div>

                <div

                    id="tracking-total-points"

                    class="text-2xl font-bold">

                    -

                </div>

            </div>

            <div class="bg-white rounded-xl p-4 shadow">

                <div class="text-gray-500">

                    Total Jarak

                </div>

                <div

                    id="tracking-distance"

                    class="text-2xl font-bold">

                    -

                </div>

            </div>

            <div class="bg-white rounded-xl p-4 shadow">

                <div class="text-gray-500">

                    Mulai

                </div>

                <div

                    id="tracking-start"

                    class="text-lg">

                    -

                </div>

            </div>

            <div class="bg-white rounded-xl p-4 shadow">

                <div class="text-gray-500">

                    Selesai

                </div>

                <div

                    id="tracking-end"

                    class="text-lg">

                    -

                </div>

            </div>

        </div>

        <div class="grid lg:grid-cols-3 gap-5">

            <div class="lg:col-span-2">

                <div class="bg-white rounded-xl shadow overflow-hidden">

                    <div

                        id="tracking-map"

                        data-endpoint="{{ route('monitoring.tracking.data') }}"

                        style="height:650px">

                    </div>

                </div>

            </div>

            <div>

                <div class="bg-white rounded-xl shadow">

                    <div class="p-4 border-b">

                        <h3 class="font-bold">

                            Timeline

                        </h3>

                    </div>

                    <div

                        id="tracking-timeline"

                        class="p-4 space-y-3 overflow-y-auto"

                        style="height:600px">

                        Belum ada data.

                    </div>

                </div>

            </div>

        </div>

    </div>

    @push('styles')

        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet/dist/leaflet.css"/>

    @endpush

    @push('scripts')

        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

        <script src="{{ asset('js/tracking-history.js') }}"></script>

    @endpush

</x-app-layout>