@extends('layouts.app')

@section('title', $seo['title'])

@push('styles')
<!-- Leaflet CSS (Local First with CDN fallback) -->
<link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}" />
<style>
    #map {
        cursor: grab !important;
        touch-action: pan-x pan-y !important;
        user-select: none !important;
        -webkit-user-select: none !important;
    }
    #map:active,
    #map.leaflet-drag-moving,
    .leaflet-dragging .leaflet-container,
    .leaflet-dragging .leaflet-grab {
        cursor: grabbing !important;
    }
    /* Prevent browser native image drag on map tiles */
    #map .leaflet-tile {
        pointer-events: none !important;
        user-select: none !important;
        -webkit-user-select: none !important;
        -webkit-user-drag: none !important;
    }
    /* Interactive panes must receive mouse clicks */
    #map .leaflet-marker-pane,
    #map .leaflet-popup-pane,
    #map .leaflet-control-container,
    #map .leaflet-top,
    #map .leaflet-bottom {
        pointer-events: auto !important;
    }

    .leaflet-popup-content-wrapper {
        border-radius: 1rem;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.15), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        border: 1px solid #e2e8f0;
    }
    .leaflet-popup-content {
        margin: 0 !important;
        line-height: 1.4;
    }
    .leaflet-popup-close-button {
        top: 8px !important;
        right: 8px !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border-radius: 9999px !important;
        width: 24px !important;
        height: 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        color: #334155 !important;
        z-index: 10;
    }
    /* Custom Marker Styling */
    .custom-map-marker {
        cursor: pointer;
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        user-select: none;
    }
    .custom-map-marker:hover {
        transform: scale(1.12) translateY(-4px);
        z-index: 9999 !important;
    }
    .custom-marker-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
        border: 2px solid #ffffff;
        white-space: nowrap;
    }
    .custom-marker-pointer {
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        margin: 0 auto;
    }
</style>
@endpush

