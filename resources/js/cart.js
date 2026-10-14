import axios from 'axios';

const CART_KEY = 'shopping_cart';
const CART_VERSION = 2;

function dispatchCartUpdated() {
  window.dispatchEvent(new Event('cart-updated'));
}

function readRawItems() {
  try {
    const stored = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
    return Array.isArray(stored) ? stored : [];
  } catch {
    return [];
  }
}

function normalizeItem(item) {
  const variantId = Number(item?.variantId ?? item?.variant_id ?? 0);
  const quantity = Number(item?.cantidad ?? item?.quantity ?? 0);

  if (!variantId || quantity < 1) return null;

  return {
    ...item,
    variantId,
    cantidad: Math.floor(quantity),
  };
}

export function getItems() {
  const rawItems = readRawItems();
  const items = rawItems.map(normalizeItem).filter(Boolean);

  if (JSON.stringify(items) !== JSON.stringify(rawItems)) {
    saveItems(items, false);
  }

  return items;
}

export function saveItems(items, notify = true) {
  localStorage.setItem(CART_KEY, JSON.stringify(items));
  localStorage.setItem(`${CART_KEY}_version`, String(CART_VERSION));
  if (notify) dispatchCartUpdated();
}

function apiPayload(items) {
  return {
    items: items.map(item => ({
      variant_id: Number(item.variantId),
      quantity: Number(item.cantidad),
    })),
  };
}

function mapServerItem(item) {
  return {
    id: `variant:${item.variant_id}`,
    variantId: item.variant_id,
    productId: item.product_id,
    name: item.name,
    price: Number(item.unit_price),
    image: item.image,
    colorId: item.color_id,
    colorNombre: item.color,
    talla: item.size,
    cantidad: Number(item.quantity),
    stock: Number(item.available_stock),
    subtotal: Number(item.subtotal),
  };
}

function saveServerPayload(payload) {
  const errorsByVariant = new Map(
    (payload.errors || []).map(error => [Number(error.variant_id), error]),
  );

  const items = (payload.items || []).map(item => {
    const mapped = mapServerItem(item);
    const error = errorsByVariant.get(Number(item.variant_id));

    if (error?.available_stock !== undefined) {
      mapped.cantidad = Math.min(mapped.cantidad, Number(error.available_stock));
      mapped.subtotal = Number((mapped.price * mapped.cantidad).toFixed(2));
    }

    return mapped;
  }).filter(item => item.cantidad > 0);

  saveItems(items);

  return {
    ...payload,
    items,
  };
}

export function totalItems() {
  return getItems().reduce((total, item) => total + item.cantidad, 0);
}

export async function validateGuestCart() {
  const items = getItems();

  if (!items.length) {
    return {
      valid: true,
      items: [],
      errors: [],
      summary: { subtotal: 0, tax: 0, shipping: 0, total: 0, total_items: 0 },
    };
  }

  const { data } = await axios.post('/api/cart/validate', apiPayload(items));
  return saveServerPayload(data);
}

export async function hydrateCustomerCart(customerId) {
  const localItems = getItems();
  const activeCustomerKey = localStorage.getItem(`${CART_KEY}_customer_id`);
  let response;

  if (localItems.length && activeCustomerKey !== String(customerId)) {
    const { data } = await axios.post('/api/cart/sync', apiPayload(localItems));
    response = data;
  } else {
    const { data } = await axios.get('/api/cart');
    response = data;
  }

  localStorage.setItem(`${CART_KEY}_customer_id`, String(customerId));
  return saveServerPayload(response);
}

export async function add(product) {
  const variantId = Number(product.variantId ?? product.variant_id ?? 0);
  if (!variantId) {
    throw new Error('No se pudo identificar la variante del producto.');
  }

  const items = getItems();
  const existing = items.some(item => item.variantId === variantId);
  const nextItems = existing
    ? items.map(item => item.variantId === variantId
      ? { ...item, cantidad: item.cantidad + (product.cantidad || 1) }
      : item)
    : [...items, { ...product, variantId, cantidad: product.cantidad || 1 }];

  const customerId = window.__streetUrbanCustomerId;
  if (customerId) {
    const { data } = await axios.post('/api/cart/items', {
      variant_id: variantId,
      quantity: product.cantidad || 1,
    });
    return saveServerPayload(data);
  }

  const { data } = await axios.post('/api/cart/validate', apiPayload(nextItems));
  if (!data.valid) return saveServerPayload({ ...data, items });
  return saveServerPayload(data);
}

export async function setQuantity(lineId, cantidad) {
  const items = getItems();
  const nextQuantity = Number(cantidad);
  const item = items.find(current => current.id === lineId || current.variantId === Number(lineId));
  if (!item) return null;

  if (nextQuantity <= 0) return removeItem(lineId);

  const nextItems = items.map(current => current.id === item.id
    ? { ...current, cantidad: nextQuantity }
    : current);

  const customerId = window.__streetUrbanCustomerId;
  if (customerId) {
    const { data } = await axios.patch(`/api/cart/items/${item.variantId}`, {
      variant_id: item.variantId,
      quantity: nextQuantity,
    });
    return saveServerPayload(data);
  }

  const { data } = await axios.post('/api/cart/validate', apiPayload(nextItems));
  if (!data.valid) return saveServerPayload({ ...data, items });
  return saveServerPayload(data);
}

export async function removeItem(lineId) {
  const items = getItems();
  const item = items.find(current => current.id === lineId || current.variantId === Number(lineId));
  if (!item) return null;

  const customerId = window.__streetUrbanCustomerId;
  if (customerId) {
    const { data } = await axios.delete(`/api/cart/items/${item.variantId}`);
    return saveServerPayload(data);
  }

  const nextItems = items.filter(current => current.id !== item.id);
  const { data } = await axios.post('/api/cart/validate', apiPayload(nextItems));
  return saveServerPayload(data);
}