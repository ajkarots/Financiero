// Archivo: frontend/js/dashboard_monthly_chart.js
// Gráfica mensual de producción por año con meses únicos y valores agregados

document.addEventListener('DOMContentLoaded', () => {
  const selectYear = document.getElementById('selectYear');
  const ctx = document.getElementById('graficoProduccion').getContext('2d');
  let chart;

  // Carga lista de años disponibles
  async function cargarAnios() {
    try {
      const res = await fetch('../api/produccion.php');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const datos = await res.json();
      console.log('Anios API:', datos);
      const rawYears = datos.map(item => typeof item === 'object' ? item.anio : item);
      const years = [...new Set(rawYears)];
      selectYear.innerHTML =
        '<option value="" disabled selected>Selecciona año</option>' +
        years.map(y => `<option value="${y}">${y}</option>`).join('');
      if (years.length) {
        selectYear.value = years[0];
        cargarMensual(years[0]);
      }
    } catch (e) {
      console.error('Error cargando años:', e);
      selectYear.innerHTML = '<option value="" disabled>Error al cargar años</option>';
    }
  }

  // Dibuja gráfica mensual para un año dado
  async function cargarMensual(anio) {
    try {
      const res = await fetch(`../api/produccion.php?anio=${anio}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const datos = await res.json();
      console.log('Producción mensual:', datos);

      // Agrupar por mes: sumar unidades si hay repetidos
      const agrupado = datos.reduce((acc, { mes, unidades }) => {
        const key = mes;
        acc[key] = (acc[key] || 0) + Number(unidades);
        return acc;
      }, {});

      // Mantener orden de meses fijo
      const mesesOrden = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      const meses = [];
      const unidades = [];
      mesesOrden.forEach(m => {
        if (agrupado[m] !== undefined) {
          meses.push(m);
          unidades.push(agrupado[m]);
        }
      });

      if (chart) chart.destroy();
      chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: meses,
          datasets: [{
            label: `Producción ${anio}`,
            data: unidades
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'top' },
            title: { display: true, text: `Producción Mensual ${anio}` }
          }
        }
      });
    } catch (e) {
      console.error('Error cargando producción mensual:', e);
    }
  }

  selectYear.addEventListener('change', e => cargarMensual(e.target.value));
  cargarAnios();
});
