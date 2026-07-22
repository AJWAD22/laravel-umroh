

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import {
    ArrowDown, ArrowRight, ArrowUp, ArrowUpDown, Bell, BookOpen, Building2, ChevronDown, ChevronRight,
    CircleAlert, CircleCheck, createIcons, Database, Inbox, KeyRound, LayoutDashboard,
    Eye, EyeOff, ListFilter, LockKeyhole, LogOut, Mail, Map, MapPinned, Menu, Moon,
    PanelLeftClose, PanelLeftOpen, Pencil, Plane, Plus, RotateCcw, Save, Search, Settings,
    ShieldCheck, Siren, Sun, Trash2, TriangleAlert, UserRound,
    UserRoundCheck, Users, UsersRound, X,
} from 'lucide';

window.Alpine = Alpine;

Alpine.start();

createIcons({
    icons: {
        ArrowDown, ArrowRight, ArrowUp, ArrowUpDown, Bell, BookOpen, Building2, ChevronDown,
        ChevronRight, CircleAlert, CircleCheck, Database, Eye, EyeOff, Inbox, KeyRound,
        LayoutDashboard, ListFilter, LockKeyhole, LogOut, Mail, Map, MapPinned, Menu, Moon,
        PanelLeftClose, PanelLeftOpen, Pencil, Plane, Plus, RotateCcw,
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
                    label: 'Rombongan Baru',
                    data: data.groups,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, .08)',
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
    const map = L.map(monitoringMapElement, { zoomControl: true }).setView([21.4225, 39.8262], 13);
    const pilgrimLayer = L.layerGroup().addTo(map);
    const staffLayer = L.layerGroup().addTo(map);
    const checkpointLayer = L.layerGroup().addTo(map);
    let hasFittedBounds = false;
    let refreshTimer = null;
    let selectedMarkerId = null;
    let activeRequest = null;
    let latestPayload = { markers: [], staff: [], checkpoints: [] };

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const elements = {
        branch: document.getElementById('monitoring-branch'),
        departure: document.getElementById('monitoring-departure'),
        group: document.getElementById('monitoring-group'),
        status: document.getElementById('monitoring-status'),
        search: document.getElementById('monitoring-search'),
        refresh: document.getElementById('monitoring-refresh'),
        reload: document.getElementById('monitoring-reload'),
        reset: document.getElementById('monitoring-reset'),
        showStaff: document.getElementById('monitoring-show-staff'),
        showCheckpoints: document.getElementById('monitoring-show-checkpoints'),
        loading: document.getElementById('monitoring-loading'),
        updated: document.getElementById('monitoring-updated'),
        connection: document.getElementById('monitoring-connection'),
        list: document.getElementById('monitoring-list'),
        listCaption: document.getElementById('monitoring-list-caption'),
        empty: document.getElementById('monitoring-empty'),
        detail: document.getElementById('monitoring-detail'),
        detailContent: document.getElementById('monitoring-detail-content'),
    };

    const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[character]);

    const markerStyle = (marker) => {
        if (marker.type === 'checkpoint') return ['#d97706', 'T'];
        if (marker.type === 'tour-leader') return ['#0284c7', 'TL'];
        if (marker.type === 'muthawwif') return ['#7c3aed', 'M'];
        if (marker.status === 'sos') return ['#dc2626', '!'];
        if (marker.status === 'offline') return ['#64748b', 'J'];
        return ['#16a34a', 'J'];
    };

    const popup = (marker) => {
        if (marker.type === 'checkpoint') {
            return `<div class="min-w-52"><strong>${escapeHtml(marker.name)}</strong><br><span>${escapeHtml(marker.category)} · ${escapeHtml(marker.city)}</span><br><span>${escapeHtml(marker.group || marker.departure || marker.branch || '-')}</span></div>`;
        }

        return `<div class="min-w-52">
            <strong>${escapeHtml(marker.name)}</strong><br>
            <span>${escapeHtml(marker.branch || '-')} · ${escapeHtml(marker.group || '-')}</span><br>
            ${marker.phone ? `<span>${escapeHtml(marker.phone)}</span><br>` : ''}
            ${marker.battery !== undefined && marker.battery !== null ? `<span>Baterai: ${escapeHtml(marker.battery)}%</span><br>` : ''}
            <span>Status: <strong>${escapeHtml(marker.status || marker.type)}</strong></span>
        </div>`;
    };

    const renderDetail = (marker) => {
        selectedMarkerId = marker.id;
        const isCheckpoint = marker.type === 'checkpoint';
        const isPilgrim = marker.type === 'pilgrim';
        const statusColors = {
            online: ['#dcfce7', '#15803d'],
            offline: ['#e2e8f0', '#475569'],
            sos: ['#fee2e2', '#b91c1c'],
        };
        const [statusBackground, statusColor] = statusColors[marker.status] || ['#fef3c7', '#b45309'];
        const initials = marker.name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();
        const photo = marker.photo_url
            ? `<img src="${escapeHtml(marker.photo_url)}" alt="${escapeHtml(marker.name)}" class="size-16 shrink-0 rounded-2xl object-cover">`
            : `<div class="grid size-16 shrink-0 place-items-center rounded-2xl ${isCheckpoint ? 'bg-amber-500' : marker.type === 'muthawwif' ? 'bg-violet-600' : marker.type === 'tour-leader' ? 'bg-sky-600' : 'bg-blue-600'} text-xl font-bold text-white">${isCheckpoint ? 'T' : escapeHtml(initials)}</div>`;
        const hasBattery = marker.battery !== null && marker.battery !== undefined;
        const batteryColor = marker.battery <= 20 ? '#dc2626' : marker.battery <= 50 ? '#d97706' : '#16a34a';
        const updatedAt = marker.updated_at ? new Date(marker.updated_at).toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }) : null;
        const typeLabel = isCheckpoint ? 'Titik Tujuan' : isPilgrim ? 'Detail Jamaah' : marker.type === 'tour-leader' ? 'Tour Leader' : 'Muthawwif';
        const statusLabel = isCheckpoint ? marker.category : marker.status;
        const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(marker.latitude)},${encodeURIComponent(marker.longitude)}`;

        elements.detailContent.innerHTML = `
            <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
                <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">${escapeHtml(typeLabel)}</p>
                <p class="font-semibold">${escapeHtml(marker.registration_number || marker.branch || '')}</p></div>
                <button id="monitoring-detail-close" type="button" class="grid size-9 place-items-center rounded-xl bg-slate-100 text-xl text-slate-600 hover:bg-slate-200 dark:bg-slate-800">×</button>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-4">
                    ${photo}
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-bold">${escapeHtml(marker.name)}</h3>
                        <p class="truncate text-sm text-slate-500">${escapeHtml(marker.phone || marker.address || marker.city || '-')}</p>
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="background:${statusBackground};color:${statusColor}">${escapeHtml(String(statusLabel || '').replaceAll('_', ' ').toUpperCase())}</span>
                    </div>
                </div>

                <dl class="mt-6 space-y-4 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Cakupan</dt><dd class="mt-1 font-medium">${escapeHtml([marker.branch, marker.departure, marker.group].filter(Boolean).join(' · ') || '-')}</dd></div>
                    ${isPilgrim ? `<div><dt class="text-xs uppercase tracking-wide text-slate-400">Tour Leader</dt><dd class="mt-1 font-medium">${escapeHtml(marker.tour_leader || '-')}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Muthawwif</dt><dd class="mt-1 font-medium">${escapeHtml(marker.muthawwif || '-')}</dd></div>` : ''}
                    ${isCheckpoint && marker.description ? `<div><dt class="text-xs uppercase tracking-wide text-slate-400">Keterangan</dt><dd class="mt-1 leading-6">${escapeHtml(marker.description)}</dd></div>` : ''}
                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Koordinat${isCheckpoint && marker.radius ? ` · Radius ${escapeHtml(marker.radius)} m` : ''}</dt>
                        <dd class="mt-1 font-mono text-xs text-slate-500">${Number(marker.latitude).toFixed(7)}, ${Number(marker.longitude).toFixed(7)} · akurasi ±${escapeHtml(marker.accuracy ?? '-')} m</dd></div>
                    ${hasBattery ? `<div><dt class="text-xs uppercase tracking-wide text-slate-400">Baterai</dt>
                        <dd class="mt-2 flex items-center gap-3"><span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200"><span class="block h-full rounded-full" style="width:${Number(marker.battery)}%;background:${batteryColor}"></span></span><strong>${escapeHtml(marker.battery)}%</strong></dd></div>` : ''}
                    ${updatedAt ? `<div><dt class="text-xs uppercase tracking-wide text-slate-400">Waktu Update</dt><dd class="mt-1 font-medium">${escapeHtml(updatedAt)}</dd></div>` : ''}
                </dl>
                <div class="mt-6 grid ${marker.phone ? 'grid-cols-2' : 'grid-cols-1'} gap-2">
                    ${marker.phone ? `<a href="tel:${escapeHtml(marker.phone)}" class="button-secondary">Hubungi</a>` : ''}
                    <a href="${directionsUrl}" target="_blank" rel="noopener" class="button-primary">Buka Navigasi</a>
                </div>
            </div>`;

        elements.detail.classList.remove('hidden');
        document.getElementById('monitoring-detail-close').addEventListener('click', () => {
            selectedMarkerId = null;
            elements.detail.classList.add('hidden');
        });
    };

    const renderList = (markers) => {
        elements.list.innerHTML = '';
        elements.empty.classList.toggle('hidden', markers.length > 0);
        elements.listCaption.textContent = `${markers.length} jamaah sesuai filter`;

        markers.forEach((marker) => {
            const statusTone = marker.status === 'sos' ? 'bg-red-500' : marker.status === 'online' ? 'bg-emerald-500' : 'bg-slate-400';
            const updated = new Date(marker.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'flex w-full items-center gap-3 border-b border-slate-200/80 px-4 py-3 text-left transition hover:bg-white focus:bg-white dark:border-slate-800 dark:hover:bg-slate-900 dark:focus:bg-slate-900';
            item.innerHTML = `<span class="relative grid size-10 shrink-0 place-items-center rounded-2xl bg-slate-200 text-xs font-extrabold text-slate-700 dark:bg-slate-800 dark:text-slate-200">${escapeHtml(marker.name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase())}<i class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-white ${statusTone} dark:border-slate-900"></i></span><span class="min-w-0 flex-1"><strong class="block truncate text-sm">${escapeHtml(marker.name)}</strong><small class="mt-0.5 block truncate text-slate-500">${escapeHtml(marker.group || marker.branch || '-')}</small></span><span class="shrink-0 text-right"><small class="block font-bold text-slate-500">${escapeHtml(updated)}</small><small class="mt-1 block text-[10px] uppercase text-slate-400">${escapeHtml(marker.status)}</small></span>`;
            item.addEventListener('click', () => {
                map.flyTo([marker.latitude, marker.longitude], 17);
                renderDetail(marker);
            });
            elements.list.appendChild(item);
        });
    };

    const addMarker = (marker, layer) => {
        const [color, label] = markerStyle(marker);
        const icon = L.divIcon({
            className: '',
            html: `<span class="monitoring-marker ${marker.type === 'checkpoint' ? 'monitoring-marker-square' : ''}" style="background:${color}">${label}</span>`,
            iconSize: [marker.type === 'tour-leader' ? 34 : 30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -16],
        });
        const leafletMarker = L.marker([marker.latitude, marker.longitude], { icon }).bindPopup(popup(marker)).addTo(layer);
        leafletMarker.on('click', () => renderDetail(marker));

        if (marker.type === 'checkpoint' && marker.radius) {
            L.circle([marker.latitude, marker.longitude], {
                radius: marker.radius,
                color: '#d97706',
                weight: 1,
                fillColor: '#f59e0b',
                fillOpacity: .08,
            }).addTo(layer);
        }
    };

    const renderLayers = () => {
        pilgrimLayer.clearLayers();
        staffLayer.clearLayers();
        checkpointLayer.clearLayers();
        const bounds = [];

        latestPayload.markers.forEach((marker) => { addMarker(marker, pilgrimLayer); bounds.push([marker.latitude, marker.longitude]); });
        if (elements.showStaff.checked) latestPayload.staff.forEach((marker) => { addMarker(marker, staffLayer); bounds.push([marker.latitude, marker.longitude]); });
        if (elements.showCheckpoints.checked) latestPayload.checkpoints.forEach((marker) => { addMarker(marker, checkpointLayer); bounds.push([marker.latitude, marker.longitude]); });
        renderList(latestPayload.markers);

        if (!hasFittedBounds && bounds.length) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
            hasFittedBounds = true;
        }
    };

    const loadMarkers = async () => {
        if (document.hidden) return;
        activeRequest?.abort();
        activeRequest = new AbortController();
        elements.loading.classList.remove('hidden');
        elements.loading.classList.add('grid');
        elements.connection.textContent = 'Memuat';
        elements.connection.className = 'rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300';

        const parameters = new URLSearchParams();
        if (elements.branch?.value) parameters.set('branch_id', elements.branch.value);
        if (elements.departure?.value) parameters.set('departure_id', elements.departure.value);
        if (elements.group?.value) parameters.set('group_id', elements.group.value);
        if (elements.status?.value) parameters.set('status', elements.status.value);
        if (elements.search?.value.trim()) parameters.set('search', elements.search.value.trim());

        try {
            const response = await fetch(`${monitoringMapElement.dataset.endpoint}?${parameters}`, {
                headers: { Accept: 'application/json' },
                signal: activeRequest.signal,
            });
            if (!response.ok) throw new Error('Gagal memuat data monitoring.');

            const payload = await response.json();
            latestPayload = payload;
            renderLayers();

            if (selectedMarkerId) {
                const selectedMarker = [...payload.markers, ...payload.staff, ...payload.checkpoints].find((marker) => marker.id === selectedMarkerId);
                if (selectedMarker) renderDetail(selectedMarker);
                else {
                    selectedMarkerId = null;
                    elements.detail.classList.add('hidden');
                }
            }

            ['total', 'online', 'offline', 'sos', 'staff', 'checkpoints'].forEach((key) => {
                document.getElementById(`monitoring-${key}`).textContent = payload.summary[key];
            });
            elements.updated.textContent = `Data terakhir diperbarui ${new Date(payload.generated_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'medium' })}`;
            elements.connection.textContent = 'Terhubung';
            elements.connection.className = 'rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
        } catch (error) {
            if (error.name === 'AbortError') return;
            elements.updated.textContent = error.message;
            elements.connection.textContent = 'Terputus';
            elements.connection.className = 'rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-700 dark:bg-red-950/40 dark:text-red-300';
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

    const filterOptions = () => {
        const branchId = elements.branch?.value;
        const departureId = elements.departure?.value;
        Array.from(elements.departure.options).forEach((option) => {
            option.hidden = Boolean(branchId && option.dataset.branch && option.dataset.branch !== branchId);
        });
        if (elements.departure.selectedOptions[0]?.hidden) elements.departure.value = '';

        Array.from(elements.group.options).forEach((option) => {
            const wrongBranch = branchId && option.dataset.branch && option.dataset.branch !== branchId;
            const wrongDeparture = departureId && option.dataset.departure !== departureId;
            option.hidden = Boolean(wrongBranch || wrongDeparture);
        });
        if (elements.group.selectedOptions[0]?.hidden) elements.group.value = '';
    };

    const applyFilters = () => { hasFittedBounds = false; filterOptions(); loadMarkers(); };
    let searchTimer;
    elements.branch?.addEventListener('change', applyFilters);
    elements.departure.addEventListener('change', applyFilters);
    elements.group.addEventListener('change', applyFilters);
    elements.status.addEventListener('change', applyFilters);
    elements.search.addEventListener('input', () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(applyFilters, 400); });
    elements.refresh.addEventListener('change', resetRefresh);
    elements.reload.addEventListener('click', loadMarkers);
    elements.showStaff.addEventListener('change', renderLayers);
    elements.showCheckpoints.addEventListener('change', renderLayers);
    elements.reset.addEventListener('click', () => {
        if (elements.branch?.tagName === 'SELECT') elements.branch.value = '';
        elements.departure.value = '';
        elements.group.value = '';
        elements.status.value = '';
        elements.search.value = '';
        applyFilters();
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) loadMarkers(); });

    filterOptions();
    loadMarkers();
    resetRefresh();
}

const sosDetailMapElement = document.getElementById('sos-detail-map');

if (sosDetailMapElement) {
    const lat = Number(sosDetailMapElement.dataset.lat);
    const lng = Number(sosDetailMapElement.dataset.lng);
    const name = sosDetailMapElement.dataset.name || 'Lokasi SOS';
    const map = L.map(sosDetailMapElement, { zoomControl: true }).setView([lat, lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const icon = L.divIcon({
        className: '',
        html: '<span class="monitoring-marker" style="background:#dc2626">!</span>',
        iconSize: [34, 34],
        iconAnchor: [17, 17],
    });

    L.marker([lat, lng], { icon }).bindPopup(`<strong>${name}</strong><br>Lokasi SOS terakhir`).addTo(map).openPopup();
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
