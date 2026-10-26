<template>
  <Head title="Registrar Venta" />
  <AdminLayout>
    <template #breadcrumb>Ventas / Registrar</template>
    <template #header>Registrar Nueva Venta</template>

    <div class="space-y-5 sm:space-y-6 pb-28 xl:pb-0">
      <!-- Tabs (en el celular se deslizan hacia el lado si no caben) -->
      <div class="flex gap-2 border-b border-gray-200 overflow-x-auto whitespace-nowrap -mx-4 px-4 sm:mx-0 sm:px-0">
        <Link
          :href="route('admin.sales.create')"
          class="px-4 py-2.5 text-sm font-semibold text-[#ff8c42] border-b-2 border-[#ff8c42]"
        >
          Registrar Venta
        </Link>
        <Link
          :href="route('admin.sales.index')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Historial de Ventas
        </Link>
        <Link
          :href="route('admin.sales.reportes')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Reportes de Ventas
        </Link>
      </div>

      <div v-if="page.props.flash?.success && !page.props.flash?.ventaId" class="bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-medium flex items-center justify-between gap-3 flex-wrap">
        <span class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-full bg-green-500 text-white flex items-center justify-center"><i class="fa-solid fa-check text-xs"></i></span>
          {{ page.props.flash.success }}
        </span>
        <div v-if="page.props.flash?.ventaId" class="flex items-center gap-2">
          <Link
            :href="`${route('admin.sales.index')}?venta=${page.props.flash.ventaId}`"
            class="text-xs font-semibold text-green-700 hover:text-green-800 bg-surface border border-green-200 px-3.5 py-2 rounded-lg flex items-center gap-1.5"
          >
            <i class="fa-solid fa-receipt text-[11px]"></i> Ver en Historial
          </Link>
          <a
            :href="route('admin.sales.boleta', page.props.flash.ventaId)"
            target="_blank"
            class="text-xs font-semibold text-white bg-green-600 hover:bg-green-700 px-3.5 py-2 rounded-lg flex items-center gap-1.5"
          >
            <i class="fa-solid fa-print text-[11px]"></i> Imprimir Boleta
          </a>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_25rem] gap-6 items-start">
        <!-- ======================= IZQUIERDA: catálogo ======================= -->
        <section class="bg-surface rounded-3xl border border-gray-100 shadow-sm p-4 sm:p-6">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h3 class="text-lg font-bold text-gray-900">Buscar prenda</h3>
            <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 bg-gray-50 rounded-full px-3 py-1.5">
              <i class="fa-solid fa-boxes-stacked text-gray-400"></i> Solo se venden prendas con stock registrado
            </span>
          </div>

          <!-- Buscador grande (también lee códigos de barras: pistola o cámara) -->
          <div class="flex gap-2 mb-2">
            <div class="relative flex-1 min-w-0">
              <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
              <input
                v-model="searchQuery"
                type="text"
                autocomplete="off"
                aria-label="Buscar por modelo, marca o código de barras"
                placeholder="Modelo, marca o código…"
                class="w-full h-12 pl-11 pr-11 rounded-2xl border border-gray-200 bg-surface text-base text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
                @keydown.enter.prevent="alEnter"
              >
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                aria-label="Limpiar búsqueda"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center"
              >
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
            <button
              type="button"
              class="h-12 w-12 sm:w-auto sm:px-4 rounded-2xl border border-gray-200 bg-surface text-gray-700 hover:border-[#ff8c42] hover:text-[#e67e2d] font-semibold text-sm flex items-center justify-center gap-2 transition-colors flex-shrink-0 focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100"
              @click="camaraRespuesta = null; camaraAbierta = true"
            >
              <i class="fa-solid fa-barcode" aria-hidden="true"></i>
              <span class="hidden sm:inline">Escanear</span><span class="sm:hidden sr-only">Escanear código</span>
            </button>
          </div>
          <p
            v-if="mensajeEscaneo"
            :class="['mb-3 text-sm font-medium flex items-center gap-2', mensajeEscaneo.error ? 'text-red-500' : 'text-green-600']"
            role="status"
          >
            <i :class="mensajeEscaneo.error ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check'" aria-hidden="true"></i> {{ mensajeEscaneo.texto }}
          </p>
          <div v-else class="mb-2"></div>

          <!-- Categorías como chips -->
          <div class="flex gap-2 mb-4 sm:mb-5 overflow-x-auto sm:flex-wrap sm:overflow-visible -mx-4 px-4 sm:mx-0 sm:px-0 pb-1" role="group" aria-label="Categorías">
            <button
              v-for="c in opcionesCategoria"
              :key="c || 'todas'"
              type="button"
              @click="filtroCategoria = c"
              :aria-pressed="filtroCategoria === c"
              :class="[
                'px-4 h-11 sm:h-9 flex-shrink-0 rounded-full text-sm font-semibold border transition-all',
                filtroCategoria === c
                  ? 'bg-[#ff8c42] border-[#ff8c42] text-white shadow-sm shadow-orange-200'
                  : 'bg-surface border-gray-200 text-gray-600 hover:border-[#ff8c42] hover:text-[#e67e2d]',
              ]"
            >{{ c || 'Todas' }}</button>
          </div>

          <div v-if="isSearching" class="flex items-center justify-center gap-2 text-sm text-gray-400 py-16">
            <i class="fa-solid fa-spinner fa-spin"></i> Buscando...
          </div>

          <!-- Resultados: una tarjeta por modelo; al abrir uno, su cuadrícula de colores × tallas -->
          <template v-else-if="searchResults.length > 0">
            <!-- Cuadrícula del modelo elegido -->
            <div v-if="modeloAbierto">
              <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                  <button
                    v-if="searchResults.length > 1"
                    type="button"
                    @click="cerrarModelo"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#ff8c42] mb-1.5 transition-colors"
                  ><i class="fa-solid fa-arrow-left text-[10px]"></i> Volver a los resultados</button>
                  <h4 class="text-xl font-bold text-gray-900 leading-tight">{{ modeloAbierto.producto }}</h4>
                  <p class="text-sm text-gray-500">{{ modeloAbierto.marca }} · {{ modeloAbierto.categoria }}</p>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-black text-gray-900 tabular-nums leading-none">{{ textoPrecio(modeloAbierto) }}</p>
                  <p v-if="enTicketModelo(modeloAbierto) > 0" class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-[#ff8c42] rounded-full px-2.5 py-1 mt-2"><i class="fa-solid fa-check text-[10px]"></i> {{ enTicketModelo(modeloAbierto) }} en el ticket</p>
                </div>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-500">
                  <span class="font-semibold text-gray-700 w-full sm:w-auto">Toca una casilla para agregar 1 unidad</span>
                  <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded border-2 border-gray-300 bg-surface"></span> Hay</span>
                  <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded border-2 border-amber-300 bg-amber-50"></span> Quedan pocas</span>
                  <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#ff8c42]"></span> En el ticket</span>
                </div>
                <div v-if="coloresDelModelo.length > 6" class="relative">
                  <i class="fa-solid fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                  <input
                    v-model="filtroColor"
                    type="text"
                    aria-label="Filtrar colores"
                    placeholder="Filtrar color..."
                    class="h-9 w-44 pl-8 pr-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
                  >
                </div>
              </div>

              <div class="overflow-x-auto rounded-2xl border border-gray-100">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                      <th class="text-left font-semibold py-3 px-3 sm:px-4 sticky left-0 z-[1] bg-gray-50">Color</th>
                      <th v-for="t in tallasDelModelo" :key="t" class="text-center font-semibold py-3 px-1.5 min-w-[4rem]">{{ t }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="c in coloresVisibles" :key="c.nombre" data-color>
                      <td class="py-2.5 px-3 sm:px-4 sticky left-0 z-[1] bg-surface">
                        <div class="flex items-center gap-2.5 whitespace-nowrap">
                          <span class="w-8 h-8 rounded-lg bg-gray-50 ring-1 ring-black/5 flex items-center justify-center flex-shrink-0">
                            <ColorSwatch :hex="c.hex" class="w-[18px] h-[18px] rounded-full border border-gray-200" />
                          </span>
                          <span class="font-semibold text-gray-900">{{ c.nombre }}</span>
                        </div>
                      </td>

                      <td v-for="t in tallasDelModelo" :key="t" class="py-2 px-1.5 text-center">
                        <template v-if="c.celdas[t]">
                          <button
                            type="button"
                            @click="agregarRapido(conModelo(modeloAbierto, c.celdas[t]))"
                            :disabled="restante(c.celdas[t]) <= 0"
                            :aria-label="`Agregar ${modeloAbierto.producto} talla ${t} ${c.nombre} al ticket`"
                            :title="`${c.nombre} · talla ${t} · S/ ${c.celdas[t].precioVenta.toFixed(2)}`"
                            :class="[
                              'w-[3.75rem] h-12 mx-auto rounded-xl border-2 flex flex-col items-center justify-center leading-none tabular-nums transition-all focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100',
                              cantidadYaAgregada(c.celdas[t].variantId) > 0
                                ? 'border-[#ff8c42] bg-[#ff8c42] text-white shadow-sm shadow-orange-200 enabled:hover:bg-[#f27d30] enabled:active:scale-95 disabled:cursor-not-allowed'
                                : restante(c.celdas[t]) <= 0
                                  ? 'border-dashed border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed'
                                  : restante(c.celdas[t]) <= 3
                                    ? 'border-amber-300 bg-amber-50 text-amber-700 hover:border-[#ff8c42] hover:bg-orange-50 active:scale-95'
                                    : 'border-gray-200 bg-surface text-gray-800 hover:border-[#ff8c42] hover:bg-orange-50 active:scale-95',
                            ]"
                          >
                            <template v-if="cantidadYaAgregada(c.celdas[t].variantId) > 0">
                              <span class="text-base font-black">×{{ cantidadYaAgregada(c.celdas[t].variantId) }}</span>
                              <span class="text-[10px] font-semibold text-white/80 mt-1">quedan {{ restante(c.celdas[t]) }}</span>
                            </template>
                            <template v-else>
                              <span class="text-base font-bold">{{ restante(c.celdas[t]) }}</span>
                              <span class="text-[10px] font-semibold mt-1 opacity-70">{{ restante(c.celdas[t]) <= 0 ? 'agotado' : 'unid.' }}</span>
                            </template>
                          </button>
                        </template>
                        <span v-else class="text-gray-200" aria-hidden="true">—</span>
                      </td>
                    </tr>
                    <tr v-if="coloresVisibles.length === 0">
                      <td :colspan="tallasDelModelo.length + 1" class="py-6 text-center text-sm text-gray-400">Ningún color coincide con «{{ filtroColor }}».</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Modelos encontrados -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-3">
              <button
                v-for="m in searchResults"
                :key="m.productId"
                type="button"
                @click="abrirModelo(m)"
                :aria-label="`Ver colores y tallas de ${m.producto}`"
                :class="[
                  'group relative text-left rounded-2xl border-2 p-4 transition-all focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100',
                  enTicketModelo(m) > 0 ? 'border-[#ff8c42] bg-orange-50/60 hover:shadow-md' : 'border-gray-100 bg-surface hover:border-[#ff8c42] hover:shadow-md active:scale-[0.99]',
                ]"
              >
                <div class="flex items-center gap-1.5 min-h-[1.5rem]">
                  <ColorSwatch
                    v-for="c in coloresUnicos(m).slice(0, 6)"
                    :key="c.nombre"
                    :hex="c.hex"
                    :title="c.nombre"
                    class="w-5 h-5 rounded-full border border-gray-200"
                  />
                  <span v-if="coloresUnicos(m).length > 6" class="text-xs font-semibold text-gray-400">+{{ coloresUnicos(m).length - 6 }}</span>
                </div>

                <p class="font-semibold text-gray-900 leading-tight mt-3 truncate">{{ m.producto }}</p>
                <p class="text-xs text-gray-400 truncate">{{ m.marca }} · {{ m.categoria }}</p>

                <div class="flex items-end justify-between gap-2 mt-3">
                  <div>
                    <p class="text-lg font-black text-gray-900 tabular-nums leading-none">{{ textoPrecio(m) }}</p>
                    <p class="text-[11px] font-semibold text-gray-400 mt-1">{{ coloresUnicos(m).length }} color{{ coloresUnicos(m).length === 1 ? '' : 'es' }} · {{ stockTotalModelo(m) }} unid.</p>
                  </div>
                  <span v-if="enTicketModelo(m) > 0" class="text-[11px] font-bold text-white bg-[#ff8c42] rounded-full px-2.5 py-1">En ticket ×{{ enTicketModelo(m) }}</span>
                  <span v-else class="w-9 h-9 rounded-full bg-orange-50 text-[#ff8c42] group-hover:bg-[#ff8c42] group-hover:text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                  </span>
                </div>
              </button>
            </div>
            <div v-if="hayMas" class="mt-4 flex justify-center">
              <button type="button" class="h-11 px-6 rounded-xl border border-gray-200 bg-surface text-sm font-semibold text-gray-700 hover:border-[#ff8c42] hover:text-[#e67e2d] transition-colors disabled:opacity-60" :disabled="cargandoMas" @click="verMas">
                <i v-if="cargandoMas" class="fa-solid fa-spinner fa-spin mr-1.5" aria-hidden="true"></i>Ver más modelos
              </button>
            </div>
          </template>

          <EmptyHero
            v-else-if="busquedaRealizada"
            icon="fa-solid fa-magnifying-glass"
            title="No hay prendas con stock para esa búsqueda"
            text="Prueba con otro nombre o cambia de categoría. Solo aparecen prendas que tengan unidades disponibles."
          />

          <EmptyHero
            v-else
            icon="fa-solid fa-bag-shopping"
            title="Busca la prenda que vas a vender"
            text="Elige una categoría o escribe un modelo o una marca. Al tocar una prenda se agrega al ticket."
          />
        </section>

        <!-- ======================= DERECHA: ticket ======================= -->
        <aside
          id="ticket-venta"
          :class="[
            'bg-surface border border-gray-100 shadow-sm flex flex-col',
            'max-xl:fixed max-xl:inset-x-0 max-xl:bottom-0 max-xl:z-[55] max-xl:max-h-[92dvh] max-xl:overflow-y-auto max-xl:overscroll-contain max-xl:rounded-t-3xl max-xl:shadow-2xl max-xl:transition-transform max-xl:duration-300 max-xl:ease-[cubic-bezier(0.32,0.72,0,1)] motion-reduce:max-xl:transition-none',
            ticketAbierto ? 'max-xl:translate-y-0' : 'max-xl:translate-y-full max-xl:invisible',
            'xl:sticky xl:top-6 xl:rounded-3xl xl:overflow-hidden',
          ]"
          ref="hoja"
          :style="esMovil && arrastre.activo ? { transform: `translateY(${arrastre.y}px)`, transition: 'none' } : undefined"
          @touchstart.passive="iniciarArrastre"
          @touchmove="moverArrastre"
          @touchend="terminarArrastre"
          @touchcancel="terminarArrastre"
          :role="esMovil ? 'dialog' : undefined"
          :aria-modal="esMovil ? 'true' : undefined"
          aria-label="Ticket de venta"
        >
          <!-- Asa y cierre (solo celular) -->
          <div class="xl:hidden flex justify-center pt-2.5 pb-1.5 flex-shrink-0" data-arrastre aria-hidden="true"><span class="w-10 h-1 rounded-full bg-gray-300"></span></div>
          <header class="px-5 py-4 border-b border-gray-100 flex items-center gap-3" data-arrastre>
            <span class="w-10 h-10 rounded-2xl bg-orange-50 text-[#ff8c42] flex items-center justify-center"><i class="fa-solid fa-receipt"></i></span>
            <div class="flex-1">
              <h3 class="font-bold text-gray-900 leading-tight">Ticket</h3>
              <p class="text-xs text-gray-400">Venta en mostrador</p>
            </div>
            <span class="text-xs font-bold text-[#e67e2d] bg-orange-50 rounded-full px-2.5 py-1">{{ totalPrendas() }} prenda{{ totalPrendas() === 1 ? '' : 's' }}</span>
            <button type="button" class="xl:hidden -mr-2 w-11 h-11 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center" aria-label="Cerrar el ticket y seguir agregando" @click="ticketAbierto = false">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </header>

          <!-- Líneas -->
          <div v-if="prendas.length === 0" class="px-6 py-10 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-gray-50 text-gray-300 flex items-center justify-center"><i class="fa-solid fa-cart-shopping"></i></div>
            <p class="text-sm font-semibold text-gray-600">El ticket está vacío</p>
            <p class="text-xs text-gray-400 mt-1">Toca una prenda para agregarla.</p>
          </div>

          <ul v-else class="divide-y divide-gray-50 xl:max-h-[26rem] xl:overflow-y-auto">
            <li v-for="prenda in prendas" :key="prenda.id" data-linea class="px-5 py-3.5">
              <div class="flex items-start gap-3">
                <span class="w-9 h-9 rounded-xl bg-gray-50 ring-1 ring-black/5 flex items-center justify-center flex-shrink-0">
                  <ColorSwatch :hex="prenda.colorHex" class="w-5 h-5 rounded-full border border-gray-200" />
                </span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-gray-900 truncate leading-tight">{{ prenda.producto }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ prenda.marca }} · Talla {{ prenda.talla }} · {{ prenda.color }}</p>
                </div>
                <button @click="eliminarPrenda(prenda.id)" type="button" :aria-label="`Quitar ${prenda.producto} talla ${prenda.talla} del ticket`" class="w-11 h-11 xl:w-7 xl:h-7 -mr-2 xl:mr-0 -mt-1.5 xl:mt-0 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 flex items-center justify-center flex-shrink-0 transition-colors">
                  <i class="fa-solid fa-xmark text-sm"></i>
                </button>
              </div>

              <div class="flex items-center justify-between gap-3 mt-3 pl-12">
                <div class="inline-flex items-center rounded-xl border border-gray-200 bg-surface overflow-hidden">
                  <button
                    type="button"
                    @click="cambiarCantidad(prenda, -1)"
                    :disabled="prenda.cantidad <= 1"
                    :aria-label="`Restar 1 a ${prenda.producto} talla ${prenda.talla}`"
                    class="w-11 h-11 xl:w-9 xl:h-9 flex items-center justify-center text-gray-500 hover:bg-orange-50 hover:text-[#ff8c42] disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition-colors"
                  ><i class="fa-solid fa-minus text-xs"></i></button>
                  <input
                    :value="prenda.cantidad"
                    @change="alEscribirCantidad(prenda, $event.target.value); $event.target.value = prenda.cantidad"
                    type="number"
                    min="1"
                    inputmode="numeric"
                    :aria-label="`Cantidad de ${prenda.producto} talla ${prenda.talla}`"
                    class="w-11 xl:w-10 text-center text-sm font-bold text-gray-900 border-0 p-0 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                  >
                  <button
                    type="button"
                    @click="cambiarCantidad(prenda, 1)"
                    :disabled="cantidadYaAgregada(prenda.variantId) >= prenda.stock"
                    :aria-label="`Sumar 1 a ${prenda.producto} talla ${prenda.talla}`"
                    class="w-11 h-11 xl:w-9 xl:h-9 flex items-center justify-center text-white bg-[#ff8c42] hover:bg-[#f27d30] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                  ><i class="fa-solid fa-plus text-xs"></i></button>
                </div>

                <div class="text-right">
                  <label class="flex items-center justify-end gap-1 text-xs text-gray-400">
                    c/u S/
                    <input
                      v-model.number="prenda.precioVenta"
                      type="number"
                      step="0.01"
                      min="0"
                      :aria-label="`Precio de ${prenda.producto} talla ${prenda.talla}`"
                      class="w-20 xl:w-16 h-10 xl:h-7 text-right text-sm xl:text-xs font-semibold text-gray-700 border border-gray-200 rounded-lg px-2 xl:px-1.5 focus:outline-none focus:border-[#ff8c42] focus:ring-2 focus:ring-orange-100"
                    >
                  </label>
                  <p class="text-sm font-bold text-gray-900 tabular-nums mt-0.5">S/ {{ subtotalDe(prenda).toFixed(2) }}</p>
                  <p v-if="bajoCosto(prenda)" class="mt-0.5 flex items-center justify-end gap-1 text-[11px] font-semibold text-amber-600" role="status">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Menos que su costo (S/ {{ prenda.costo.toFixed(2) }})
                  </p>
                </div>
              </div>
            </li>
          </ul>

          <!-- Datos de la venta -->
          <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-3 border-t border-gray-100">
            <p class="sm:col-span-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Datos de la venta</p>
            <div class="relative sm:col-span-2">
              <div class="flex items-center justify-between mb-1">
                <label for="venta-documento" class="text-xs font-semibold text-gray-600">Cliente (opcional)</label>
                <div class="inline-flex p-0.5 bg-gray-100 rounded-lg" role="group" aria-label="Tipo de documento">
                  <button v-for="op in ['DNI', 'RUC']" :key="op" type="button" @click="docTipo = op" :aria-pressed="docTipo === op"
                    :class="['px-2.5 py-0.5 text-[11px] font-bold rounded-md transition-all', docTipo === op ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">{{ op }}</button>
                </div>
              </div>

              <div v-if="!clienteSeleccionado" class="relative mb-2">
                <i :class="docEstado === 'buscando' ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-id-card'" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                <input id="venta-documento" v-model="docNumero" type="text" inputmode="numeric" autocomplete="off"
                  :placeholder="docTipo === 'DNI' ? 'DNI del cliente (8 dígitos)' : 'RUC del cliente (11 dígitos)'"
                  class="w-full h-11 sm:h-10 pl-9 pr-3 text-sm tracking-wider placeholder:tracking-normal border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
              </div>

              <div v-if="clienteSeleccionado" class="w-full min-h-10 px-3 py-1.5 border border-green-200 bg-green-50 rounded-xl flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 truncate leading-tight">{{ clienteSeleccionado.name }}</p>
                  <p class="text-xs text-green-700 truncate"><i class="fa-solid fa-circle-check"></i> Cliente registrado<template v-if="clienteSeleccionado.document_number"> · {{ clienteSeleccionado.document_type }} {{ clienteSeleccionado.document_number }}</template></p>
                </div>
                <button type="button" @click="limpiarCliente" aria-label="Quitar el cliente elegido" class="w-7 h-7 rounded-full text-gray-400 hover:text-gray-600 hover:bg-surface flex-shrink-0 flex items-center justify-center">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>

              <!-- Documento nuevo: el nombre viene de RENIEC/SUNAT (editable) y el cliente se registra al guardar la venta -->
              <div v-else-if="docEstadoNecesitaNombre" class="rounded-xl border border-orange-200 bg-orange-50/60 p-3">
                <p v-if="docEstado === 'nuevo'" class="flex items-center gap-1.5 text-xs font-semibold text-[#e67e2d] mb-2"><i class="fa-solid fa-user-plus"></i> Cliente nuevo · se registra al guardar la venta</p>
                <p v-else class="flex items-center gap-1.5 text-xs text-red-600 mb-2"><i class="fa-solid fa-circle-exclamation"></i> {{ docMensaje }}</p>
                <label for="venta-cliente" class="sr-only">Nombre del cliente</label>
                <input id="venta-cliente" v-model="ventaForm.cliente" type="text" maxlength="255" placeholder="Nombre del cliente"
                  class="w-full h-11 sm:h-10 px-3 text-sm font-semibold text-gray-900 border border-orange-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
              </div>

              <template v-else-if="!docEstadoNecesitaNombre">
                <div class="relative">
                  <i class="fa-regular fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                  <input id="venta-cliente"
                    v-model="ventaForm.cliente"
                    @input="onClienteInput"
                    @focus="showClienteSuggestions = true"
                    @blur="ocultarSugerenciasClienteConRetraso"
                    type="text"
                    autocomplete="off"
                    placeholder="O busca por nombre un cliente registrado"
                    class="w-full h-11 sm:h-10 px-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 pl-9"
                  >
                </div>
                <div
                  v-if="showClienteSuggestions && clienteResultados.length > 0"
                  class="absolute z-20 mt-1.5 w-full bg-surface border border-gray-100 rounded-xl shadow-xl ring-1 ring-black/5 p-1 max-h-52 overflow-y-auto"
                >
                  <button
                    v-for="c in clienteResultados"
                    :key="c.id"
                    type="button"
                    @mousedown.prevent="seleccionarCliente(c)"
                    class="w-full text-left px-3 py-2 rounded-lg hover:bg-orange-50 transition-colors"
                  >
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ c.name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ c.email }}</p>
                  </button>
                </div>
              </template>
              <p v-if="!clienteSeleccionado && !docEstadoNecesitaNombre && docEstado !== 'buscando'" class="text-[11px] text-gray-400 mt-1.5">Con el DNI se busca en tus clientes y, si no está, en RENIEC. Déjalo vacío para una venta sin cliente.</p>
            </div>

            <div>
              <label for="registrar-1" class="block text-xs font-semibold text-gray-600 mb-1">Fecha</label>
              <input id="registrar-1" v-model="ventaForm.fecha" type="date" class="w-full h-11 sm:h-10 px-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
            </div>

            <div>
              <label for="registrar-2" class="block text-xs font-semibold text-gray-600 mb-1">Notas (opcional)</label>
              <input id="registrar-2" v-model="ventaForm.notas" type="text" placeholder="Cualquier detalle importante..." class="w-full h-11 sm:h-10 px-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
            </div>
          </div>

          <!-- Totales y guardar -->
          <footer class="px-5 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] border-t border-gray-100 bg-gray-50 space-y-3 max-xl:sticky max-xl:bottom-0 max-xl:z-10 max-xl:shadow-[0_-8px_16px_-12px_rgba(0,0,0,0.25)]">
            <div class="flex items-center justify-between text-sm text-gray-500">
              <span>Prendas</span>
              <span class="font-semibold text-gray-900 tabular-nums">{{ totalPrendas() }}</span>
            </div>
            <div class="flex items-baseline justify-between">
              <span class="font-bold text-gray-900">Total</span>
              <span class="text-3xl font-black text-[#ff8c42] tabular-nums">S/ {{ totalVenta().toFixed(2) }}</span>
            </div>

            <button
              @click="guardarVenta"
              :disabled="isSaving || prendas.length === 0"
              type="button"
              class="w-full h-12 flex items-center justify-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-base sm:text-sm rounded-xl shadow-md shadow-orange-200 transition-all"
            >
              <i :class="isSaving ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-floppy-disk'"></i> {{ isSaving ? 'Guardando...' : 'Guardar Venta' }}
            </button>
            <button
              v-if="prendas.length > 0"
              @click="vaciarTicket"
              type="button"
              class="w-full h-10 text-xs font-semibold text-gray-400 hover:text-red-500 transition-colors"
            >Vaciar ticket</button>
          </footer>
        </aside>
      </div>
    </div>
    <!-- Celular: fondo del ticket y barra fija con el resumen -->
    <div v-if="ticketAbierto" class="xl:hidden fixed inset-0 z-[54] bg-black/50" aria-hidden="true" @click="ticketAbierto = false"></div>
    <div v-if="prendas.length > 0 && !ticketAbierto" class="xl:hidden fixed inset-x-0 bottom-0 z-40 px-3 pt-6 pb-[max(0.75rem,env(safe-area-inset-bottom))] bg-gradient-to-t from-canvas via-canvas/95 to-transparent pointer-events-none">
      <button
        type="button"
        class="pointer-events-auto w-full h-14 rounded-2xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.99] text-white shadow-xl shadow-orange-300/40 flex items-center gap-3 px-4 transition-all"
        aria-controls="ticket-venta"
        :aria-expanded="ticketAbierto"
        @click="ticketAbierto = true"
      >
        <span class="relative w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0" aria-hidden="true">
          <i class="fa-solid fa-receipt"></i>
        </span>
        <span class="flex-1 text-left leading-tight">
          <span class="block text-xs font-semibold text-white/80">{{ totalPrendas() }} prenda{{ totalPrendas() === 1 ? '' : 's' }} en el ticket</span>
          <span class="block text-lg font-black tabular-nums">S/ {{ totalVenta().toFixed(2) }}</span>
        </span>
        <span class="text-sm font-bold flex items-center gap-1.5">Ver ticket <i class="fa-solid fa-chevron-up text-xs" aria-hidden="true"></i></span>
      </button>
    </div>
    <BoletaModal
      v-if="boletaAbierta"
      :venta-id="boletaAbierta.ventaId"
      :resumen="boletaAbierta.resumen"
      @close="boletaAbierta = null"
    />
    <CamaraScanner
      v-if="camaraAbierta"
      continuo
      titulo="Escanear prendas"
      :respuesta="camaraRespuesta"
      :resumen="resumenTicket"
      @codigo="alLeerConCamara"
      @terminar="terminarEscaneo"
      @close="camaraAbierta = false"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { avisar, confirmar } from '@/alerts';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import BoletaModal from './BoletaModal.vue';
