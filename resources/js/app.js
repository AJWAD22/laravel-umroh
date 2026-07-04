

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import {
    ArrowDown, ArrowUp, ArrowUpDown, Bell, BookOpen, Building2, ChevronDown, ChevronRight,
    CircleAlert, CircleCheck, createIcons, Database, Inbox, KeyRound, LayoutDashboard,
    ListFilter, LogOut, Map, Menu, Moon, PanelLeftClose, PanelLeftOpen, Pencil, Plane, Plus,
    RotateCcw, Save, Search, Settings, ShieldCheck, Siren, Sun, Trash2, TriangleAlert, UserRound,
    UserRoundCheck, Users, UsersRound, X,
} from 'lucide';

window.Alpine = Alpine;

Alpine.start();

createIcons({
    icons: {
        ArrowDown, ArrowUp, ArrowUpDown, Bell, BookOpen, Building2, ChevronDown, ChevronRight,
        CircleAlert, CircleCheck, Database, Inbox, KeyRound, LayoutDashboard, ListFilter,
        LogOut, Map, Menu, Moon, PanelLeftClose, PanelLeftOpen, Pencil, Plane, Plus, RotateCcw,
        Save, Search, Settings, ShieldCheck, Siren, Sun, Trash2, TriangleAlert, UserRound,
        UserRoundCheck, Users, UsersRound, X,
    },
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();
    window.dispatchEvent(new CustomEvent('confirm-action', {
        detail: {
            form,
            title: form.dataset.confirmTitle,
            message: form.dataset.confirm,
        },
    }));
});

const chartElement = document.getElementById('dashboard-statistics-chart');

if (chartElement && window.dashboardChartData) {
    const data = window.dashboardChartData;

    new Chart(chartElement, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Jamaah Baru',
                    data: data.pilgrims,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, .12)',
                    fill: true,
                    tension: .35,
                },
                {
                    label: 'Keberangkatan',
                    data: data.departures,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, .08)',
                    tension: .35,
                },
                {
                    label: 'SOS',
                    data: data.sos,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, .08)',
                    tension: .35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, .15)' } },
                x: { grid: { display: false } },
            },
        },
    });
}

const monitoringMapElement = document.getElementById('monitoring-map');

