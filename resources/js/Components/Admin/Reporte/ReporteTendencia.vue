<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { dinero } from './format';

// Evolución en el tiempo: área + línea por serie, cuadrícula recesiva, cruz que sigue al cursor con un solo cuadro
// que lista todas las series, y vista de tabla (los datos nunca dependen del cursor ni del color).
const props = defineProps({
  puntos: { type: Array, required: true }, // [{ clave: 'YYYY-MM-DD' | 'YYYY-MM', ...medidas }]
  granularidad: { type: String, default: 'dia' }, // 'dia' | 'mes'
  series: { type: Array, required: true }, // [{ key, label, color: 'var(--viz-2)', area }]
  extra: { type: Function, default: null }, // (punto) => texto secundario del cuadro
  nombre: { type: String, default: 'Tendencia' },
});

const contenedor = ref(null);
const ancho = ref(640);
const alto = computed(() => (ancho.value < 520 ? 220 : 280));
let observador = null;

onMounted(() => {
  ancho.value = contenedor.value?.clientWidth || 640;
  observador = new ResizeObserver(([e]) => { ancho.value = Math.round(e.contentRect.width) || ancho.value; });
  observador.observe(contenedor.value);
});
onBeforeUnmount(() => observador?.disconnect());

const M = { izq: 52, der: 14, arr: 14, aba: 30 };
const areaW = computed(() => Math.max(10, ancho.value - M.izq - M.der));
const areaH = computed(() => alto.value - M.arr - M.aba);
const n = computed(() => props.puntos.length);

// Tope del eje con números redondos (1, 2, 2.5, 5 × 10^k) y 4 divisiones.
const escala = computed(() => {
  const maximo = Math.max(0, ...props.puntos.flatMap((p) => props.series.map((s) => Number(p[s.key]) || 0)));
  if (maximo <= 0) return { tope: 100, paso: 25 };
  const crudo = maximo / 4;
  const magnitud = 10 ** Math.floor(Math.log10(crudo));
  const paso = [1, 2, 2.5, 5, 10].map((m) => m * magnitud).find((v) => v >= crudo);
  return { tope: paso * 4, paso };
});
const ticksY = computed(() => [0, 1, 2, 3, 4].map((i) => i * escala.value.paso));

const x = (i) => M.izq + (n.value === 1 ? areaW.value / 2 : (i * areaW.value) / (n.value - 1));
const y = (v) => M.arr + areaH.value - (Math.max(0, v) / escala.value.tope) * areaH.value;

const compacto = (v) => (v >= 1000 ? `${+(v / 1000).toFixed(1)}K` : String(v));

const fecha = (clave, largo = false) => {
  const [a, m, d] = clave.split('-').map(Number);
  const f = new Date(a, m - 1, d || 1);
  if (props.granularidad === 'mes') return f.toLocaleDateString('es-PE', { month: largo ? 'long' : 'short', year: largo ? 'numeric' : '2-digit' });
  return largo
    ? f.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' })
    : f.toLocaleDateString('es-PE', { day: 'numeric', month: 'short' });
};

const ticksX = computed(() => {
  const max = Math.max(2, Math.floor(areaW.value / 84));
  if (n.value <= max) return props.puntos.map((_, i) => i);
  const paso = (n.value - 1) / (max - 1);
  return [...new Set(Array.from({ length: max }, (_, k) => Math.round(k * paso)))];
});

