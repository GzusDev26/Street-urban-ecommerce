// Formatos compartidos por las pantallas de reportes.
export const dinero = (n) => `S/ ${Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
export const entero = (n) => Number(n || 0).toLocaleString('es-PE');
export const porcentaje = (parte, total, decimales = 0) => (total > 0 ? (parte / total) * 100 : 0).toFixed(decimales) + '%';

// Fecha de hoy según el navegador (nunca toISOString: pasa a UTC y de 7 pm a medianoche daría el día siguiente).
const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

export const rangosRapidos = () => {
  const hoy = new Date();
  const dia = (y, m, d) => new Date(y, m, d);
  const y = hoy.getFullYear();
  const m = hoy.getMonth();

  return [
    { id: 'hoy', label: 'Hoy', desde: iso(hoy), hasta: iso(hoy) },
    { id: '7d', label: 'Últimos 7 días', desde: iso(dia(y, m, hoy.getDate() - 6)), hasta: iso(hoy) },
    { id: 'mes', label: 'Este mes', desde: iso(dia(y, m, 1)), hasta: iso(hoy) },
    { id: 'mes-pasado', label: 'Mes pasado', desde: iso(dia(y, m - 1, 1)), hasta: iso(dia(y, m, 0)) },
    { id: 'anio', label: 'Este año', desde: iso(dia(y, 0, 1)), hasta: iso(hoy) },
    { id: 'todo', label: 'Todo', desde: '', hasta: '' },
  ];
};

// "26 oct" / "26 oct 2026" a partir de 'YYYY-MM-DD'.
export const fechaCorta = (iso, conAnio = false) => {
  const [a, m, d] = iso.split('-').map(Number);
  return new Date(a, m - 1, d).toLocaleDateString('es-PE', { day: 'numeric', month: 'short', ...(conAnio ? { year: 'numeric' } : {}) });
};

// Texto del período del reporte según los filtros.
export const textoPeriodo = ({ desde, hasta }) => {
  if (desde && hasta) return desde === hasta ? fechaCorta(desde, true) : `${fechaCorta(desde)} – ${fechaCorta(hasta, true)}`;
  if (desde) return `Desde el ${fechaCorta(desde, true)}`;
  if (hasta) return `Hasta el ${fechaCorta(hasta, true)}`;
  return 'Todo el historial';
};

// "25 oct." si es un solo día, "13 oct. – 19 oct." si es un rango (para mostrar el período anterior).
export const rangoCorto = (desde, hasta) => (desde === hasta ? fechaCorta(desde) : `${fechaCorta(desde)} – ${fechaCorta(hasta)}`);

// Variación porcentual contra el período anterior (null si no hay base para comparar).
export const variacion = (actual, anterior) => (anterior > 0 ? ((actual - anterior) / anterior) * 100 : null);
