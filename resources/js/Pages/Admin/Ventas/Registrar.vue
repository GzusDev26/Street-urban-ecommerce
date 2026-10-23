<template>
  <Head title="Registrar Venta" />
  <AdminLayout>
    <template #breadcrumb>Ventas / Registrar</template>
    <template #header>Registrar Nueva Venta</template>

    <div class="space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200">
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
            class="text-xs font-semibold text-green-700 hover:text-green-800 bg-white border border-green-200 px-3.5 py-2 rounded-lg flex items-center gap-1.5"
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
        <section class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 sm:p-6">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h3 class="text-lg font-bold text-gray-900">Buscar prenda</h3>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 bg-gray-50 rounded-full px-3 py-1.5">
              <i class="fa-solid fa-boxes-stacked text-gray-400"></i> Solo se venden prendas con stock registrado
            </span>
          </div>

          <!-- Buscador grande -->
          <div class="relative mb-4">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            <input
              v-model="searchQuery"
              type="text"
              autocomplete="off"
              aria-label="Buscar por modelo o marca"
              placeholder="Escribe un modelo o una marca..."
              class="w-full h-12 pl-11 pr-11 rounded-2xl border border-gray-200 bg-white text-base text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
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

          <!-- Categorías como chips -->
          <div class="flex flex-wrap gap-2 mb-5" role="group" aria-label="Categorías">
            <button
              v-for="c in opcionesCategoria"
              :key="c || 'todas'"
              type="button"
              @click="filtroCategoria = c"
              :aria-pressed="filtroCategoria === c"
              :class="[
                'px-4 h-9 rounded-full text-sm font-semibold border transition-all',
                filtroCategoria === c
                  ? 'bg-[#ff8c42] border-[#ff8c42] text-white shadow-sm shadow-orange-200'
                  : 'bg-white border-gray-200 text-gray-600 hover:border-[#ff8c42] hover:text-[#e67e2d]',
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
                  <span class="font-semibold text-gray-700">Toca una casilla para agregar 1 unidad</span>
                  <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded border-2 border-gray-300 bg-white"></span> Disponibles</span>
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
                    class="h-9 w-44 pl-8 pr-3 text-sm border border-gray-200 rounded-xl bg-white placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
                  >
                </div>
              </div>

              <div class="overflow-x-auto rounded-2xl border border-gray-100">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                      <th class="text-left font-semibold py-3 px-4">Color</th>
                      <th v-for="t in tallasDelModelo" :key="t" class="text-center font-semibold py-3 px-1.5 min-w-[4rem]">{{ t }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="c in coloresVisibles" :key="c.nombre" data-color>
                      <td class="py-2.5 px-4">
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
                                    : 'border-gray-200 bg-white text-gray-800 hover:border-[#ff8c42] hover:bg-orange-50 active:scale-95',
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
                  enTicketModelo(m) > 0 ? 'border-[#ff8c42] bg-orange-50/60 hover:shadow-md' : 'border-gray-100 bg-white hover:border-[#ff8c42] hover:shadow-md active:scale-[0.99]',
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
        <aside class="xl:sticky xl:top-6 bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col" aria-label="Ticket de venta">
          <header class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-orange-50 text-[#ff8c42] flex items-center justify-center"><i class="fa-solid fa-receipt"></i></span>
            <div class="flex-1">
              <h3 class="font-bold text-gray-900 leading-tight">Ticket</h3>
              <p class="text-xs text-gray-400">Venta en mostrador</p>
            </div>
            <span class="text-xs font-bold text-[#e67e2d] bg-orange-50 rounded-full px-2.5 py-1">{{ totalPrendas() }} prenda{{ totalPrendas() === 1 ? '' : 's' }}</span>
          </header>

          <!-- Líneas -->
          <div v-if="prendas.length === 0" class="px-6 py-10 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-gray-50 text-gray-300 flex items-center justify-center"><i class="fa-solid fa-cart-shopping"></i></div>
            <p class="text-sm font-semibold text-gray-600">El ticket está vacío</p>
            <p class="text-xs text-gray-400 mt-1">Toca una prenda para agregarla.</p>
          </div>

          <ul v-else class="divide-y divide-gray-50 max-h-[26rem] overflow-y-auto">
            <li v-for="prenda in prendas" :key="prenda.id" data-linea class="px-5 py-3.5">
              <div class="flex items-start gap-3">
                <span class="w-9 h-9 rounded-xl bg-gray-50 ring-1 ring-black/5 flex items-center justify-center flex-shrink-0">
                  <ColorSwatch :hex="prenda.colorHex" class="w-5 h-5 rounded-full border border-gray-200" />
                </span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-gray-900 truncate leading-tight">{{ prenda.producto }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ prenda.marca }} · Talla {{ prenda.talla }} · {{ prenda.color }}</p>
                </div>
                <button @click="eliminarPrenda(prenda.id)" type="button" :aria-label="`Quitar ${prenda.producto} talla ${prenda.talla} del ticket`" class="w-7 h-7 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 flex items-center justify-center flex-shrink-0 transition-colors">
                  <i class="fa-solid fa-xmark text-sm"></i>
                </button>
              </div>

              <div class="flex items-center justify-between gap-3 mt-3 pl-12">
                <div class="inline-flex items-center rounded-xl border border-gray-200 bg-white overflow-hidden">
                  <button
                    type="button"
                    @click="cambiarCantidad(prenda, -1)"
                    :disabled="prenda.cantidad <= 1"
                    :aria-label="`Restar 1 a ${prenda.producto} talla ${prenda.talla}`"
                    class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-orange-50 hover:text-[#ff8c42] disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition-colors"
                  ><i class="fa-solid fa-minus text-xs"></i></button>
                  <input
                    :value="prenda.cantidad"
                    @change="alEscribirCantidad(prenda, $event.target.value); $event.target.value = prenda.cantidad"
                    type="number"
                    min="1"
                    inputmode="numeric"
                    :aria-label="`Cantidad de ${prenda.producto} talla ${prenda.talla}`"
                    class="w-10 text-center text-sm font-bold text-gray-900 border-0 p-0 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                  >
                  <button
                    type="button"
                    @click="cambiarCantidad(prenda, 1)"
                    :disabled="cantidadYaAgregada(prenda.variantId) >= prenda.stock"
                    :aria-label="`Sumar 1 a ${prenda.producto} talla ${prenda.talla}`"
                    class="w-9 h-9 flex items-center justify-center text-white bg-[#ff8c42] hover:bg-[#f27d30] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
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
                      class="w-16 h-7 text-right text-xs font-semibold text-gray-700 border border-gray-200 rounded-lg px-1.5 focus:outline-none focus:border-[#ff8c42] focus:ring-2 focus:ring-orange-100"
                    >
                  </label>
                  <p class="text-sm font-bold text-gray-900 tabular-nums mt-0.5">S/ {{ subtotalDe(prenda).toFixed(2) }}</p>
                </div>
              </div>
            </li>
          </ul>

          <!-- Datos de la venta -->
          <div class="px-5 py-4 grid grid-cols-2 gap-x-3 gap-y-3 border-t border-gray-100">
            <p class="col-span-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Datos de la venta</p>
            <div class="relative col-span-2">
              <div class="flex items-center justify-between mb-1">
                <label for="venta-documento" class="text-xs font-semibold text-gray-600">Cliente (opcional)</label>
                <div class="inline-flex p-0.5 bg-gray-100 rounded-lg" role="group" aria-label="Tipo de documento">
                  <button v-for="op in ['DNI', 'RUC']" :key="op" type="button" @click="docTipo = op" :aria-pressed="docTipo === op"
                    :class="['px-2.5 py-0.5 text-[11px] font-bold rounded-md transition-all', docTipo === op ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">{{ op }}</button>
                </div>
              </div>

              <div v-if="!clienteSeleccionado" class="relative mb-2">
                <i :class="docEstado === 'buscando' ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-id-card'" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                <input id="venta-documento" v-model="docNumero" type="text" inputmode="numeric" autocomplete="off"
                  :placeholder="docTipo === 'DNI' ? 'DNI del cliente (8 dígitos)' : 'RUC del cliente (11 dígitos)'"
                  class="w-full h-10 pl-9 pr-3 text-sm tracking-wider placeholder:tracking-normal border border-gray-200 rounded-xl bg-white placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
              </div>

              <div v-if="clienteSeleccionado" class="w-full min-h-10 px-3 py-1.5 border border-green-200 bg-green-50 rounded-xl flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 truncate leading-tight">{{ clienteSeleccionado.name }}</p>
                  <p class="text-xs text-green-700 truncate"><i class="fa-solid fa-circle-check"></i> Cliente registrado<template v-if="clienteSeleccionado.document_number"> · {{ clienteSeleccionado.document_type }} {{ clienteSeleccionado.document_number }}</template></p>
                </div>
                <button type="button" @click="limpiarCliente" aria-label="Quitar el cliente elegido" class="w-7 h-7 rounded-full text-gray-400 hover:text-gray-600 hover:bg-white flex-shrink-0 flex items-center justify-center">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>

              <!-- Documento nuevo: el nombre viene de RENIEC/SUNAT (editable) y el cliente se registra al guardar la venta -->
              <div v-else-if="docEstadoNecesitaNombre" class="rounded-xl border border-orange-200 bg-orange-50/60 p-3">
                <p v-if="docEstado === 'nuevo'" class="flex items-center gap-1.5 text-xs font-semibold text-[#e67e2d] mb-2"><i class="fa-solid fa-user-plus"></i> Cliente nuevo · se registra al guardar la venta</p>
                <p v-else class="flex items-center gap-1.5 text-xs text-red-600 mb-2"><i class="fa-solid fa-circle-exclamation"></i> {{ docMensaje }}</p>
                <label for="venta-cliente" class="sr-only">Nombre del cliente</label>
                <input id="venta-cliente" v-model="ventaForm.cliente" type="text" maxlength="255" placeholder="Nombre del cliente"
                  class="w-full h-10 px-3 text-sm font-semibold text-gray-900 border border-orange-200 rounded-xl bg-white placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
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
                    class="w-full h-10 px-3 text-sm border border-gray-200 rounded-xl bg-white placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 pl-9"
                  >
                </div>
                <div
                  v-if="showClienteSuggestions && clienteResultados.length > 0"
                  class="absolute z-20 mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-xl ring-1 ring-black/5 p-1 max-h-52 overflow-y-auto"
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
              <input id="registrar-1" v-model="ventaForm.fecha" type="date" class="w-full h-10 px-3 text-sm border border-gray-200 rounded-xl bg-white placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
            </div>

            <div>
              <label for="registrar-2" class="block text-xs font-semibold text-gray-600 mb-1">Notas (opcional)</label>
              <input id="registrar-2" v-model="ventaForm.notas" type="text" placeholder="Cualquier detalle importante..." class="w-full h-10 px-3 text-sm border border-gray-200 rounded-xl bg-white placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
            </div>
          </div>

          <!-- Totales y guardar -->
          <footer class="px-5 py-4 border-t border-gray-100 bg-gray-50/70 space-y-3">
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
              class="w-full h-12 flex items-center justify-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md shadow-orange-200 transition-all"
            >
              <i :class="isSaving ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-floppy-disk'"></i> {{ isSaving ? 'Guardando...' : 'Guardar Venta' }}
            </button>
            <button
              v-if="prendas.length > 0"
              @click="vaciarTicket"
              type="button"
              class="w-full text-xs font-semibold text-gray-400 hover:text-red-500 transition-colors"
            >Vaciar ticket</button>
          </footer>
        </aside>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import EmptyHero from '@/Components/Admin/EmptyHero.vue';
import { ORDEN_TALLAS } from '@/Components/Admin/tallas';

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

const ejecutarBusqueda = () => {
  clearTimeout(searchTimer);

  const q = searchQuery.value.trim();
  const cat = filtroCategoria.value;

  if (q.length < 2 && !cat) {
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

      // Un solo modelo coincide: se abre directo su cuadrícula de colores y tallas (un toque menos).
      if (searchResults.value.length === 1) {
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

// Cuánto de una variante ya está en el ticket, para no exceder el stock real.
const cantidadYaAgregada = (variantId) =>
  prendas.value.filter((p) => p.variantId === variantId).reduce((sum, p) => sum + p.cantidad, 0);

const restante = (v) => v.stock - cantidadYaAgregada(v.variantId);

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

const escapar = (t) => String(t).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

// Aviso al guardar: resume la venta y ofrece lo siguiente (imprimir, ver en historial o seguir vendiendo).
const avisarVentaGuardada = (resumen) => {
  const ventaId = page.props.flash?.ventaId;
  if (!ventaId) return;

  Swal.fire({
    icon: 'success',
    iconColor: '#22c55e',
    title: '¡Venta registrada!',
    html: `
      <p class="text-3xl font-black text-[#ff8c42] tabular-nums mt-1">S/ ${resumen.total.toFixed(2)}</p>
      <p class="text-sm text-gray-500 mt-2">${resumen.prendas} prenda${resumen.prendas === 1 ? '' : 's'}${resumen.cliente ? ` · ${escapar(resumen.cliente)}` : ''}</p>`,
    showDenyButton: true,
    showCancelButton: true,
    confirmButtonText: '<i class="fa-solid fa-print"></i>&nbsp; Imprimir boleta',
    denyButtonText: '<i class="fa-solid fa-receipt"></i>&nbsp; Ver en historial',
    cancelButtonText: 'Seguir vendiendo',
    buttonsStyling: false,
    reverseButtons: true,
    focusConfirm: true,
    customClass: {
      popup: '!rounded-3xl !p-8',
      title: '!text-2xl !font-bold !text-gray-900',
      actions: '!w-full !flex !flex-col-reverse !gap-2 !mt-6',
      confirmButton: '!w-full !h-12 !rounded-xl !bg-[#ff8c42] hover:!bg-[#f27d30] !text-white !font-bold !text-sm !shadow-md !shadow-orange-200 !m-0',
      denyButton: '!w-full !h-11 !rounded-xl !bg-white !border !border-gray-200 hover:!bg-gray-50 !text-gray-700 !font-semibold !text-sm !m-0',
      cancelButton: '!w-full !h-10 !bg-transparent !text-gray-400 hover:!text-gray-600 !font-semibold !text-sm !m-0',
    },
  }).then((r) => {
    if (r.isConfirmed) window.open(route('admin.sales.boleta', ventaId), '_blank');
    else if (r.isDenied) router.visit(`${route('admin.sales.index')}?venta=${ventaId}`);
  });
};

const guardarVenta = () => {
  if (prendas.value.length === 0) return;

  if (prendas.value.some((p) => !precioValido(p.precioVenta))) {
    alert('Revisa los precios: todas las prendas del ticket necesitan un precio de venta.');
    return;
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
      searchResults.value = [];
      busquedaRealizada.value = false;
    },
    onError: (errors) => {
      const primerError = Object.values(errors)[0];
      alert(primerError || 'Ocurrió un error al guardar la venta. Revisa los datos e intenta de nuevo.');
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>
