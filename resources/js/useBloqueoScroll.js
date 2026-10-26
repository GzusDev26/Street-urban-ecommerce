import { onBeforeUnmount, onMounted } from 'vue';

// Bloquea el desplazamiento de la página mientras hay una ventana abierta (modal, cámara, menú...). En el celular se
// desliza la página entera, así que sin esto la página de atrás se movería al deslizar el modal. Cuenta los bloqueos
// para que cerrar una ventana no libere la página si todavía hay otra abierta encima.
let activos = 0;

export const bloquearScroll = () => {
  if (activos++ === 0) document.body.style.overflow = 'hidden';

  let liberado = false;
  return () => {
    if (liberado) return;
    liberado = true;
    if (--activos === 0) document.body.style.overflow = '';
  };
};

// Para componentes que solo existen mientras están abiertos (v-if desde quien los usa).
export const useBloqueoScroll = () => {
  let liberar = null;
  onMounted(() => { liberar = bloquearScroll(); });
  onBeforeUnmount(() => liberar?.());
};
