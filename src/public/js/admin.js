// listarea categoriilor in admin panel
document.addEventListener('DOMContentLoaded',async ()=>{
  const panel = document.getElementById('adminPanel');
  if (!panel) return;
  const cats = await fetch('/src/api/categories.php').then(r=>r.json());
  panel.innerHTML = '<h3>Categorii</h3>'+
    '<ul>'+cats.map(c=>`<li>${c.type}</li>`).join('')+'</ul>';
});
