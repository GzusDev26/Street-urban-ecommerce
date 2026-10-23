// Única fuente del menú del panel: la usan el aside y el buscador rápido (Ctrl + K).
// `active` acepta un patrón de ruta o una lista; un ítem con `children` se despliega en el aside.
export function buildAdminNav(esAdmin) {
  return [
    {
      label: null,
      items: [
        { name: 'Dashboard', icon: 'fa-chart-pie', href: route('admin.dashboard'), active: 'admin.dashboard' },
      ],
    },
    {
      label: 'Operaciones',
      items: [
        {
          name: 'Ventas',
          icon: 'fa-cash-register',
          active: 'admin.sales.*',
          children: [
            { name: 'Registrar venta', href: route('admin.sales.create'), active: 'admin.sales.create' },
            { name: 'Historial', href: route('admin.sales.index'), active: ['admin.sales.index', 'admin.sales.boleta'] },
            { name: 'Reportes', href: route('admin.sales.reportes'), active: 'admin.sales.reportes' },
          ],
        },
        {
          name: 'Compras',
          icon: 'fa-boxes-stacked',
          active: 'admin.purchases.*',
          children: [
            { name: 'Inventario', href: route('admin.purchases.index'), active: 'admin.purchases.index' },
            { name: 'Historial', href: route('admin.purchases.historial'), active: 'admin.purchases.historial' },
            { name: 'Reportes', href: route('admin.purchases.reportes'), active: 'admin.purchases.reportes' },
          ],
        },
      ],
    },
    {
      label: 'Contactos',
      items: [
        { name: 'Clientes', icon: 'fa-user-group', href: route('admin.customers.index'), active: 'admin.customers.*' },
        { name: 'Proveedores', icon: 'fa-truck-fast', href: route('admin.suppliers.index'), active: 'admin.suppliers.*' },
      ],
    },
    ...(esAdmin ? [{
      label: 'Configuración',
      items: [
        { name: 'Usuarios', icon: 'fa-user-shield', href: route('admin.users.index'), active: 'admin.users.*' },
        { name: 'Datos del negocio', icon: 'fa-store', href: route('admin.settings.edit'), active: 'admin.settings.*' },
        { name: 'Categorías y marcas', icon: 'fa-tags', href: route('admin.taxonomies.index'), active: 'admin.taxonomies.*' },
        { name: 'Avatares', icon: 'fa-images', href: route('admin.avatars.index'), active: 'admin.avatars.*' },
      ],
    }] : []),
  ];
}

export function isRouteActive(active) {
  if (!active) return false;
  return (Array.isArray(active) ? active : [active]).some((pattern) => route().current(pattern));
}