const trazo = (key) => props.puntos.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(Number(p[key]) || 0).toFixed(1)}`).join('');
const area = (key) => `${trazo(key)}L${x(n.value - 1).toFixed(1)},${y(0)}L${x(0).toFixed(1)},${y(0)}Z`;

// Con un solo día no hay línea que trazar: se dibuja una columna (máx. 24 px, borde superior redondeado).
const columna = computed(() => n.value === 1);

// --- Cursor ----------------------------------------------------------------------------------------
const activo = ref(null);
const alMover = (e) => {
  const caja = e.currentTarget.getBoundingClientRect();
  const px = e.clientX - caja.left - M.izq;
  activo.value = n.value === 1 ? 0 : Math.min(n.value - 1, Math.max(0, Math.round((px / areaW.value) * (n.value - 1))));
};
const alTeclear = (e) => {
  if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
  e.preventDefault();
  const actual = activo.value ?? 0;
  activo.value = e.key === 'Home' ? 0 : e.key === 'End' ? n.value - 1 : Math.min(n.value - 1, Math.max(0, actual + (e.key === 'ArrowRight' ? 1 : -1)));
};

const punto = computed(() => (activo.value == null ? null : props.puntos[activo.value]));
const izquierda = computed(() => (activo.value == null ? 0 : Math.min(Math.max(x(activo.value) - 90, 4), ancho.value - 184)));

const verTabla = ref(false);
const resumen = computed(() => `${props.nombre}: ${n.value} ${props.granularidad === 'mes' ? 'meses' : 'días'}. ${props.series.map((s) => `${s.label} total ${dinero(props.puntos.reduce((a, p) => a + (Number(p[s.key]) || 0), 0))}`).join('; ')}.`);
</script>

<template>
  <div class="viz">
    <div v-if="series.length > 1" class="flex flex-wrap items-center gap-x-5 gap-y-1 mb-3 text-sm text-gray-600" aria-label="Leyenda">
      <span v-for="s in series" :key="s.key" class="inline-flex items-center gap-2">
        <span class="w-4 h-0.5 rounded-full" :style="{ background: s.color }" aria-hidden="true"></span>{{ s.label }}
      </span>
    </div>

    <div ref="contenedor" class="relative select-none" @pointerleave="activo = null">
      <svg
        :width="ancho"
        :height="alto"
        :viewBox="`0 0 ${ancho} ${alto}`"
        class="block outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] rounded-lg"
        role="img"
        :aria-label="resumen"
        tabindex="0"
        @pointermove="alMover"
        @keydown="alTeclear"
        @focus="activo = activo ?? n - 1"
        @blur="activo = null"
      >
        <!-- Rejilla y eje Y -->
        <g>
          <line v-for="t in ticksY" :key="t" :x1="M.izq" :x2="ancho - M.der" :y1="y(t)" :y2="y(t)" :stroke="t === 0 ? 'var(--viz-eje)' : 'var(--viz-rejilla)'" stroke-width="1" />
          <text v-for="t in ticksY" :key="'l' + t" :x="M.izq - 10" :y="y(t) + 4" text-anchor="end" font-size="11" fill="var(--viz-tenue)" class="tabular-nums">{{ compacto(t) }}</text>
        </g>

        <!-- Eje X -->
        <text v-for="i in ticksX" :key="'x' + i" :x="x(i)" :y="alto - 8" :text-anchor="i === 0 && n > 1 ? 'start' : i === n - 1 && n > 1 ? 'end' : 'middle'" font-size="11" fill="var(--viz-tenue)">{{ fecha(puntos[i].clave) }}</text>

        <template v-if="columna">
          <path
            v-for="(s, k) in series"
            :key="s.key"
            :d="`M${x(0) - 12 + k * 0},${y(0)} V${y(Number(puntos[0][s.key]) || 0) + 4} q0,-4 4,-4 h16 q4,0 4,4 V${y(0)} Z`"
            :fill="s.color"
            :transform="`translate(${(k - (series.length - 1) / 2) * 30 - 4} 0)`"
          />
        </template>

        <template v-else-if="n > 1">
          <g v-for="s in series" :key="s.key">
            <path v-if="s.area" :d="area(s.key)" :fill="s.color" fill-opacity="0.1" />
            <path :d="trazo(s.key)" fill="none" :stroke="s.color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </g>
          <!-- Punto final de cada serie, con anillo del color de la superficie -->
          <circle
            v-for="s in series"
            :key="'f' + s.key"
            :cx="x(n - 1)"
            :cy="y(Number(puntos[n - 1][s.key]) || 0)"
            r="4"
            :fill="s.color"
            stroke="rgb(var(--surface))"
            stroke-width="2"
          />
        </template>

        <!-- Cruz y puntos del cursor -->
        <g v-if="punto && n > 0" pointer-events="none">
          <line :x1="x(activo)" :x2="x(activo)" :y1="M.arr" :y2="y(0)" stroke="var(--viz-eje)" stroke-width="1" />
          <circle v-for="s in series" :key="'c' + s.key" :cx="x(activo)" :cy="y(Number(punto[s.key]) || 0)" r="4" :fill="s.color" stroke="rgb(var(--surface))" stroke-width="2" />
        </g>
      </svg>

      <!-- Un solo cuadro con todas las series: el valor manda, el nombre acompaña -->
      <div
        v-if="punto"
        class="pointer-events-none absolute top-1 z-10 w-[180px] rounded-xl border border-gray-200 bg-surface px-3 py-2.5 shadow-lg"
        :style="{ left: izquierda + 'px' }"
        role="status"
      >
        <p class="text-xs font-semibold text-gray-500 first-letter:uppercase">{{ fecha(punto.clave, true) }}</p>
        <p v-for="s in series" :key="s.key" class="mt-1.5 flex items-center gap-2">
          <span class="w-3 h-0.5 rounded-full flex-shrink-0" :style="{ background: s.color }" aria-hidden="true"></span>
          <b class="text-sm text-gray-900 tabular-nums">{{ dinero(punto[s.key]) }}</b>
          <span class="text-xs text-gray-500 truncate">{{ s.label }}</span>
        </p>
        <p v-if="extra" class="mt-1.5 pt-1.5 border-t border-gray-100 text-xs text-gray-500">{{ extra(punto) }}</p>
      </div>
    </div>

    <button type="button" class="mt-3 text-sm font-semibold text-[#e67e2d] hover:underline" :aria-expanded="verTabla" @click="verTabla = !verTabla">
      {{ verTabla ? 'Ocultar tabla' : 'Ver como tabla' }}
    </button>

    <div v-if="verTabla" class="mt-3 max-h-64 overflow-auto rounded-xl border border-gray-100">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 sticky top-0">
          <tr>
            <th scope="col" class="text-left py-2 px-3 font-semibold">{{ granularidad === 'mes' ? 'Mes' : 'Día' }}</th>
            <th v-for="s in series" :key="s.key" scope="col" class="text-right py-2 px-3 font-semibold">{{ s.label }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in puntos" :key="p.clave">
            <td class="py-2 px-3 text-gray-700 first-letter:uppercase">{{ fecha(p.clave, true) }}</td>
            <td v-for="s in series" :key="s.key" class="py-2 px-3 text-right tabular-nums text-gray-900">{{ dinero(p[s.key]) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
