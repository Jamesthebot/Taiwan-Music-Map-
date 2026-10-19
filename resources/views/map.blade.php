<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://unpkg.com/leaflet@latest/dist/leaflet.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { height: 100%; }
        #map { height: 100vh; width: 100%; }
    </style>
</head>
<body>
    <div id="map"></div>
    <a href="{{ route('dashboard') }}" style="position:fixed;top:12px;right:12px;z-index:1000;background:white;border:1px solid #ccc;border-radius:6px;padding:6px 14px;font-size:14px;text-decoration:none;color:#333;box-shadow:0 1px 4px rgba(0,0,0,0.2);">← Back</a>

    <script src="https://unpkg.com/leaflet@latest/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

    <script>
        const twBounds = [
            [21.729034, 122.484550],
            [25.397551, 119.792932],
        ];

        const map = L.map("map", {
            zoomControl: true,
            maxBounds: twBounds,
            maxBoundsViscosity: 1.0,
            minZoom: 7,
        }).setView([25.0330, 121.5654], 13);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO'
        }).addTo(map);

        const competitions = @json($competitions);
        const markers = L.markerClusterGroup({
            maxClusterRadius: 0.5,
        });

        competitions.forEach(c => {
            if(c.lat && c.lng) {
                const marker = L.marker([c.lat, c.lng])
                    .bindPopup(`
                        <b>名稱/地區: ${c.name}</b><br>
                        ${c.location}<br>
                        ${c.date}<br>
                        <a href="${c.source_url}" target="_blank">More Info</a><br><br>
                        <button id="save-btn-${c.id}" onclick="toggleSave(${c.id})" style="cursor:pointer;padding:2px 10px;background:#3b82f6;color:white;border:none;border-radius:4px;">
                            ${c.saved ? 'Unsave' : 'Save'}
                        </button>
                    `);
                markers.addLayer(marker);
            }
        });

        map.addLayer(markers);

        const youIcon = L.divIcon({
            html: '<div style="width:14px;height:14px;background:#3b82f6;border:2px solid white;border-radius:50%;box-shadow:0 0 0 3px rgba(59,130,246,0.4);"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
            className: '',
        });

        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                const { latitude: lat, longitude: lng } = pos.coords;
                L.marker([lat, lng], { icon: youIcon })
                    .bindPopup('Your location')
                    .addTo(map);
            });
        }

        function toggleSave(id) {
            fetch(`/competitions/${id}/save`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(r => r.json()).then(data => {
                const btn = document.getElementById(`save-btn-${id}`);
                if (btn) btn.textContent = data.saved ? 'Unsave' : 'Save';
            });
        }
    </script>
</body>
</html>