import EmptyHero from '@/Components/Admin/EmptyHero.vue';
import { ORDEN_TALLAS } from '@/Components/Admin/tallas';

const CamaraScanner = defineAsyncComponent(() => import('@/Components/Admin/CamaraScanner.vue')); // carga la librería de la cámara solo al abrirla

const props = defineProps({
  categorias: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();

// OJO: no usar Date().toISOString() para "hoy" — toISOString() siempre convierte a UTC,
// así que de 7pm a 12am hora Perú (UTC-5) ya cuenta como "mañana" en UTC y la venta se
// guarda con la fecha equivocada. Se arma la fecha con los componentes locales del navegador.
const hoyLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const ventaForm = ref({
  fecha: hoyLocal(),
  cliente: '',
  notas: '',
});

const clienteSeleccionado = ref(null);

// Cliente por documento: primero se busca entre los clientes registrados y, si no está, en RENIEC/SUNAT.
// El cliente nuevo NO se crea aquí; viaja con la venta y se registra al guardarla.
const docTipo = ref('DNI');
const docNumero = ref('');
const docEstado = ref(null); // null | 'buscando' | 'nuevo' | 'error'
const docMensaje = ref('');
const docNecesitaNombre = () => docEstado.value === 'nuevo' || docEstado.value === 'error';
const docEstadoNecesitaNombre = computed(docNecesitaNombre);
const docLongitud = computed(() => (docTipo.value === 'DNI' ? 8 : 11));
let docSolicitud = 0;

const reiniciarDocumento = () => {
  docEstado.value = null;
  docMensaje.value = '';
  clienteSeleccionado.value = null;
  ventaForm.value.cliente = '';
};

const buscarDocumento = async () => {
  const solicitud = ++docSolicitud;
  docEstado.value = 'buscando';
  docMensaje.value = '';

  try {
    const { data } = await axios.get(route('admin.sales.buscar-documento'), { params: { tipo: docTipo.value, numero: docNumero.value } });
    if (solicitud !== docSolicitud) return; // el vendedor ya cambió el número

    if (data.existe) {
      seleccionarCliente(data.cliente);
      docEstado.value = null;
    } else {
      ventaForm.value.cliente = data.nombre;
      docEstado.value = 'nuevo';
    }
  } catch (e) {
    if (solicitud !== docSolicitud) return;
    docEstado.value = 'error';
    docMensaje.value = e.response?.data?.message || 'No se pudo consultar el documento. Escribe el nombre a mano.';
  }
};

watch(docNumero, (valor) => {
  const limpio = valor.replace(/\D/g, '').slice(0, docLongitud.value);
  if (limpio !== valor) {
    docNumero.value = limpio;
    return;
  }
  docSolicitud++;
  reiniciarDocumento();
  if (limpio.length === docLongitud.value) buscarDocumento();
});

watch(docTipo, () => {
  docNumero.value = '';
});
const clienteResultados = ref([]);
const showClienteSuggestions = ref(false);
let clienteSearchTimer = null;

const buscarClientes = () => {
  clearTimeout(clienteSearchTimer);
  const q = ventaForm.value.cliente.trim();

  if (q.length < 2) {
    clienteResultados.value = [];
    return;
  }

  clienteSearchTimer = setTimeout(async () => {
    try {
      const res = await fetch(`/admin/ventas/buscar-clientes?q=${encodeURIComponent(q)}`);
      clienteResultados.value = await res.json();
    } catch (e) {
      console.error('Falló la búsqueda de clientes', e);
      clienteResultados.value = [];
    }
  }, 300);
};

const onClienteInput = () => {
  clienteSeleccionado.value = null;
  showClienteSuggestions.value = true;
  buscarClientes();
};

const seleccionarCliente = (c) => {
  clienteSeleccionado.value = c;
  ventaForm.value.cliente = c.name;
  showClienteSuggestions.value = false;
};

const limpiarCliente = () => {
  clienteSeleccionado.value = null;
  docNumero.value = '';
  ventaForm.value.cliente = '';
  clienteResultados.value = [];
};

const ocultarSugerenciasClienteConRetraso = () => {
  setTimeout(() => { showClienteSuggestions.value = false; }, 150);
};

// Categorías como "chips": "Todas" + una por categoría.
const filtroCategoria = ref('');
const opcionesCategoria = computed(() => ['', ...props.categorias]);

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const busquedaRealizada = ref(false);
let searchTimer = null;

const isSaving = ref(false);
const prendas = ref([]);

// Cada resultado es un MODELO con todas sus variantes (color × talla) disponibles. Al abrir uno se ve su cuadrícula.
const modeloAbiertoId = ref(null);
const filtroColor = ref('');

const modeloAbierto = computed(() => searchResults.value.find((m) => m.productId === modeloAbiertoId.value) ?? null);

const abrirModelo = (m) => {
  modeloAbiertoId.value = m.productId;
  filtroColor.value = '';
};
const cerrarModelo = () => { modeloAbiertoId.value = null; };

const tallasDelModelo = computed(() => {
  if (!modeloAbierto.value) return [];
  const set = new Set(modeloAbierto.value.variantes.map((v) => v.talla));

  return [...set].sort((a, b) => {
    const ia = ORDEN_TALLAS.indexOf(a);
    const ib = ORDEN_TALLAS.indexOf(b);
    return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib) || String(a).localeCompare(String(b));
  });
});

const coloresDelModelo = computed(() => {
  if (!modeloAbierto.value) return [];
  const mapa = new Map();

  modeloAbierto.value.variantes.forEach((v) => {
    if (!mapa.has(v.color)) mapa.set(v.color, { nombre: v.color, hex: v.colorHex, celdas: {} });
    mapa.get(v.color).celdas[v.talla] = v;
  });

  return [...mapa.values()].sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const coloresVisibles = computed(() => {
  const q = filtroColor.value.trim().toLowerCase();
  return q ? coloresDelModelo.value.filter((c) => c.nombre.toLowerCase().includes(q)) : coloresDelModelo.value;
});

// Colores distintos de un modelo (para los puntos de la tarjeta).
const coloresUnicos = (m) => {
  const vistos = new Map();
  m.variantes.forEach((v) => { if (!vistos.has(v.color)) vistos.set(v.color, { nombre: v.color, hex: v.colorHex }); });
  return [...vistos.values()];
};

const preciosDelModelo = (m) => [...new Set(m.variantes.map((v) => v.precioVenta))].sort((a, b) => a - b);
const textoPrecio = (m) => {
  const precios = preciosDelModelo(m);
  return precios.length === 1 ? `S/ ${precios[0].toFixed(2)}` : `Desde S/ ${precios[0].toFixed(2)}`;
};
const stockTotalModelo = (m) => m.variantes.reduce((sum, v) => sum + v.stock, 0);
const enTicketModelo = (m) => prendas.value
  .filter((p) => m.variantes.some((v) => v.variantId === p.variantId))
  .reduce((sum, p) => sum + p.cantidad, 0);

// Une la variante con los datos del modelo, como la espera el ticket.
const conModelo = (m, v) => ({ ...v, producto: m.producto, marca: m.marca });

// Los modelos llegan de 30 en 30: si la última página vino llena, se ofrece «Ver más modelos».
let paginaBusqueda = 1;
const hayMas = ref(false);
const cargandoMas = ref(false);

const verMas = async () => {
  cargandoMas.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value.trim()) params.set('q', searchQuery.value.trim());
    if (filtroCategoria.value) params.set('categoria', filtroCategoria.value);
    params.set('pagina', paginaBusqueda + 1);

    const mas = await (await fetch(`/admin/ventas/buscar-variantes?${params.toString()}`)).json();
    paginaBusqueda += 1;
    searchResults.value = [...searchResults.value, ...mas];
    hayMas.value = mas.length === 30;
  } finally {
    cargandoMas.value = false;
  }
};

const ejecutarBusqueda = () => {
  clearTimeout(searchTimer);

  const q = searchQuery.value.trim();
  const cat = filtroCategoria.value;

  // Una letra suelta no busca nada útil. Sin texto ni categoría se listan los modelos con stock.
  if (q.length === 1) {
    searchResults.value = [];
    busquedaRealizada.value = false;
    return;
  }

  searchTimer = setTimeout(async () => {
    isSearching.value = true;
    try {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (cat) params.set('categoria', cat);

      const res = await fetch(`/admin/ventas/buscar-variantes?${params.toString()}`);
      searchResults.value = await res.json();
      paginaBusqueda = 1;
      hayMas.value = searchResults.value.length === 30; // una página llena puede tener más detrás

      // Un solo modelo coincide: se abre directo su cuadrícula de colores y tallas (un toque menos).
      if (searchResults.value.length === 1 && (q || cat)) {
        abrirModelo(searchResults.value[0]);
      } else {
        // Cambió la búsqueda o la categoría: se vuelve a la lista de modelos encontrados.
        modeloAbiertoId.value = null;
      }
    } catch (e) {
      console.error('Falló la búsqueda de variantes', e);
      searchResults.value = [];
    } finally {
      isSearching.value = false;
      busquedaRealizada.value = true;
    }
  }, 300);
};

watch(filtroCategoria, ejecutarBusqueda);
watch(searchQuery, ejecutarBusqueda);
ejecutarBusqueda(); // al abrir, ya se ven los modelos con stock

// --- Código de barras (pistola USB/Bluetooth o cámara) ---------------------------------------
// La pistola "teclea" el código y pulsa Enter, así que basta con detectarlo en el buscador: letras opcionales
// (códigos propios "SU000123") y varios dígitos. Un texto como "air force" sigue siendo una búsqueda normal.
const pareceCodigo = (t) => /^[A-Za-z]{0,3}\d{4,}$/.test(t.replace(/\s+/g, ''));

const camaraAbierta = ref(false);
const mensajeEscaneo = ref(null);
let mensajeTimer = null;

const mostrarMensajeEscaneo = (texto, error = false) => {
  clearTimeout(mensajeTimer);
  mensajeEscaneo.value = { texto, error };
  mensajeTimer = setTimeout(() => { mensajeEscaneo.value = null; }, 3500);
};

// Devuelve { error, texto } para que la cámara muestre qué pasó con la lectura.
const procesarCodigo = async (codigo) => {
  try {
    const { data: v } = await axios.get(route('admin.sales.search-codigo'), { params: { codigo } });
    const nombre = `${v.producto} · ${v.color} · ${v.talla}`;

    if (restante(v) <= 0) return { error: true, texto: `${nombre}: ya agregaste todo el stock.` };

    agregarRapido(v);
    return { error: false, texto: nombre };
  } catch (e) {
    return { error: true, texto: e.response?.data?.message || 'No se pudo leer el código.' };
  }
};

// Desde el buscador (pistola o escritura): aviso en línea bajo el campo.
const agregarPorCodigo = async (codigo) => {
  const r = await procesarCodigo(codigo);
  mostrarMensajeEscaneo(r.error ? r.texto : `Agregado: ${r.texto}`, r.error);
};

// Desde la cámara: la lectura se queda en pantalla hasta elegir seguir o ir a la venta.
// Celular: el ticket es una hoja que sube desde abajo; en pantalla grande es la columna de la derecha.
const ticketAbierto = ref(false);
const esMovil = ref(typeof window !== 'undefined' && !window.matchMedia('(min-width: 1280px)').matches);
if (typeof window !== 'undefined') {
  const mq = window.matchMedia('(min-width: 1280px)');
  mq.addEventListener('change', (e) => {
    esMovil.value = !e.matches;
    if (e.matches) ticketAbierto.value = false;
  });
}
// Bajar la hoja con el dedo para cerrarla: se arrastra desde el asa o la cabecera, o desde cualquier parte cuando la
// lista está arriba del todo. Si se suelta pasados 120 px (o con un gesto rápido) se cierra; si no, vuelve a su sitio.
const hoja = ref(null);
const arrastre = reactive({ candidato: false, activo: false, desdeCabecera: false, inicio: 0, y: 0, t0: 0 });

const iniciarArrastre = (e) => {
  if (!esMovil.value) return;
  arrastre.candidato = true;
  arrastre.activo = false;
  arrastre.desdeCabecera = !!e.target.closest('[data-arrastre]');
  arrastre.inicio = e.touches[0].clientY;
  arrastre.t0 = Date.now();
  arrastre.y = 0;
};

const moverArrastre = (e) => {
  if (!arrastre.candidato) return;

  const dy = e.touches[0].clientY - arrastre.inicio;
  if (!arrastre.activo) {
    const arribaDelTodo = (hoja.value?.scrollTop ?? 0) <= 0;
    if (dy > 8 && (arrastre.desdeCabecera || arribaDelTodo)) arrastre.activo = true;
    else return;
  }

  e.preventDefault();
  arrastre.y = Math.max(0, dy);
};

const terminarArrastre = () => {
  if (arrastre.activo) {
    const velocidad = arrastre.y / Math.max(1, Date.now() - arrastre.t0);
    if (arrastre.y > 120 || velocidad > 0.6) ticketAbierto.value = false;
  }
  arrastre.candidato = false;
  arrastre.activo = false;
  arrastre.y = 0;
};

watch(ticketAbierto, (abierto) => {
  document.body.style.overflow = abierto && esMovil.value ? 'hidden' : '';
});

const camaraRespuesta = ref(null);
const alLeerConCamara = async (codigo) => {
  camaraRespuesta.value = null;
  camaraRespuesta.value = await procesarCodigo(codigo);
};
const terminarEscaneo = async () => {
  camaraAbierta.value = false;
  await nextTick();
  if (esMovil.value) ticketAbierto.value = true;
  else document.querySelector('#ticket-venta')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
const resumenTicket = computed(() => {
  const n = totalPrendas();
  return `${n} ${n === 1 ? 'prenda' : 'prendas'} · S/ ${totalVenta().toFixed(2)}`;
});

const alEnter = () => {
  const texto = searchQuery.value.trim();
  if (!pareceCodigo(texto)) return;

  searchQuery.value = '';
  agregarPorCodigo(texto);
};

// Cuánto de una variante ya está en el ticket, para no exceder el stock real.
const cantidadYaAgregada = (variantId) =>
  prendas.value.filter((p) => p.variantId === variantId).reduce((sum, p) => sum + p.cantidad, 0);

const restante = (v) => v.stock - cantidadYaAgregada(v.variantId);

// Vender por debajo del costo no se bloquea (a veces es una liquidación), pero se avisa para que no sea un descuido.
const bajoCosto = (p) => p.costo > 0 && precioValido(p.precioVenta) && Number(p.precioVenta) < p.costo;

const precioValido = (n) => n !== '' && n != null && Number(n) >= 0;

// Tocar una prenda la agrega al ticket (1 unidad al precio de lista); si ya estaba con ese precio, suma 1.
const agregarRapido = (v) => {
  if (restante(v) <= 0) return;

  const existente = prendas.value.find((p) => p.variantId === v.variantId && Number(p.precioVenta) === Number(v.precioVenta));

  if (existente) {
    existente.cantidad += 1;
    return;
  }

  prendas.value.push({
    id: Date.now() + Math.random(),
    variantId: v.variantId,
    producto: v.producto,
    marca: v.marca,
    talla: v.talla,
    color: v.color,
    colorHex: v.colorHex,
    stock: v.stock,
    cantidad: 1,
    precioVenta: v.precioVenta,
    costo: Number(v.costo) || 0,
  });
};

// "−" y "+" de cada línea del ticket: nunca bajan de 1 (para quitar se usa la X) ni superan el stock.
const cambiarCantidad = (prenda, delta) => {
  const nueva = prenda.cantidad + delta;
  const otrasLineas = cantidadYaAgregada(prenda.variantId) - prenda.cantidad;

  if (nueva < 1 || nueva + otrasLineas > prenda.stock) return;
  prenda.cantidad = nueva;
};

const alEscribirCantidad = (prenda, valor) => {
  const n = parseInt(valor, 10);
  const otrasLineas = cantidadYaAgregada(prenda.variantId) - prenda.cantidad;
  const maximo = prenda.stock - otrasLineas;

  prenda.cantidad = Number.isFinite(n) ? Math.min(Math.max(n, 1), maximo) : 1;
};

const eliminarPrenda = (id) => {
  prendas.value = prendas.value.filter((p) => p.id !== id);
};

const vaciarTicket = () => {
  prendas.value = [];
};

const subtotalDe = (p) => p.cantidad * (Number(p.precioVenta) || 0);
const totalVenta = () => prendas.value.reduce((sum, p) => sum + subtotalDe(p), 0);
const totalPrendas = () => prendas.value.reduce((sum, p) => sum + p.cantidad, 0);

// Al guardar se abre la boleta en un modal (con imprimir, ver en historial o seguir vendiendo).
const boletaAbierta = ref(null); // { ventaId, resumen }
// Al abrirse la boleta, la hoja del ticket (celular) se cierra.
watch(boletaAbierta, (b) => { if (b) ticketAbierto.value = false; });

const avisarVentaGuardada = (resumen) => {
  const ventaId = page.props.flash?.ventaId;
  if (!ventaId) return;

  boletaAbierta.value = { ventaId, resumen };
};

const guardarVenta = async () => {
  if (prendas.value.length === 0) return;

  if (prendas.value.some((p) => !precioValido(p.precioVenta))) {
    avisar('Todas las prendas del ticket necesitan un precio de venta.', { titulo: 'Revisa los precios' });
    return;
  }

  const bajoElCosto = prendas.value.filter(bajoCosto);
  if (bajoElCosto.length) {
    const nombres = bajoElCosto.map((p) => `${p.producto} · ${p.color} · ${p.talla}`).join('; ');
    const seguir = await confirmar(`Estas prendas se venden por debajo de su costo y darán pérdida: ${nombres}`, { titulo: '¿Guardar igual?', confirmarTexto: 'Sí, guardar', cancelarTexto: 'Revisar precios' });
    if (!seguir) return;
  }

  isSaving.value = true;
  const resumen = { total: totalVenta(), prendas: totalPrendas(), cliente: ventaForm.value.cliente.trim() };

  router.post(route('admin.sales.store'), {
    fecha: ventaForm.value.fecha,
    cliente: ventaForm.value.cliente,
    clienteId: clienteSeleccionado.value?.id ?? null,
    documentoTipo: docEstadoNecesitaNombre.value ? docTipo.value : null,
    documentoNumero: docEstadoNecesitaNombre.value ? docNumero.value : null,
    notas: ventaForm.value.notas,
    prendas: prendas.value.map((p) => ({
      variantId: p.variantId,
      cantidad: p.cantidad,
      precioVenta: p.precioVenta,
    })),
  }, {
    onSuccess: () => {
      avisarVentaGuardada(resumen);
      // Deja la fecha tal cual (para seguir registrando ventas del mismo día) y limpia
      // el resto, listo para la siguiente venta sin salir de esta página.
      ventaForm.value.cliente = '';
      ventaForm.value.notas = '';
      clienteSeleccionado.value = null;
      clienteResultados.value = [];
      docNumero.value = '';
      prendas.value = [];
      searchQuery.value = '';
      ejecutarBusqueda();
    },
    onError: (errors) => {
      const primerError = Object.values(errors)[0];
      avisar(primerError || 'Ocurrió un error al guardar la venta. Revisa los datos e intenta de nuevo.', { titulo: 'No se pudo guardar la venta', tipo: 'error' });
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>
