// La wishlist vive en el servidor, ligada al cliente autenticado (no en localStorage),
// para que no "flote" compartida entre distintos usuarios del mismo navegador.
let idsCache = null;
let loadPromise = null;

function jsonHeaders() {
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
  };
}

function ensureLoaded() {
  if (idsCache) return Promise.resolve(idsCache);
  if (!loadPromise) {
    loadPromise = fetch('/api/wishlist', { headers: jsonHeaders() })
      .then(res => res.ok ? res.json() : [])
      .then(ids => {
        idsCache = new Set(ids);
        return idsCache;
      })
      .catch(() => {
        idsCache = new Set();
        return idsCache;
      });
  }
  return loadPromise;
}

export async function isInWishlist(productId) {
  const ids = await ensureLoaded();
  return ids.has(productId);
}

export async function totalItems() {
  const ids = await ensureLoaded();
  return ids.size;
}

// Alterna el producto en la wishlist del cliente autenticado. Devuelve true si quedó
// agregado, false si quedó removido, o null si la petición falló.
export async function toggle(productId) {
  try {
    const res = await fetch(`/api/wishlist/${productId}/toggle`, {
      method: 'POST',
      headers: jsonHeaders(),
    });

    if (!res.ok) return null;

    const data = await res.json();

    if (!idsCache) idsCache = new Set();
    if (data.added) {
      idsCache.add(productId);
    } else {
      idsCache.delete(productId);
    }

    window.dispatchEvent(new Event('wishlist-updated'));
    return data.added;
  } catch (e) {
    console.error('Falló la actualización de la lista de deseos', e);
    return null;
  }
}
