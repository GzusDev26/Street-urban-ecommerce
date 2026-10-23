import Swal from 'sweetalert2';

// Avisos del panel con SweetAlert2 en vez de alert()/confirm() del navegador.
// Mismo estilo que el aviso de "¡Venta registrada!" (Ventas/Registrar.vue).
// El mensaje va como `text` (no `html`) porque a veces viene del servidor.

const CLASES = {
  popup: '!rounded-3xl !p-8',
  title: '!text-xl !font-bold !text-gray-900',
  htmlContainer: '!text-sm !text-gray-500 !leading-relaxed',
  actions: '!w-full !flex !gap-2 !mt-6',
  confirmButton: '!flex-1 !h-11 !rounded-xl !bg-[#ff8c42] hover:!bg-[#f27d30] !text-white !font-bold !text-sm !shadow-md !shadow-orange-200 !m-0',
  cancelButton: '!flex-1 !h-11 !rounded-xl !bg-white !border !border-gray-200 hover:!bg-gray-50 !text-gray-700 !font-semibold !text-sm !m-0',
};

const BOTON_PELIGRO = '!flex-1 !h-11 !rounded-xl !bg-red-500 hover:!bg-red-600 !text-white !font-bold !text-sm !shadow-md !shadow-red-200 !m-0';

const ICONOS = {
  warning: { icon: 'warning', iconColor: '#ff8c42' },
  error: { icon: 'error', iconColor: '#ef4444' },
  success: { icon: 'success', iconColor: '#22c55e' },
  info: { icon: 'info', iconColor: '#3b82f6' },
};

// Aviso con un solo botón. tipo: 'warning' (datos incompletos), 'error', 'success' o 'info'.
export function avisar(mensaje, { titulo = 'Revisa los datos', tipo = 'warning' } = {}) {
  return Swal.fire({
    ...ICONOS[tipo],
    title: titulo,
    text: mensaje,
    confirmButtonText: 'Entendido',
    buttonsStyling: false,
    customClass: CLASES,
  });
}

// Confirmación de dos botones; resuelve true solo si el usuario acepta.
// `peligro` pinta el botón de rojo y deja el foco en "Cancelar" para acciones que quitan algo.
export async function confirmar(mensaje, { titulo = '¿Estás seguro?', confirmarTexto = 'Sí, continuar', cancelarTexto = 'Cancelar', peligro = false } = {}) {
  const r = await Swal.fire({
    ...ICONOS.warning,
    title: titulo,
    text: mensaje,
    showCancelButton: true,
    confirmButtonText: confirmarTexto,
    cancelButtonText: cancelarTexto,
    reverseButtons: true,
    focusCancel: peligro,
    buttonsStyling: false,
    customClass: peligro ? { ...CLASES, confirmButton: BOTON_PELIGRO } : CLASES,
  });
  return r.isConfirmed;
}
