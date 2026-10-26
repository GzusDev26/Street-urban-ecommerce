<template>
  <!-- theme-fixed: el papel térmico se ve igual en modo oscuro -->
  <div class="boleta-sombra theme-fixed">
    <article class="boleta" :style="{ '--ancho': ancho + 'mm', '--util': (ancho === 58 ? 48 : 72) + 'mm', ...(zoom ? { zoom } : {}) }" aria-label="Boleta de venta">
        <header class="text-center">
          <img class="logo" src="/image/logo/street-urban-icono-light.png" width="512" height="512" alt="" aria-hidden="true" />
          <h1 class="marca">{{ business.name }}</h1>
          <p class="adorno" aria-hidden="true"><i></i><b></b><i></i></p>
          <div class="datos-negocio">
            <p v-if="business.ruc">RUC {{ business.ruc }}</p>
            <p v-if="business.address">{{ business.address }}</p>
            <p v-if="business.phone">Tel. {{ business.phone }}</p>
          </div>
        </header>

        <section class="numero">
          <p class="etq">Boleta de venta</p>
          <p class="num">{{ venta.numero }}</p>
          <p v-if="venta.cancelada" class="anulada">VENTA CANCELADA</p>
        </section>

        <dl class="meta">
          <div>
            <dt class="etq">Fecha</dt>
            <dd>{{ venta.fecha }}</dd>
          </div>
          <div v-if="venta.registradoPor" class="text-right">
            <dt class="etq">Atendió</dt>
            <dd>{{ venta.registradoPor }}</dd>
          </div>
        </dl>

        <section class="bloque">
          <p class="etq">Cliente</p>
          <p class="font-bold uppercase break-words leading-tight">{{ venta.cliente || 'Cliente varios' }}</p>
          <p v-if="venta.clienteDocumento" class="mono">{{ venta.clienteDocumento }}</p>
        </section>

        <section class="bloque">
          <div class="fila etq"><span>Detalle</span><span>Importe</span></div>
          <ul class="items">
            <li v-for="(item, idx) in venta.items" :key="idx">
              <span class="cant mono">{{ item.cantidad }}×</span>
              <span class="desc">
                <b class="uppercase">{{ item.producto }}</b>
                <span class="uppercase">{{ item.marca }} · T.{{ item.talla }} · {{ item.color }}</span>
                <span class="mono precio-u">S/ {{ item.precioUnitario.toFixed(2) }} c/u</span>
                <span v-if="item.cantidadDevuelta > 0" class="font-bold">Devuelto: {{ item.cantidadDevuelta }}</span>
              </span>
              <span class="importe mono">{{ item.subtotal.toFixed(2) }}</span>
            </li>
          </ul>
        </section>

        <section class="resumen">
          <div class="fila"><span>Prendas</span><span class="mono">{{ venta.totalPrendas }}</span></div>
          <div v-if="venta.prendasDevueltas > 0" class="fila"><span>Devoluciones</span><span class="mono">− {{ (venta.total - venta.totalNeto).toFixed(2) }}</span></div>
          <div class="total">
            <span>{{ venta.prendasDevueltas > 0 ? 'Total neto' : 'Total' }}</span>
            <span class="mono">S/ {{ totalFinal.toFixed(2) }}</span>
          </div>
          <p class="letras">{{ enLetras }}</p>
        </section>

        <p v-if="venta.notas" class="nota"><b>Nota</b> {{ venta.notas }}</p>

        <footer class="text-center">
          <p class="adorno" aria-hidden="true"><i></i><b></b><i></i></p>
          <p class="gracias">Gracias por tu compra</p>
          <p>Conserva tu boleta para cualquier cambio.</p>
          <p class="mt-1.5 mono">Emitida el {{ venta.fecha }}</p>
          <p class="corte" aria-hidden="true"><i class="fa-solid fa-scissors"></i></p>
        </footer>
    </article>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  venta: { type: Object, required: true },
  ancho: { type: Number, default: 58 },
  // Para la vista previa de Datos del negocio: datos aún sin guardar y tamaño reducido.
  business: { type: Object, default: null },
  zoom: { type: Number, default: null },
});

const page = usePage();
const business = computed(() => props.business ?? page.props.business);

const totalFinal = computed(() => (props.venta.prendasDevueltas > 0 ? props.venta.totalNeto : props.venta.total));

// ---- Importe en letras ("SON: SEISCIENTOS CON 00/100 SOLES")
const UNIDADES = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
const DECENAS = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
const CENTENAS = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

const menorDeMil = (n) => {
  if (n === 100) return 'CIEN';
  const c = Math.floor(n / 100);
  const r = n % 100;
  const resto = r < 30 ? UNIDADES[r] : DECENAS[Math.floor(r / 10)] + (r % 10 ? ` Y ${UNIDADES[r % 10]}` : '');
  return [CENTENAS[c], resto].filter(Boolean).join(' ');
};

const entero = (n) => {
  if (n === 0) return 'CERO';
  const miles = Math.floor(n / 1000);
  const resto = n % 1000;
  const parteMiles = miles === 0 ? '' : miles === 1 ? 'MIL' : `${menorDeMil(miles).replace(/UNO$/, 'UN')} MIL`;
  return [parteMiles, resto ? menorDeMil(resto) : ''].filter(Boolean).join(' ');
};

const enLetras = computed(() => {
  const total = Math.round(totalFinal.value * 100);
  const soles = Math.floor(total / 100);
  const centimos = String(total % 100).padStart(2, '0');

  // Por encima del millón no se escribe en letras (no es un caso de una tienda de ropa).
  if (soles >= 1000000) return `SON: ${soles} CON ${centimos}/100 SOLES`;
  return `SON: ${entero(soles)} CON ${centimos}/100 SOLES`;
});
</script>