if (monitoringMapElement) {
    const map = L.map(monitoringMapElement, { zoomControl: true }).setView([21.4225, 39.8262], 15);
    const markerLayer = L.layerGroup().addTo(map);
    let hasFittedBounds = false;
    let refreshTimer = null;
    let selectedMarkerId = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const elements = {
        branch: document.getElementById('monitoring-branch'),
        group: document.getElementById('monitoring-group'),
        status: document.getElementById('monitoring-status'),
        refresh: document.getElementById('monitoring-refresh'),
        reload: document.getElementById('monitoring-reload'),
        loading: document.getElementById('monitoring-loading'),
        updated: document.getElementById('monitoring-updated'),
        detail: document.getElementById('monitoring-detail'),
        detailContent: document.getElementById('monitoring-detail-content'),
    };

    const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[character]);

    const markerStyle = (marker) => {
        if (marker.type === 'hotel') return ['#7c3aed', 'H'];
        if (marker.type === 'muthawwif') return ['#0891b2', 'M'];
        if (marker.status === 'sos') return ['#dc2626', '!'];
        if (marker.status === 'offline') return ['#64748b', 'J'];
        return ['#16a34a', 'J'];
    };

    const popup = (marker) => {
        if (marker.type === 'hotel') {
            return `<div class="min-w-48"><strong>${escapeHtml(marker.name)}</strong><br>
                <span>${escapeHtml(marker.branch)}</span><br><small>${escapeHtml(marker.address || '-')}</small></div>`;
        }

        return `<div class="min-w-52">
            <strong>${escapeHtml(marker.name)}</strong><br>
            <span>${escapeHtml(marker.branch)} · ${escapeHtml(marker.group || '-')}</span><br>
            ${marker.phone ? `<span>${escapeHtml(marker.phone)}</span><br>` : ''}
            ${marker.battery !== undefined && marker.battery !== null ? `<span>Battery: ${escapeHtml(marker.battery)}%</span><br>` : ''}
            <span>Status: <strong>${escapeHtml(marker.status)}</strong></span>
        </div>`;
    };

    const renderDetail = (marker) => {
        if (marker.type !== 'pilgrim') return;

        selectedMarkerId = marker.id;
        const statusColors = {
            online: ['#dcfce7', '#15803d'],
            offline: ['#e2e8f0', '#475569'],
            sos: ['#fee2e2', '#b91c1c'],
        };
        const [statusBackground, statusColor] = statusColors[marker.status] || statusColors.offline;
        const initials = marker.name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();
        const photo = marker.photo_url
            ? `<img src="${escapeHtml(marker.photo_url)}" alt="${escapeHtml(marker.name)}" class="size-16 shrink-0 rounded-2xl object-cover">`
            : `<div class="grid size-16 shrink-0 place-items-center rounded-2xl bg-blue-600 text-xl font-bold text-white">${escapeHtml(initials)}</div>`;
        const hasBattery = marker.battery !== null && marker.battery !== undefined;
        const batteryColor = marker.battery <= 20 ? '#dc2626' : marker.battery <= 50 ? '#d97706' : '#16a34a';
        const updatedAt = new Date(marker.updated_at).toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });

        elements.detailContent.innerHTML = `
            <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
                <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Detail Jamaah</p>
                <p class="font-semibold">${escapeHtml(marker.registration_number)}</p></div>
                <button id="monitoring-detail-close" type="button" class="grid size-9 place-items-center rounded-xl bg-slate-100 text-xl text-slate-600 hover:bg-slate-200 dark:bg-slate-800">×</button>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-4">
                    ${photo}
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-bold">${escapeHtml(marker.name)}</h3>
                        <p class="truncate text-sm text-slate-500">${escapeHtml(marker.phone || '-')}</p>
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="background:${statusBackground};color:${statusColor}">${escapeHtml(marker.status.toUpperCase())}</span>
                    </div>
                </div>

                <dl class="mt-6 space-y-4 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Cabang & Rombongan</dt><dd class="mt-1 font-medium">${escapeHtml(marker.branch)} · ${escapeHtml(marker.group || '-')}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Tour Leader</dt><dd class="mt-1 font-medium">${escapeHtml(marker.tour_leader || '-')}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Muthawwif</dt><dd class="mt-1 font-medium">${escapeHtml(marker.muthawwif || '-')}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Lokasi Terakhir</dt><dd class="mt-1 font-medium">${escapeHtml(marker.location_name || '-')}</dd>
                        <dd class="mt-1 font-mono text-xs text-slate-500">${Number(marker.latitude).toFixed(7)}, ${Number(marker.longitude).toFixed(7)} · akurasi ±${escapeHtml(marker.accuracy ?? '-')} m</dd></div>
                    ${hasBattery ? `<div><dt class="text-xs uppercase tracking-wide text-slate-400">Battery</dt>
                        <dd class="mt-2 flex items-center gap-3"><span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200"><span class="block h-full rounded-full" style="width:${Number(marker.battery)}%;background:${batteryColor}"></span></span><strong>${escapeHtml(marker.battery)}%</strong></dd></div>` : ''}
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Waktu Update</dt><dd class="mt-1 font-medium">${escapeHtml(updatedAt)}</dd></div>
                </dl>
            </div>`;

        elements.detail.classList.remove('hidden');
        document.getElementById('monitoring-detail-close').addEventListener('click', () => {
            selectedMarkerId = null;
            elements.detail.classList.add('hidden');
        });
    };

    const loadMarkers = async () => {
        elements.loading.classList.remove('hidden');
        elements.loading.classList.add('grid');

        const parameters = new URLSearchParams();
        if (elements.branch?.value) parameters.set('branch_id', elements.branch.value);
        if (elements.group?.value) parameters.set('group_id', elements.group.value);
        if (elements.status?.value) parameters.set('status', elements.status.value);

        try {
            const response = await fetch(`${monitoringMapElement.dataset.endpoint}?${parameters}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Gagal memuat data monitoring.');

            const payload = await response.json();
            markerLayer.clearLayers();
            const bounds = [];

            payload.markers.forEach((marker) => {
                const [color, label] = markerStyle(marker);
                const icon = L.divIcon({
                    className: '',
                    html: `<span class="monitoring-marker" style="background:${color}">${label}</span>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15],
                    popupAnchor: [0, -16],
                });

                const leafletMarker = L.marker([marker.latitude, marker.longitude], { icon })
                    .bindPopup(popup(marker))
                    .addTo(markerLayer);
                leafletMarker.on('click', () => renderDetail(marker));
                bounds.push([marker.latitude, marker.longitude]);
            });

            if (selectedMarkerId) {
                const selectedMarker = payload.markers.find((marker) => marker.id === selectedMarkerId);
                if (selectedMarker) renderDetail(selectedMarker);
                else {
                    selectedMarkerId = null;
                    elements.detail.classList.add('hidden');
                }
            }

            if (!hasFittedBounds && bounds.length) {
                map.fitBounds(bounds, { padding: [35, 35], maxZoom: 16 });
                hasFittedBounds = true;
            }

            ['total', 'online', 'offline', 'sos'].forEach((key) => {
                document.getElementById(`monitoring-${key}`).textContent = payload.summary[key];
            });
            elements.updated.textContent = `Diperbarui ${new Date(payload.generated_at).toLocaleTimeString('id-ID')}`;
        } catch (error) {
            elements.updated.textContent = error.message;
        } finally {
            elements.loading.classList.add('hidden');
            elements.loading.classList.remove('grid');
        }
    };

    const resetRefresh = () => {
        window.clearInterval(refreshTimer);
        const interval = Number(elements.refresh.value);
        if (interval > 0) refreshTimer = window.setInterval(loadMarkers, interval);
    };

    const filterGroups = () => {
        const branchId = elements.branch?.value;
        Array.from(elements.group.options).forEach((option) => {
            option.hidden = Boolean(branchId && option.dataset.branch && option.dataset.branch !== branchId);
        });
        if (elements.group.selectedOptions[0]?.hidden) elements.group.value = '';
    };

    elements.branch?.addEventListener('change', () => { filterGroups(); loadMarkers(); });
    elements.group.addEventListener('change', loadMarkers);
    elements.status.addEventListener('change', loadMarkers);
    elements.refresh.addEventListener('change', resetRefresh);
    elements.reload.addEventListener('click', loadMarkers);

    filterGroups();
    loadMarkers();
    resetRefresh();
}

const trackingMapElement = document.getElementById('tracking-map');

if (trackingMapElement) {
    const map = L.map(trackingMapElement).setView([21.4225, 39.8262], 14);
    const routeLayer = L.layerGroup().addTo(map);
    const pilgrimSelect = document.getElementById('tracking-pilgrim');
    const dateInput = document.getElementById('tracking-date');
    const loadButton = document.getElementById('tracking-load');
    const loading = document.getElementById('tracking-loading');
    const empty = document.getElementById('tracking-empty');
    const timeline = document.getElementById('tracking-timeline');
    const person = document.getElementById('tracking-person');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const setText = (id, value) => {
        const element = document.getElementById(id);
        element.textContent = `${value ?? '—'}${value !== null && value !== undefined ? element.dataset.suffix : ''}`;
    };

    const renderTimeline = (points) => {
        timeline.innerHTML = '';
        points.forEach((point, index) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'relative flex w-full gap-3 pb-5 text-left last:pb-0';
            const time = new Date(point.recorded_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            item.innerHTML = `
                <span class="relative z-10 grid size-7 shrink-0 place-items-center rounded-full ${index === 0 ? 'bg-emerald-600' : index === points.length - 1 ? 'bg-red-600' : 'bg-blue-600'} text-[10px] font-bold text-white">${point.sequence}</span>
                ${index < points.length - 1 ? '<span class="absolute left-[13px] top-7 h-[calc(100%-1.25rem)] w-px bg-slate-200"></span>' : ''}
                <span><strong class="block text-sm">Pukul ${time}</strong>
                <small class="mt-1 block text-slate-500">${Number(point.latitude).toFixed(7)}, ${Number(point.longitude).toFixed(7)}</small>
                <small class="block text-slate-400">Akurasi ${point.accuracy ?? '-'} m · Battery ${point.battery ?? '-'}%</small></span>`;
            item.addEventListener('click', () => map.flyTo([point.latitude, point.longitude], 18));
            timeline.appendChild(item);
        });
    };

    const loadHistory = async () => {
        if (!pilgrimSelect.value || !dateInput.value) {
            pilgrimSelect.focus();
            return;
        }

        loading.classList.remove('hidden');
        loading.classList.add('grid');

        try {
            const query = new URLSearchParams({ pilgrim_id: pilgrimSelect.value, date: dateInput.value });
            const response = await fetch(`${trackingMapElement.dataset.endpoint}?${query}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Histori perjalanan gagal dimuat.');
            const payload = await response.json();
            const coordinates = payload.points.map((point) => [point.latitude, point.longitude]);

            routeLayer.clearLayers();
            if (coordinates.length) {
                L.polyline(coordinates, { color: '#2563eb', weight: 5, opacity: .8 }).addTo(routeLayer);
                payload.points.forEach((point, index) => {
                    const color = index === 0 ? '#16a34a' : index === payload.points.length - 1 ? '#dc2626' : '#2563eb';
                    L.circleMarker([point.latitude, point.longitude], {
                        radius: index === 0 || index === payload.points.length - 1 ? 7 : 4,
                        color: '#fff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 1,
                    }).bindTooltip(`Titik ${point.sequence} · ${new Date(point.recorded_at).toLocaleTimeString('id-ID')}`).addTo(routeLayer);
                });
                map.fitBounds(coordinates, { padding: [35, 35], maxZoom: 17 });
            }

            setText('tracking-total-points', payload.summary.total_points);
            setText('tracking-distance', payload.summary.total_distance_km);
            setText('tracking-start', payload.summary.started_at ? new Date(payload.summary.started_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : null);
            setText('tracking-end', payload.summary.ended_at ? new Date(payload.summary.ended_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : null);
            person.textContent = `${payload.pilgrim.name} · ${payload.pilgrim.registration_number} · Data GPS`;
            renderTimeline(payload.points);
            empty.classList.toggle('hidden', coordinates.length > 0);
        } catch (error) {
            person.textContent = error.message;
        } finally {
            loading.classList.add('hidden');
            loading.classList.remove('grid');
        }
    };

    loadButton.addEventListener('click', loadHistory);
}

const sosDetailMapElement = document.getElementById('sos-detail-map');

if (sosDetailMapElement) {
    const latitude = Number(sosDetailMapElement.dataset.latitude);
    const longitude = Number(sosDetailMapElement.dataset.longitude);
    const map = L.map(sosDetailMapElement).setView([latitude, longitude], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const popupContent = document.createElement('div');
    const popupName = document.createElement('strong');
    popupName.textContent = sosDetailMapElement.dataset.name;
    popupContent.append(popupName, document.createElement('br'), document.createTextNode('Lokasi laporan SOS'));

    L.circleMarker([latitude, longitude], {
        radius: 10,
        color: '#fff',
        weight: 3,
        fillColor: '#dc2626',
        fillOpacity: 1,
    }).bindPopup(popupContent).addTo(map).openPopup();
}
