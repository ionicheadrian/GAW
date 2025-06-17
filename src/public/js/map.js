// leaflet + raport nou
document.addEventListener('DOMContentLoaded',()=>{
  const mapDiv = document.getElementById('map');
  if (!mapDiv) return;
  const map = L.map('map').setView([47.1585,27.6014],13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap contributors'
  }).addTo(map);
  map.on('click', e=>{
    const lat=e.latlng.lat, lng=e.latlng.lng;
    const payload = { title:'Raport nou', description:'...', waste_category_id:1, latitude:lat, longitude:lng, location_name:'Punct' };
    fetch('/src/api/reports.php?action=create',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    }).then(res=>res.json()).then(console.log);
  });
});
// POST /src/api/reports.php?action=create