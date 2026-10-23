// Tallas por tipo de prenda. Cada categoría guarda su tipo (ropa, pantalón o calzado) y de ahí
// salen las tallas que se ofrecen al comprar y al agregar tallas a un modelo.
export const GRUPOS_TALLA = [
  {
    id: 'ropa',
    nombre: 'Ropa',
    detalle: 'XS, S, M, L, XL...',
    ejemplo: 'Polos, camisas, buzos',
    icono: 'fa-solid fa-shirt',
    tallas: ['Único', 'XS', 'S', 'M', 'L', 'XL', 'XXL'],
  },
  {
    id: 'pantalon',
    nombre: 'Pantalón',
    detalle: '28, 30, 32... hasta 40',
    ejemplo: 'Pantalones, jeans, shorts',
    icono: 'fa-solid fa-ruler-combined',
    tallas: ['28', '30', '32', '34', '36', '38', '40'],
  },
  {
    id: 'calzado',
    nombre: 'Calzado',
    detalle: '35, 36, 37... hasta 45',
    ejemplo: 'Zapatillas, zapatos, sandalias',
    icono: 'fa-solid fa-shoe-prints',
    tallas: ['35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45'],
  },
];

export const ORDEN_TALLAS = ['Único', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45'];

export const grupoDeTallas = (id) => GRUPOS_TALLA.find((g) => g.id === id) ?? GRUPOS_TALLA[0];