@section('content')
<!-- Fallback Leaflet CSS in body in case Turbo loads body without merging head styles -->
<link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}" />

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">

    <!-- Header & Type Filter -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm" x-data="{ currentFilter: 'all' }">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span>🗺️</span> ইন্টারেক্টিভ ম্যাপ ভিউ
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">ম্যাপে মাউস দিয়ে ড্র্যাগ করে বা জুম করে আফতাবনগর ও পার্শ্ববর্তী এলাকার প্রপার্টি দেখুন।</p>
        </div>

        <!-- Filter Buttons -->
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <button @click="filterProperties('all'); currentFilter = 'all'"
                    :class="currentFilter === 'all' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                <span>সব প্রপার্টি</span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-full" :class="currentFilter === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-200 text-slate-700'">{{ count($properties) }}</span>
            </button>

            @php
                $landCount = $properties->where('type', 'land')->count();
                $flatCount = $properties->where('type', 'flat')->count();
                $shareCount = $properties->where('type', 'land_share')->count();
                $commCount = $properties->where('type', 'commercial')->count();
            @endphp

            @if($landCount > 0)
            <button @click="filterProperties('land'); currentFilter = 'land'"
                    :class="currentFilter === 'land' ? 'bg-emerald-600 text-white shadow' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
                    class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                <span>🌱 জমি</span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-full" :class="currentFilter === 'land' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800'">{{ $landCount }}</span>
            </button>
            @endif

            @if($flatCount > 0)
            <button @click="filterProperties('flat'); currentFilter = 'flat'"
                    :class="currentFilter === 'flat' ? 'bg-blue-600 text-white shadow' : 'bg-blue-50 text-blue-800 hover:bg-blue-100'"
                    class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                <span>🏢 ফ্ল্যাট</span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-full" :class="currentFilter === 'flat' ? 'bg-blue-700 text-white' : 'bg-blue-100 text-blue-800'">{{ $flatCount }}</span>
            </button>
            @endif

            @if($shareCount > 0)
            <button @click="filterProperties('land_share'); currentFilter = 'land_share'"
                    :class="currentFilter === 'land_share' ? 'bg-amber-600 text-white shadow' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
                    class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                <span>🤝 ল্যান্ড শেয়ার</span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-full" :class="currentFilter === 'land_share' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800'">{{ $shareCount }}</span>
            </button>
            @endif

            @if($commCount > 0)
            <button @click="filterProperties('commercial'); currentFilter = 'commercial'"
                    :class="currentFilter === 'commercial' ? 'bg-purple-600 text-white shadow' : 'bg-purple-50 text-purple-800 hover:bg-purple-100'"
                    class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                <span>🏬 কমার্শিয়াল</span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-full" :class="currentFilter === 'commercial' ? 'bg-purple-700 text-white' : 'bg-purple-100 text-purple-800'">{{ $commCount }}</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Leaflet Map Container with explicit inline style -->
    <div class="bg-white p-2 rounded-3xl border border-slate-200 shadow-sm overflow-hidden relative">
        <div id="map" style="height: calc(100vh - 180px); min-height: 520px; width: 100%; border-radius: 1.25rem; z-index: 10;"></div>

        <!-- Legend Overlay in bottom-left -->
        <div class="absolute bottom-6 left-6 z-[400] pointer-events-none bg-white/95 backdrop-blur-sm border border-slate-200 rounded-xl px-3 py-2 shadow-lg text-[11px] font-semibold flex items-center gap-3">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span> জমি</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block"></span> ফ্ল্যাট</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-600 inline-block"></span> শেয়ার</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-600 inline-block"></span> কমার্শিয়াল</span>
        </div>

        <!-- Center Reset Button in bottom-right -->
        <button onclick="if(window.mapInstance){ window.mapInstance.setView([23.7650, 90.4350], 14); }"
                type="button"
                class="absolute bottom-6 right-6 z-[400] bg-white/95 hover:bg-white text-slate-800 text-xs font-bold px-3 py-2 rounded-xl shadow-lg border border-slate-200 transition flex items-center gap-1.5">
            <span>📍</span>
            <span>আফতাবনগর সেন্টার</span>
        </button>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function() {
    let mapInstance = null;
    let markersLayer = null;
    const allProps = @json($properties);

    // Dynamic loader for Leaflet JS (Local first, unpkg fallback)
    function ensureLeaflet(callback) {
        if (typeof window.L !== 'undefined') {
            callback();
            return;
        }

        let script = document.getElementById('leaflet-script');
        if (!script) {
            script = document.createElement('script');
            script.id = 'leaflet-script';
            script.src = "{{ asset('assets/vendor/leaflet/leaflet.js') }}";
            script.onerror = function() {
                // CDN fallback if local fails
                var fallback = document.createElement('script');
                fallback.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                fallback.onload = function() { callback(); };
                document.head.appendChild(fallback);
            };
            document.head.appendChild(script);
        }

        if (script.loaded) {
            callback();
        } else {
            script.addEventListener('load', () => {
                script.loaded = true;
                callback();
            }, { once: true });
        }
    }

    function destroyMap() {
        if (mapInstance) {
            try {
                mapInstance.off();
                mapInstance.remove();
            } catch(e) {}
            mapInstance = null;
            window.mapInstance = null;
            markersLayer = null;
        }
        const mapElement = document.getElementById('map');
        if (mapElement) {
            if (mapElement._leaflet_id) {
                delete mapElement._leaflet_id;
            }
            mapElement.innerHTML = '';
        }
    }

    function getMarkerIcon(property) {
        let bgColor = '#059669'; // Emerald
        let iconSymbol = '🌱';

        if (property.type === 'flat') {
            bgColor = '#2563eb'; // Blue
            iconSymbol = '🏢';
        } else if (property.type === 'land_share') {
            bgColor = '#d97706'; // Amber
            iconSymbol = '🤝';
        } else if (property.type === 'commercial') {
            bgColor = '#7c3aed'; // Purple
            iconSymbol = '🏬';
        }

        const priceText = property.price || '';

        const html = `
            <div class="custom-map-marker" style="text-align: center;">
                <div class="custom-marker-pill" style="background-color: ${bgColor};">
                    <span>${iconSymbol}</span>
                    <span>${priceText}</span>
                </div>
                <div class="custom-marker-pointer" style="border-top: 6px solid ${bgColor};"></div>
            </div>
        `;

        return L.divIcon({
            html: html,
            className: 'custom-leaflet-marker',
            iconSize: [110, 34],
            iconAnchor: [55, 34],
            popupAnchor: [0, -32]
        });
    }

    function renderMarkers(type) {
        if (!markersLayer || !mapInstance) return;
        markersLayer.clearLayers();

        const filtered = (type === 'all')
            ? allProps
            : allProps.filter(p => p.type === type);

        const bounds = L.latLngBounds();

        filtered.forEach(p => {
            if (!p.lat || !p.lng || isNaN(p.lat) || isNaN(p.lng)) return;

            const latLng = [parseFloat(p.lat), parseFloat(p.lng)];
            bounds.extend(latLng);

            const popupContent = `
                <div style="width:250px; font-family: system-ui, -apple-system, sans-serif; overflow:hidden;">
                    <div style="height:130px; overflow:hidden; background:#f1f5f9; position:relative;">
                        <img src="${p.image}" alt="${p.title}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='/assets/images/og-default.jpg';">
                        <span style="position:absolute; top:8px; left:8px; background:rgba(15,23,42,0.85); color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:9999px;">
                            ${p.area || 'ঢাকা'}
                        </span>
                    </div>
                    <div style="padding:12px;">
                        <h4 style="font-weight:700; color:#0f172a; font-size:13px; margin:0 0 6px 0; line-height:1.3;" class="line-clamp-2">
                            ${p.title}
                        </h4>
                        <div style="color:#64748b; font-size:11px; margin-bottom:6px; display:flex; align-items:center; gap:4px;">
                            <span>📍</span>
                            <span>ব্লক: <strong>${p.block || 'N/A'}</strong> ${p.road ? '| রোড: <strong>' + p.road + '</strong>' : ''}</span>
                        </div>
                        ${p.size ? `<div style="color:#475569; font-size:11px; margin-bottom:8px;">📐 সাইজ: <strong>${p.size}</strong></div>` : ''}
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:8px; padding-top:8px; border-top:1px solid #f1f5f9;">
                            <div style="font-weight:800; color:#059669; font-size:15px;">${p.price}</div>
                            <a href="${p.url}" target="_blank" style="background:#0f172a; color:#ffffff; padding:5px 12px; border-radius:8px; font-weight:600; font-size:11px; text-decoration:none; display:inline-block; transition:all 0.2s;">
                                বিস্তারিত &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            `;

            const marker = L.marker(latLng, { icon: getMarkerIcon(p) }).bindPopup(popupContent);
            markersLayer.addLayer(marker);
        });

        // Fit map bounds to show all filtered markers
        if (bounds.isValid()) {
            mapInstance.fitBounds(bounds.pad(0.15));
        }
    }

    let isInitializing = false;

    function initMap() {
        const mapElement = document.getElementById('map');
        if (!mapElement) return;

        // If already initialized on this element, do nothing
        if (mapInstance && mapElement._leaflet_id) {
            return;
        }

        if (isInitializing) return;
        isInitializing = true;

        destroyMap();

        ensureLeaflet(() => {
            const el = document.getElementById('map');
            if (!el) {
                isInitializing = false;
                return;
            }

            if (el._leaflet_id) {
                delete el._leaflet_id;
            }

            try {
                // Configure local icon path
                if (L.Icon && L.Icon.Default) {
                    L.Icon.Default.imagePath = "{{ asset('assets/vendor/leaflet/images') }}/";
                }

                // Default Aftabnagar center with full mouse dragging & gesture support
                mapInstance = L.map('map', {
                    zoomControl: true,
                    scrollWheelZoom: true,
                    dragging: true,
                    tap: false,                  // CRITICAL FIX: Disables tap emulation that freezes mouse drag on hybrid/touch devices
                    touchZoom: true,
                    boxZoom: true,
                    keyboard: true,
                    bounceAtZoomLimits: false
                }).setView([23.7650, 90.4350], 14);

                // Explicitly guarantee dragging is enabled
                mapInstance.dragging.enable();

                // 100% Free Tile Layers (NO API KEY required, NO watermark)
                const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>'
                });

                const esriStreets = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: 'Tiles &copy; Esri'
                });

                const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: 'Tiles &copy; Esri'
                });

                // Default layer: OpenStreetMap
                osmLayer.addTo(mapInstance);

                // Add Base Layer Switcher (Street / Road / Satellite)
                const baseLayers = {
                    "🗺️ স্ট্রিট ম্যাপ": osmLayer,
                    "🏙️ কালার রোড": esriStreets,
                    "🛰️ স্যাটেলাইট ভিউ": esriSatellite
                };
                L.control.layers(baseLayers, null, { position: 'topright' }).addTo(mapInstance);

                markersLayer = L.layerGroup().addTo(mapInstance);
                renderMarkers('all');

                window.mapInstance = mapInstance;

                // Invalidate size after layout completes
                setTimeout(() => { if (mapInstance) mapInstance.invalidateSize(); }, 150);
                setTimeout(() => { if (mapInstance) mapInstance.invalidateSize(); }, 450);
            } catch (err) {
                console.error('Leaflet initialization error:', err);
            } finally {
                isInitializing = false;
            }
        });
    }

    // Expose filter function globally
    window.filterProperties = function(type) {
        renderMarkers(type);
    };

    // Turbo lifecycle hooks
    document.addEventListener('turbo:before-cache', destroyMap);
    document.addEventListener('turbo:before-render', destroyMap);
    document.addEventListener('turbo:load', initMap);

    // Initial load check
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        setTimeout(initMap, 50);
    }
})();
</script>
@endpush