<style>
/* Boleta térmica de un solo tono. Pantalla: ampliada y con bordes de papel rasgado; impresión: tamaño real y plana. */
.boleta-sombra {
  filter: drop-shadow(0 14px 22px rgba(15, 23, 42, 0.22));
  animation: boleta-entra 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes boleta-entra {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
  .boleta-sombra { animation: none; }
}

.boleta {
  --ancho: 58mm;
  width: var(--ancho);
  padding: 7mm 3mm 7mm;
  background: #fff;
  color: #000;
  font-family: 'Helvetica Neue', Arial, sans-serif;
  font-size: 9.5px;
  line-height: 1.3;
  zoom: 1.7;
  /* bordes dentados arriba y abajo */
  --z: 4px;
  -webkit-mask:
    conic-gradient(from -45deg at bottom, #0000, #000 1deg 89deg, #0000 90deg) bottom / calc(2 * var(--z)) 51% repeat-x,
    conic-gradient(from 135deg at top, #0000, #000 1deg 89deg, #0000 90deg) top / calc(2 * var(--z)) 51% repeat-x;
  mask:
    conic-gradient(from -45deg at bottom, #0000, #000 1deg 89deg, #0000 90deg) bottom / calc(2 * var(--z)) 51% repeat-x,
    conic-gradient(from 135deg at top, #0000, #000 1deg 89deg, #0000 90deg) top / calc(2 * var(--z)) 51% repeat-x;
}

.boleta .mono { font-family: ui-monospace, 'SF Mono', Menlo, 'Courier New', monospace; font-variant-numeric: tabular-nums; }
.boleta .fila { display: flex; justify-content: space-between; gap: 6px; }

/* etiqueta pequeña con tracking amplio: da el aire "de marca" sin usar color */
.boleta .etq { font-size: 7.5px; font-weight: 800; letter-spacing: 0.22em; text-transform: uppercase; }

/* Logo en un solo tono, como sale en la impresora térmica. */
.boleta .logo {
  display: block; width: 48px; height: 48px; margin: 0 auto 5px;
  object-fit: contain; filter: grayscale(1) contrast(1.25);
}
.boleta .marca { font-size: 14px; font-weight: 900; letter-spacing: 0.24em; text-transform: uppercase; line-height: 1.15; margin-right: -0.24em; }

/* filete con rombo central */
.boleta .adorno { display: flex; align-items: center; gap: 5px; margin: 7px 8px; }
.boleta .adorno i { flex: 1; height: 0; border-top: 1px solid #000; }
.boleta .adorno b { width: 5px; height: 5px; background: #000; transform: rotate(45deg); }
.boleta .datos-negocio { font-size: 8.5px; line-height: 1.35; }

.boleta .numero { margin: 10px 0 8px; padding: 6px 0 5px; text-align: center; border-top: 2px solid #000; border-bottom: 1px solid #000; }
.boleta .num { font-family: ui-monospace, 'SF Mono', Menlo, 'Courier New', monospace; font-size: 19px; font-weight: 800; letter-spacing: 0.04em; line-height: 1.2; }
.boleta .anulada { margin-top: 4px; border: 2px solid #000; padding: 2px 0; font-weight: 900; letter-spacing: 0.2em; font-size: 9px; }

.boleta .meta { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 7px; font-weight: 700; }
.boleta .meta dd { margin-top: 1px; }

.boleta .bloque { padding: 6px 0; border-top: 1px dotted #000; }
.boleta .bloque > .etq { margin-bottom: 3px; }

.boleta .items { margin-top: 5px; }
.boleta .items li { display: grid; grid-template-columns: 17px 1fr auto; gap: 4px; padding: 4px 0; }
.boleta .items li + li { border-top: 1px dotted #000; }
.boleta .cant { font-weight: 800; }
.boleta .desc { display: flex; flex-direction: column; min-width: 0; line-height: 1.25; }
.boleta .desc b { font-weight: 800; }
.boleta .precio-u { font-size: 8.5px; }
.boleta .importe { font-weight: 800; text-align: right; }

.boleta .resumen { padding-top: 6px; border-top: 2px solid #000; }
.boleta .total {
  display: flex; justify-content: space-between; align-items: baseline; gap: 6px;
  margin-top: 5px; padding: 5px 5px; background: #000; color: #fff;
  font-size: 13px; font-weight: 900; letter-spacing: 0.06em; text-transform: uppercase;
}
.boleta .total .mono { letter-spacing: 0; white-space: nowrap; }
.boleta .letras { margin-top: 5px; font-size: 8.5px; font-weight: 700; font-style: italic; text-align: center; }

.boleta .nota { margin-top: 7px; padding: 5px 6px; border: 1px solid #000; font-size: 8.5px; }
.boleta .gracias { font-size: 9.5px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; margin-right: -0.12em; white-space: nowrap; }
.boleta .corte { position: relative; height: 0; margin-top: 14px; border-top: 1px dashed #000; }
.boleta .corte i { position: absolute; left: 50%; top: -6px; transform: translateX(-50%); background: #fff; padding: 0 5px; font-size: 9px; line-height: 1; }

@media print {
  /* El tamaño del rollo lo define el driver de la boletera; aquí solo se quitan márgenes y se usa
     el ancho imprimible típico (48 mm en rollos de 58, 72 mm en rollos de 80). */
  @page { margin: 0; }
  .boleta-sombra { filter: none; animation: none; }
  .boleta {
    zoom: 1;
    width: var(--util);
    margin: 0 auto;
    padding: 2mm 0 12mm;
    -webkit-mask: none;
    mask: none;
  }
  body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
