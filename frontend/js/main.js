document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formProduccion');
  const tabla = document.querySelector('#tablaProduccion tbody');
  const ctx = document.getElementById('graficoCaja').getContext('2d');

  let graficoCaja;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const data = new FormData(form);
    await fetch('../api/produccion.php', {
      method: 'POST',
      body: data
    });
    form.reset();
    cargarProduccion();
  });

  

async function cargarProduccion() {
    try {
      const res = await fetch('../api/produccion.php');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const datos = await res.json();
      console.log('Datos producción crudos:', datos);

      // 1) Deduplicar filas para la tabla: agrupar por año|mes|producto
      const agrupado = datos.reduce((acc, { anio, mes, producto, unidades }) => {
        const key = `${anio}|${mes}|${producto}`;
        if (!acc[key]) acc[key] = { anio, mes, producto, unidades: 0 };
        acc[key].unidades += Number(unidades);
        return acc;
      }, {});

      // Transformar a array y ordenar por año y mes
      const mesesOrden = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      const filasUnicas = Object.values(agrupado).sort((a, b) => {
        if (a.anio !== b.anio) return a.anio - b.anio;
        return mesesOrden.indexOf(a.mes) - mesesOrden.indexOf(b.mes);
      });

      // 2) Renderizar tabla
      tabla.innerHTML = '';
      filasUnicas.forEach(({ anio, mes, producto, unidades }) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${anio}</td>
          <td>${mes}</td>
          <td>${producto}</td>
          <td>${unidades}</td>
        `;
        tabla.appendChild(tr);
      });

      // 3) Generar datos para el gráfico anual
      const porAnio = filasUnicas.reduce((acc, { anio, unidades }) => {
        acc[anio] = (acc[anio] || 0) + Number(unidades);
        return acc;
      }, {});

      // 4) Renderizar gráfico
      if (graficoCaja) graficoCaja.destroy();
      graficoCaja = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: Object.keys(porAnio),
          datasets: [{
            label: 'Total unidades por año',
            data: Object.values(porAnio)
          }]
        },
        options: { responsive: true }
      });

    } catch (e) {
      console.error('Error cargando producción:', e);
      tabla.innerHTML = '<tr><td colspan="4">Error al cargar datos</td></tr>';
    }
  }

  cargarProduccion();

  const formCostosFijos = document.getElementById('formCostosFijos');
const tablaCostosFijos = document.querySelector('#tablaCostosFijos tbody');

formCostosFijos.addEventListener('submit', async e => {
  e.preventDefault();
  const data = new FormData(formCostosFijos);
  await fetch('../api/costos.php', {
    method: 'POST',
    body: data
  });
  formCostosFijos.reset();
  cargarCostosFijos();
});

async function cargarCostosFijos() {
  const res = await fetch('../api/costos.php');
  const datos = await res.json();

  tablaCostosFijos.innerHTML = '';
  datos.forEach(c => {
    tablaCostosFijos.innerHTML += `<tr><td>${c.descripcion}</td><td>$${parseFloat(c.valor).toFixed(2)}</td></tr>`;
  });
}

cargarCostosFijos();

const formCostosVariables = document.getElementById('formCostosVariables');
const tablaCostosVariables = document.querySelector('#tablaCostosVariables tbody');

formCostosVariables.addEventListener('submit', async e => {
  e.preventDefault();
  const data = new FormData(formCostosVariables);
  await fetch('../api/costos_variable.php', {
    method: 'POST',
    body: data
  });
  formCostosVariables.reset();
  cargarCostosVariables();
});

async function cargarCostosVariables() {
  const res = await fetch('../api/costos_variable.php');
  const datos = await res.json();

  tablaCostosVariables.innerHTML = '';
  datos.forEach(c => {
    tablaCostosVariables.innerHTML += `<tr><td>${c.descripcion}</td><td>$${parseFloat(c.valor).toFixed(2)}</td></tr>`;
  });
}

cargarCostosVariables();

const formManoObra = document.getElementById('formManoObra');
const tablaManoObra = document.querySelector('#tablaManoObra tbody');

formManoObra.addEventListener('submit', async e => {
  e.preventDefault();
  const data = new FormData(formManoObra);
  await fetch('../api/mano_obra.php', {
    method: 'POST',
    body: data
  });
  formManoObra.reset();
  cargarManoObra();
});

async function cargarManoObra() {
  const res = await fetch('../api/mano_obra.php');
  const datos = await res.json();

  tablaManoObra.innerHTML = '';
  datos.forEach(d => {
    tablaManoObra.innerHTML += `
      <tr>
        <td>${d.anio}</td>
        <td>${d.cargo}</td>
        <td>$${parseFloat(d.salario).toFixed(2)}</td>
        <td>$${parseFloat(d.decimotercero).toFixed(2)}</td>
        <td>$${parseFloat(d.decimocuarto).toFixed(2)}</td>
      </tr>`;
  });
}

cargarManoObra();

const formInversiones = document.getElementById('formInversiones');
const tablaInversiones = document.querySelector('#tablaInversiones tbody');

formInversiones.addEventListener('submit', async e => {
  e.preventDefault();
  const data = new FormData(formInversiones);
  await fetch('../api/inversiones.php', {
    method: 'POST',
    body: data
  });
  formInversiones.reset();
  cargarInversiones();
});

async function cargarInversiones() {
  const res = await fetch('../api/inversiones.php');
  const datos = await res.json();

  tablaInversiones.innerHTML = '';
  datos.forEach(i => {
    tablaInversiones.innerHTML += `
      <tr>
        <td>${i.anio}</td>
        <td>${i.tipo}</td>
        <td>${i.descripcion}</td>
        <td>$${parseFloat(i.monto).toFixed(2)}</td>
      </tr>`;
  });
}

cargarInversiones();

const formCaja = document.getElementById('formCaja');
const tablaCaja = document.querySelector('#tablaCaja tbody');
const canvasFlujo = document.getElementById('flujocaja'); // id sigue igual en HTML
let graficoFlujo = null; // NUEVO nombre de variable

formCaja.addEventListener('submit', async e => {
  e.preventDefault();
  const data = new FormData(formCaja);
  await fetch('../api/flujo_caja.php', {
    method: 'POST',
    body: data
  });
  formCaja.reset();
  cargarCaja();
});

async function cargarCaja() {
  const res = await fetch('../api/flujo_caja.php');
  const datos = await res.json();

  tablaCaja.innerHTML = '';
  const labels = [];
  const ingresos = [];
  const egresos = [];

  datos.forEach(row => {
    labels.push(row.anio);
    ingresos.push(parseFloat(row.ingreso));
    egresos.push(parseFloat(row.egreso));
    tablaCaja.innerHTML += `
      <tr>
        <td>${row.anio}</td>
        <td>$${parseFloat(row.ingreso).toFixed(2)}</td>
        <td>$${parseFloat(row.egreso).toFixed(2)}</td>
      </tr>`;
  });

  if (graficoFlujo) graficoFlujo.destroy(); // ahora destruimos con el nuevo nombre
  graficoFlujo = new Chart(canvasFlujo, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Ingresos',
          backgroundColor: 'rgba(75, 192, 192, 0.7)',
          data: ingresos
        },
        {
          label: 'Egresos',
          backgroundColor: 'rgba(255, 99, 132, 0.7)',
          data: egresos
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: { display: true, text: 'Flujo de Caja Anual' }
      }
    }
  });
}

cargarCaja();

async function cargarEvaluacion() {
  const res = await fetch('../api/evaluacion.php');
  const datos = await res.json();

  document.getElementById('van').textContent = `$${parseFloat(datos.VAN).toFixed(2)}`;
  document.getElementById('tir').textContent = `${(datos.TIR * 100).toFixed(2)}%`;
  document.getElementById('pri').textContent = `${datos.PRI} años`;
}

cargarEvaluacion();

const formImport = document.getElementById('formImport');
formImport.addEventListener('submit', async e => {
  e.preventDefault();
  const data = new FormData(formImport);
  const res = await fetch('../excel/importar_excel.php', {
    method: 'POST',
    body: data
  });
  const result = await res.json();
  if (result.status === 'ok') {
    alert(result.message);
    // Volvemos a cargar todos los módulos para que aparezcan los datos importados
    cargarProduccion();
    cargarCostosFijos();
    cargarCostosVariables();
    cargarManoObra();
    cargarInversiones();
    cargarCaja();
    cargarEvaluacion();
  } else {
    alert('Error: ' + result.message);
  }
});

// Archivo: frontend/js/main.js (extensión para gráfica mensual)
document.addEventListener('DOMContentLoaded', () => {
  const ctxProd = document.getElementById('graficoProduccion').getContext('2d');
  const selectYear = document.getElementById('selectYear');
  let chartMonthly;

  // Carga años disponibles en select
  async function cargarAnios() {
    try {
      const res = await fetch('../api/produccion.php');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const datos = await res.json();
      console.log('Producción API:', datos);
      const years = [...new Set(datos.map(d => d.anio))];
      // Construir opciones con placeholder
      selectYear.innerHTML = '<option value="" disabled selected>Selecciona año</option>' +
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
  async function cargarMensual(anioSel) {
    const res = await fetch(`../api/produccion.php?anio=${anioSel}`);
    const datos = (await res.json()).filter(d => d.anio === +anioSel);
    // Agrupar por mes
    const meses = datos.map(d => d.mes);
    const unidades = datos.map(d => d.unidades);
    if (chartMonthly) chartMonthly.destroy();
    chartMonthly = new Chart(ctxProd, {
      type: 'bar',
      data: { labels: meses, datasets: [{ label: `Producción ${anioSel}`, data: unidades }] },
      options: { responsive: true, plugins: { title: { display: true, text: `Producción Mensual ${anioSel}` } } }
    });
  }

  selectYear.addEventListener('change', e => cargarMensual(e.target.value));
  cargarAnios();
});

});
