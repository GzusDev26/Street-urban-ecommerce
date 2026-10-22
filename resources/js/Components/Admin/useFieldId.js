import { inject, provide } from 'vue';

const CLAVE = Symbol('field-id');
let contador = 0;

// FormField genera un id y lo comparte con el campo que contiene, para que la <label for>
// quede asociada a su control (accesibilidad) sin repartir ids a mano en cada modal.
export const provideFieldId = () => {
  const id = `campo-${++contador}`;
  provide(CLAVE, id);
  return id;
};

export const useFieldId = () => inject(CLAVE, undefined);
