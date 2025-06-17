// simulam reciclarea
document.addEventListener('DOMContentLoaded',()=>{
  const ctx = document.getElementById('simChart');
  if (!ctx) return;
  fetch('/src/api/simulation.php')
    .then(r=>r.json())
    .then(data=>{
      new Chart(ctx,{ type:'line',
        data:{
          labels: data.map(d=>d.date),
          datasets:[
            { label:'Menajer', data:data.map(d=>d.menajer) },
            { label:'Hartie',  data:data.map(d=>d.hartie) },
            { label:'Plastic', data:data.map(d=>d.plastic) },
            { label:'Sticla',  data:data.map(d=>d.sticla) }
          ]
        }
      });
    });
});
