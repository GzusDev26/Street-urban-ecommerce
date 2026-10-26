# Street Urban Ecommerce

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Street Urban Ecommerce** is a full-stack e-commerce application built with Laravel 13 on the backend and Vue 3 on the frontend, using Inertia.js as the bridge. The app provides both a customer-facing storefront and an admin dashboard for product and customer management.

## Development Commands

### Essential Commands

- `npm install && composer install` - Install all dependencies
- `npm run dev` - Start Vite development server (frontend)
- `php artisan serve` - Start Laravel development server (backend on http://localhost:8000)
- `php artisan migrate` - Run database migrations
- `php artisan tinker` - Interactive shell for Laravel
- `php artisan queue:work` - Start queue worker for background jobs
- `npm run build` - Build frontend for production
- `php artisan lint` - Run PHPStan static analysis
- `npm run format` - Format code with Prettier (if configured)

### Running the Full Stack

In separate terminals:

```bash
# Terminal 1: Laravel backend
php artisan serve

# Terminal 2: Frontend dev server
npm run dev
```

Then visit `http://localhost:8000` in your browser. Vite will handle hot module reloading for Vue components.

## Architecture

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # Admin panel controllers
│   │   │   ├── ProductAdminController.php
│   │   │   └── CustomerAdminController.php
│   │   ├── Api/                # API endpoints
│   │   ├── Auth/               # Authentication controllers
│   │   ├── Shop/               # Customer-facing shop controllers
│   │   │   ├── ProductController.php
│   │   │   ├── CartController.php
│   │   │   ├── CheckoutValidateController.php
│   │   │   ├── ContactController.php
│   │   │   └── AboutController.php
│   │   └── ProfileController.php
│   ├── Requests/               # Form request validation
│   ├── Middleware/             # Custom middleware
│   └── Resources/              # API resource classes
├── Models/
│   ├── User.php               # User model
│   ├── Product.php            # Product model
│   ├── Sale.php               # Ventas (mostrador y web)
│   ├── SaleItem.php           # Líneas de venta, con costo real por lote
│   ├── Category.php           # Product categories
│   ├── Customer.php           # Customer information
│   └── Contact.php            # Contact form submissions
└── Providers/
    └── AppServiceProvider.php # Service provider

resources/
├── js/
│   ├── app.js                 # Main application entry
│   ├── bootstrap.js           # Bootstrap configuration
│   ├── cart.js                # Cart state management
│   ├── Components/            # Reusable Vue components
│   │   ├── shared/           # Generic UI components
│   │   ├── Navigation/       # Navigation components
│   │   ├── Product/          # Product-specific components
│   │   └── Forms/            # Form components
│   ├── Layouts/              # Layout components
│   │   ├── AppLayout.vue
│   │   └── GuestLayout.vue
│   └── Pages/                # Page components (Inertia routes)
│       ├── Admin/
│       ├── Shop/
│       ├── Auth/
│       └── Legal/
└── css/                      # Global styles

routes/
├── web.php                   # Web routes (frontend routes via Inertia)
└── api.php                   # (eliminado: no se cargaba; los endpoints /api/* viven en web.php)

database/
├── migrations/               # Database schema migrations
├── seeders/                  # Database seeders
└── factories/                # Model factories for testing

storage/
├── app/                      # Application files
├── logs/                     # Application logs
└── framework/                # Framework cache

config/
├── database.php              # Database configuration
├── app.php                   # Application configuration
├── auth.php                  # Authentication configuration
└── ... other config files
```

### Route Organization

Routes are organized in `routes/web.php` with route groups:

- **Shop Routes** (`/shop/*`) - Customer-facing storefront
  - Products listing, cart, checkout, contact, about
  - Legal pages (terms, privacy, returns, complaints)
  
- **Admin Routes** (`/admin/*`) - Admin dashboard (requires auth)
  - Product management (CRUD)
  - Customer management
  - Dashboard
  
- **Auth Routes** - Login, registration, password reset

### Database Models

Core models are in `app/Models/`:

- **User** - Authenticated users (admins and customers)
- **Product** - Products with pricing, stock, categories
- **Sale** / **SaleItem** - Ventas (mostrador y web); el costo sale de los lotes de compra (`PurchaseItem`) vía `InventoryService`
- **Category** - Product categories
- **Customer** - Customer profile information
- **Contact** - Contact form submissions

### Frontend with Inertia.js

The frontend uses Vue 3 components rendered via Inertia.js, which provides seamless server-side routing with client-side interactivity:

- **Server data flow**: Laravel controller → Inertia::render('Component', $data) → Vue component
- **Component locations**: `resources/js/Pages/` for full-page components, `resources/js/Components/` for reusable UI components
- **Styling**: Tailwind CSS (configured in `tailwind.config.js`)
- **State management**: Props passed from controllers, local component state with `ref()` and `reactive()`, cart stored in `resources/js/cart.js`

### Common Controller Pattern

Controllers follow this pattern:

```php
// app/Http/Controllers/Shop/ProductController.php
public function index()
{
    $products = Product::all();
    return Inertia::render('Shop/Products', [
        'products' => $products,
    ]);
}
```

The Vue component receives `products` as a prop via `defineProps()`.

## Technology Stack

### Backend
- **Framework**: Laravel 13.8
- **PHP**: 8.3+
- **Authentication**: Laravel Sanctum (API authentication)
- **ORM**: Eloquent
- **Database**: MySQL/PostgreSQL (configured in `.env`)

### Frontend
- **UI Framework**: Vue 3.4.0
- **Bridge**: Inertia.js 2.0
- **Build Tool**: Vite 8.0
- **Styling**: Tailwind CSS 3.2.1
- **Additional**: Alpine.js 3.4.2

### Development Tools
- **Laravel Vite Plugin**: For asset bundling
- **Tailwind Forms**: Styled form components
- **Composer**: PHP dependency manager
- **Node.js/npm**: JavaScript dependency manager

## Key Configurations

### Environment Setup

Copy `.env.example` to `.env` and configure:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=street_urban
DB_USERNAME=root
DB_PASSWORD=

APP_KEY=  # Generated with: php artisan key:generate
APP_DEBUG=true  # Set to false in production
```

### Database

Initialize the database with:

```bash
php artisan migrate        # Run all migrations
php artisan migrate:fresh  # Reset and re-run (development only)
php artisan db:seed        # Run seeders
```

### Asset Pipeline

- CSS is processed through Tailwind via `resources/css/app.css`
- Vue components are bundled by Vite in `resources/js/app.js`
- Run `npm run build` for production-optimized assets

## Development Guidelines

### When Adding a New Feature

1. **Database**: Create migration with `php artisan make:migration create_table_name`
2. **Model**: Create model with `php artisan make:model ModelName`
3. **Controller**: Create controller with `php artisan make:controller ControllerName`
4. **Route**: Add route in `routes/web.php` (`routes/api.php` no existe: los endpoints `/api/*` se definen en web.php)
5. **View**: Create Vue component in `resources/js/Pages/` or component in `resources/js/Components/`
6. **Validation**: Create form request with `php artisan make:request FormNameRequest`

### Adding Admin Features

1. Create controller in `app/Http/Controllers/Admin/`
2. Add routes under the admin middleware group in `routes/web.php`
3. Protect with `['auth', 'verified']` middleware
4. Create Vue components in `resources/js/Pages/Admin/`

### Adding Shop Features

1. Create controller in `app/Http/Controllers/Shop/`
2. Add routes in the shop route group (name: 'shop.')
3. Create Vue components in `resources/js/Pages/Shop/`

### Component Organization

- **Page components**: `resources/js/Pages/` - Full-page views (one component per route)
- **Reusable UI**: `resources/js/Components/` - Small, focused UI components
- **Layouts**: `resources/js/Layouts/` - Page layout wrappers
- **Styling**: Use Tailwind CSS classes directly; no separate CSS files needed

### Modo claro / oscuro

- El admin está diseñado en claro y la tienda en oscuro. Los grises y colores de estado (`gray-*`, `red-*`, `green-*`…) son variables CSS que se invierten solas en el modo contrario (ver el comentario al inicio de `tailwind.config.js`). No uses `dark:` para colores; escribe las clases como en el resto del archivo.
- Para fondos y texto principal usa los tokens: `bg-canvas` (fondo de página), `bg-surface` (tarjetas, inputs, modales), `bg-sunken` (footer/zonas hundidas), `text-fg` / `bg-fg` (texto principal o botón invertido). Evita `bg-white` y, en la tienda, `text-white` salvo sobre fotos o colores sólidos.
- `theme-fixed`: zona que nunca cambia (la boleta térmica). `theme-shop-dark`: pieza de la tienda que siempre va en oscuro (navbar sobre el hero).
- El botón sol/luna es `Components/ThemeToggle.vue`; la lógica está en `resources/js/theme.js`. Qué páginas son "tienda" lo decide `surfaceFor()` en `theme.js` y la misma regla en `resources/views/app.blade.php`.

### Imágenes

- **Del sitio** (logos, fotos de categorías base, hero, nosotros): en `public/image/`, **sí van en Git**.
- **Subidas desde el admin** (fotos de colores, portadas de categoría, avatares): en `storage/app/public/` (`product-colors/`, `categorias/`, `avatars/`), **no van en Git**; son datos, como la BD. Viajan con la base de datos, no con el repositorio.
- Para la URL usa `App\Support\PublicFile::url($path)` (devuelve `/storage/...` relativo). **No uses `Storage::url()`**: arma la dirección con `APP_URL` y si no coincide con el dominio real (ej. `localhost` vs `.test`) la foto no carga.
- Para guardar usa `PublicFile::storeNamed($file, 'carpeta', $modelo, $color)` → nombres legibles como `airforce-one-blanco-k3f9a2.jpg`, no el hash de Laravel.
- Al reemplazar o borrar, elimina el archivo subido anterior; nunca borres nada de `public/image/`.

### Tienda: datos honestos

- Nada de descuentos, precios "antes", ratings ni insignias inventadas (ya se quitaron de `ProductCard`): es publicidad engañosa ante INDECOPI. "Nuevo" se calcula con la fecha real de alta (`isNew`, últimos 30 días).
- No prometer envíos, cambios ni plazos que no estén en una política escrita en la web.
- El negocio está en **San Ramón, Chanchamayo (Junín, Perú)**, no en Lima. Nombre, RUC, teléfono, WhatsApp y dirección reales están en `BusinessSetting` (admin → Datos del negocio) y se comparten como `page.props.business`; úsalos en vez de textos fijos. Las páginas legales aún tienen datos de relleno (correos `@gmail.com`, `RUC_DEL_RESPONSABLE`).

### Vitrina del Home (categorías)

- El Home muestra solo las categorías `featured` (admin → Categorías y marcas → "Vitrina del Home", con interruptor y foto de portada). Las categorías nuevas entran **ocultas** para no romper la composición; si hay más activas que destacadas aparece "Ver todas las categorías".
- Las categorías sin productos salen como "Próximamente" y sin enlace. Las 5 categorías base (con imágenes en `public/image/shop/`) las crea `CategorySeeder`, que se puede correr varias veces sin duplicar ni pisar cambios del admin.

### Animación

- Usa los tokens de `resources/css/app.css`: `--ease-out` (entradas/salidas), `--ease-in-out` (revelados, movimiento en pantalla), `--ease-drawer`. No `ease-in` ni `transition: all`.
- Solo `transform`, `opacity` y `clip-path`. Respeta `prefers-reduced-motion` y limita los hover a `@media (hover: hover) and (pointer: fine)`.
- Movimiento solo con propósito (ver skills de Emil Kowalski en `.claude/skills`, ej. `emil-design-eng`, `animate`). Nada de cintas/marquesinas decorativas: se descartaron por genéricas.

### Configurar una máquina nueva

```bash
composer install && npm install
php artisan migrate
php artisan db:seed --class=CategorySeeder   # categorías base + vitrina
php artisan storage:link                     # sin esto no se ven las fotos subidas
npm run build
```

Los skills de `.claude/skills/` son copias reales (no accesos directos) para que funcionen al clonar; si se actualizan con `npx skills update` hay que volver a reemplazar los accesos por copias antes de subir.

### Working with Inertia.js

When passing data from controller to Vue component:

```php
// Controller
return Inertia::render('Shop/ProductDetail', [
    'product' => $product,
    'relatedProducts' => $related,
]);

// Vue component
<script setup>
defineProps({
  product: Object,
  relatedProducts: Array,
});
</script>
```

Use `ziggy` for named routes in frontend:
```js
route('shop.tienda')  // /tienda
route('admin.products.show', id)  // /admin/products/{id}
```

### Validation

Use Laravel form requests for validation:

```php
// app/Http/Requests/StoreProductRequest.php
public function rules()
{
    return [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
    ];
}

// Controller
public function store(StoreProductRequest $request)
{
    Product::create($request->validated());
}
```

### Database Relationships

Common relationships in models:

```php
// Product has many categories
public function categories() { return $this->hasMany(Category::class); }

// Order belongs to User
public function user() { return $this->belongsTo(User::class); }

// Order has many items
public function items() { return $this->hasMany(OrderItem::class); }
```

## Common Workflows

### Adding a New Page

1. Create controller action:
```php
public function show($id)
{
    $product = Product::findOrFail($id);
    return Inertia::render('Shop/ProductDetail', ['product' => $product]);
}
```

2. Add route in `routes/web.php`:
```php
Route::get('/producto/{id}', [ProductController::class, 'show'])->name('producto');
```

3. Create Vue component in `resources/js/Pages/Shop/ProductDetail.vue`:
```vue
<script setup>
defineProps({ product: Object });
</script>

<template>
  <div>{{ product.name }}</div>
</template>
```

### Updating a Model's Data

1. Modify the migration if needed (create new migration for changes)
2. Update model relationships if required
3. Update controller to use new fields
4. Update Vue component to display/edit new fields

## Notes

- Routes in `routes/web.php` use Inertia to render Vue components - they're not traditional HTTP routes returning HTML strings
- Cart state is managed in `resources/js/cart.js` - persists across page navigations
- The admin panel requires authentication via `['auth', 'verified']` middleware
- Use `php artisan make:migration` for any database schema changes
- Run `npm run dev` during development to see real-time Vue changes
- Keep business logic in controllers, presentational logic in Vue components
- Database models should reflect business entities; use accessors/mutators for computed properties

## Useful Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Vue 3 Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Laravel Eloquent ORM](https://laravel.com/docs/eloquent)
