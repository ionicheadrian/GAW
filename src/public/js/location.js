
var map = L.map('mapid').setView([47.1585, 27.6014], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

if (typeof locations !== 'undefined') {
    locations.forEach(function(loc) {
        if (!loc.latitude || !loc.longitude) return;
        var marker = L.marker([loc.latitude, loc.longitude]).addTo(map);
        var popup = '<strong>' + loc.name + '</strong><br>' +
                    (loc.neighborhood ? '<em>' + loc.neighborhood + '</em><br>' : '') +
                    loc.address;
        marker.bindPopup(popup);
    });
}