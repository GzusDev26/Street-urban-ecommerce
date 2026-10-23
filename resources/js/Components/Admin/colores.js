// Paleta de colores frecuentes en ropa y calzado, agrupada para elegirla con rapidez. Los 10 primeros
// (Rojo, Azul, Negro...) conservan su tono de siempre, así los colores ya registrados coinciden.
export const GRUPOS_COLOR = [
  {
    nombre: 'Básicos',
    colores: [
      { nombre: 'Negro', hex: '#1F2937' },
      { nombre: 'Blanco', hex: '#F3F4F6' },
      { nombre: 'Gris', hex: '#9CA3AF' },
      { nombre: 'Gris oscuro', hex: '#4B5563' },
      { nombre: 'Gris claro', hex: '#D1D5DB' },
      { nombre: 'Beige', hex: '#E8D9B5' },
      { nombre: 'Crema', hex: '#FFF4D6' },
    ],
  },
  {
    nombre: 'Azules',
    colores: [
      { nombre: 'Azul', hex: '#3B82F6' },
      { nombre: 'Azul noche', hex: '#1E2A4A' },
      { nombre: 'Azul marino', hex: '#1F3A68' },
      { nombre: 'Azul rey', hex: '#2563EB' },
      { nombre: 'Celeste', hex: '#7DD3FC' },
      { nombre: 'Turquesa', hex: '#14B8A6' },
    ],
  },
  {
    nombre: 'Verdes',
    colores: [
      { nombre: 'Verde', hex: '#10B981' },
      { nombre: 'Verde oliva', hex: '#6B7A3A' },
      { nombre: 'Verde militar', hex: '#4B5320' },
      { nombre: 'Verde botella', hex: '#14532D' },
      { nombre: 'Verde menta', hex: '#A7F3D0' },
      { nombre: 'Verde limón', hex: '#A3E635' },
    ],
  },
  {
    nombre: 'Rojos y rosas',
    colores: [
      { nombre: 'Rojo', hex: '#EF4444' },
      { nombre: 'Vino', hex: '#7F1D1D' },
      { nombre: 'Coral', hex: '#FF7F6B' },
      { nombre: 'Rosa', hex: '#EC4899' },
      { nombre: 'Rosa palo', hex: '#F4C2C2' },
      { nombre: 'Fucsia', hex: '#C026D3' },
      { nombre: 'Terracota', hex: '#C2623F' },
    ],
  },
  {
    nombre: 'Cálidos',
    colores: [
      { nombre: 'Naranja', hex: '#ff8c42' },
      { nombre: 'Amarillo', hex: '#FBBF24' },
      { nombre: 'Mostaza', hex: '#D4A017' },
    ],
  },
  {
    nombre: 'Tierra',
    colores: [
      { nombre: 'Marrón', hex: '#78350F' },
      { nombre: 'Camel', hex: '#C19A6B' },
      { nombre: 'Khaki', hex: '#BDB76B' },
      { nombre: 'Nude', hex: '#E3B79A' },
    ],
  },
  {
    nombre: 'Morados',
    colores: [
      { nombre: 'Púrpura', hex: '#A855F7' },
      { nombre: 'Lila', hex: '#C4B5FD' },
    ],
  },
  {
    nombre: 'Metálicos',
    colores: [
      { nombre: 'Dorado', hex: '#CA8A04' },
      { nombre: 'Plateado', hex: '#C0C0C0' },
    ],
  },
];

export const PALETA = GRUPOS_COLOR.flatMap((g) => g.colores);

// ¿Es un color claro? Sirve para que la marca de "elegido" (el check) se lea sobre fondos claros.
export const esColorClaro = (hex) => {
  const m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
  if (!m) return false;

  const [r, g, b] = [0, 2, 4].map((i) => parseInt(m[1].slice(i, i + 2), 16));
  return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.62;
};
