<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\DataRegisterController;
use App\Http\Controllers\Shop\CheckoutValidateController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\ComplaintController;
use App\Http\Controllers\Shop\AboutController;
use App\Http\Controllers\Shop\WishlistController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CustomerAdminController;
use App\Http\Controllers\Admin\SupplierAdminController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\BusinessSettingController;
use App\Http\Controllers\Admin\CategoryBrandController;
use App\Http\Controllers\Admin\AvatarAdminController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Api\CartController as ApiCartController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ActivateAccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

// Rutas de administración
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/customers/consultar-documento', [CustomerAdminController::class, 'consultarDocumento'])->name('admin.customers.consultar-documento');
    Route::resource('customers', CustomerAdminController::class, ['as' => 'admin']);
    Route::get('/customers/{customer}/historial', [CustomerAdminController::class, 'historial'])->name('admin.customers.historial');

    Route::resource('suppliers', SupplierAdminController::class, ['as' => 'admin']);
    Route::get('/suppliers/{supplier}/historial', [SupplierAdminController::class, 'historial'])->name('admin.suppliers.historial');

    Route::get('/compras', [PurchaseController::class, 'index'])->name('admin.purchases.index');
    Route::post('/compras', [PurchaseController::class, 'store'])->name('admin.purchases.store');
    Route::get('/compras/buscar-productos', [PurchaseController::class, 'searchProducts'])->name('admin.purchases.search');
    Route::get('/compras/variantes/{variant}/lotes', [PurchaseController::class, 'lotes'])->name('admin.purchases.lotes');
    Route::get('/compras/historial', [PurchaseController::class, 'historial'])->name('admin.purchases.historial');
    Route::get('/compras/reportes', [PurchaseController::class, 'reportes'])->name('admin.purchases.reportes');
    Route::patch('/compras/{compra}/cancelar', [PurchaseController::class, 'cancel'])->name('admin.purchases.cancel');

    Route::get('/ventas', [SaleController::class, 'index'])->name('admin.sales.index');
    Route::get('/ventas/registrar', [SaleController::class, 'create'])->name('admin.sales.create');
    Route::post('/ventas', [SaleController::class, 'store'])->name('admin.sales.store');
    Route::get('/ventas/buscar-variantes', [SaleController::class, 'searchVariants'])->name('admin.sales.search');
    Route::get('/ventas/buscar-clientes', [SaleController::class, 'searchClientes'])->name('admin.sales.search-clientes');
    Route::get('/ventas/buscar-documento', [SaleController::class, 'buscarDocumento'])->name('admin.sales.buscar-documento');
    Route::get('/ventas/reportes', [SaleController::class, 'reportes'])->name('admin.sales.reportes');
    Route::get('/ventas/{venta}/boleta', [SaleController::class, 'boleta'])->name('admin.sales.boleta');
    Route::get('/ventas/{venta}/boleta/datos', [SaleController::class, 'boletaDatos'])->name('admin.sales.boleta.datos');
    Route::patch('/ventas/{venta}/cancelar', [SaleController::class, 'cancel'])->name('admin.sales.cancel');
    Route::post('/ventas/items/{item}/devolver', [SaleController::class, 'returnItem'])->name('admin.sales.items.return');

    // La gestión del catálogo y sus variantes es exclusiva de administradores.
    Route::middleware(['admin.only'])->group(function () {
        Route::get('/catalogo', [CatalogoController::class, 'index'])->name('admin.catalogo.index');
        Route::post('/catalogo', [CatalogoController::class, 'store'])->name('admin.catalogo.store');
        Route::post('/catalogo/{producto}/tallas', [CatalogoController::class, 'storeTallas'])->name('admin.catalogo.tallas.store');
        Route::get('/catalogo/{producto}/colores', [CatalogoController::class, 'colores'])->name('admin.catalogo.colores.index');
        Route::post('/catalogo/{producto}/colores', [CatalogoController::class, 'storeColor'])->name('admin.catalogo.colores.store');
        Route::post('/catalogo/{producto}/colores/varios', [CatalogoController::class, 'storeColores'])->name('admin.catalogo.colores.store-many');
        Route::post('/catalogo/colores/{color}', [CatalogoController::class, 'updateColor'])->name('admin.catalogo.colores.update');
        Route::post('/catalogo/colores/{color}/foto', [CatalogoController::class, 'updateColorPhoto'])->name('admin.catalogo.colores.foto');
        Route::delete('/catalogo/colores/{color}', [CatalogoController::class, 'destroyColor'])->name('admin.catalogo.colores.destroy');
        Route::patch('/catalogo/{producto}', [CatalogoController::class, 'update'])->name('admin.catalogo.update');
        Route::patch('/catalogo/{producto}/restore', [CatalogoController::class, 'restore'])->name('admin.catalogo.restore');
        Route::patch('/catalogo/{producto}/deactivate', [CatalogoController::class, 'deactivate'])->name('admin.catalogo.deactivate');
        // Compatibilidad con clientes antiguos: DELETE ya no borra el registro ni su historial.
        Route::delete('/catalogo/{producto}', [CatalogoController::class, 'destroy'])->name('admin.catalogo.destroy');

        $mensajeProductosMovidos = 'La gestión de productos ahora se realiza en el catálogo.';
        $rutaProducto = '/products/{product}';

        // URLs heredadas de lectura llevan al catálogo canónico. Se preservan los nombres
        // de ruta y se responde 410 a escrituras para no fingir que el cambio fue aplicado.
        Route::get('/products', fn () => redirect()->route('admin.purchases.index'))->name('admin.products.index');
        Route::get('/products/create', fn () => redirect()->route('admin.purchases.index'))->name('admin.products.create');
        Route::post('/products', fn () => abort(410, $mensajeProductosMovidos))->name('admin.products.store');
        Route::get($rutaProducto, fn () => redirect()->route('admin.purchases.index'))->name('admin.products.show');
        Route::get('/products/{product}/edit', fn () => redirect()->route('admin.purchases.index'))->name('admin.products.edit');
        Route::match(['put', 'patch'], $rutaProducto, fn () => abort(410, $mensajeProductosMovidos))->name('admin.products.update');
        Route::delete($rutaProducto, fn () => abort(410, $mensajeProductosMovidos))->name('admin.products.destroy');
    });

    // Gestión de usuarios/roles y datos del negocio — solo un administrador puede entrar aquí.
    Route::middleware(['admin.only'])->group(function () {
        Route::patch('/users/{user}/active', [UserAdminController::class, 'toggleActive'])->name('admin.users.toggle-active');
        Route::resource('users', UserAdminController::class, ['as' => 'admin']);
        Route::get('/settings', [BusinessSettingController::class, 'edit'])->name('admin.settings.edit');
        Route::patch('/settings', [BusinessSettingController::class, 'update'])->name('admin.settings.update');

        Route::get('/categorias-marcas', [CategoryBrandController::class, 'index'])->name('admin.taxonomies.index');
        Route::post('/categorias', [CategoryBrandController::class, 'storeCategory'])->name('admin.categories.store');
        Route::post('/categorias/rapida', [CategoryBrandController::class, 'quickStoreCategory'])->name('admin.categories.quick-store');
        Route::post('/categorias/{category}/marcas', [CategoryBrandController::class, 'attachBrand'])->name('admin.categories.brands.attach');
        Route::delete('/categorias/{category}/marcas/{brand}', [CategoryBrandController::class, 'detachBrand'])->name('admin.categories.brands.detach');
        Route::patch('/categorias/{category}', [CategoryBrandController::class, 'updateCategory'])->name('admin.categories.update');
        Route::delete('/categorias/{category}', [CategoryBrandController::class, 'destroyCategory'])->name('admin.categories.destroy');
        Route::post('/marcas', [CategoryBrandController::class, 'storeBrand'])->name('admin.brands.store');
        Route::patch('/marcas/{brand}', [CategoryBrandController::class, 'updateBrand'])->name('admin.brands.update');
        Route::delete('/marcas/{brand}', [CategoryBrandController::class, 'destroyBrand'])->name('admin.brands.destroy');

        Route::get('/avatares', [AvatarAdminController::class, 'index'])->name('admin.avatars.index');
        Route::post('/avatares', [AvatarAdminController::class, 'store'])->name('admin.avatars.store');
        Route::delete('/avatares/{avatar}', [AvatarAdminController::class, 'destroy'])->name('admin.avatars.destroy');
    });
});

// Rutas de la tienda
Route::name('shop.')->group(function () {
  // Páginas principales
  Route::get('/', [ProductController::class, 'index'])->name('home');
  Route::get('/tienda', [ProductController::class, 'shop'])->name('tienda');
  Route::get('/products/{producto}', [ProductController::class, 'show'])->name('producto');
  Route::get('/contacto', [ContactController::class, 'index'])->name('contacto');
  Route::get('/nosotros', [AboutController::class, 'index'])->name('nosotros');

  // Carrito y checkout
  Route::get('/carrito', [CartController::class, 'index'])->name('carrito');
  Route::get('/wishlist', [WishlistController::class, 'index'])->middleware('auth:web,customer')->name('wishlist');
  Route::get('/api/wishlist', [WishlistController::class, 'ids'])->name('wishlist.ids');
  Route::post('/api/wishlist/{color}/toggle', [WishlistController::class, 'toggle'])->middleware('auth:web,customer')->name('wishlist.toggle');
  Route::get('/registro-datos', [DataRegisterController::class, 'index'])->name('registrodatos');
  Route::post('/validar-registro', [DataRegisterController::class, 'validateRegisterForm'])->name('validateRegisterForm');
  Route::get('/confirmar-pedido', [CheckoutValidateController::class, 'index'])->name('confirmarpedido');

  // Páginas legales y atención
  Route::get('/terminos-y-condiciones', function () {
    return Inertia::render('Shop/Legal/Terminos');
  })->name('terminos');
  Route::get('/politica-de-privacidad', function () {
    return Inertia::render('Shop/Legal/Privacidad');
  })->name('privacidad');
  Route::get('/cambios-y-devoluciones', function () {
    return Inertia::render('Shop/Legal/CambiosDevoluciones');
  })->name('cambios');
  Route::get('/libro-de-reclamaciones', [ComplaintController::class, 'index'])->name('reclamaciones');
  Route::post('/libro-de-reclamaciones', [ComplaintController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('reclamaciones.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
});

// Carrito: la validación es pública para visitantes; la persistencia usa la sesión del cliente.
Route::prefix('api/cart')->name('api.cart.')->group(function () {
    Route::post('/validate', [ApiCartController::class, 'validateCart'])->name('validate');
    Route::post('/confirm', [ApiCartController::class, 'confirm'])->name('confirm');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [ApiCartController::class, 'show'])->name('show');
        Route::post('/sync', [ApiCartController::class, 'sync'])->name('sync');
        Route::post('/items', [ApiCartController::class, 'store'])->name('items.store');
        Route::patch('/items/{variant}', [ApiCartController::class, 'update'])->name('items.update');
        Route::delete('/items/{variant}', [ApiCartController::class, 'destroy'])->name('items.destroy');
    });
});

// Rutas de autenticación (públicas)
Route::get('/login', fn() => redirect('/') )->name('login');
Route::post('/api/auth/check-email', [VerificationController::class, 'checkEmail']);
Route::post('/api/auth/send-verification-email', [VerificationController::class, 'sendVerificationEmail']);
Route::post('/auth/login-action', [LoginController::class, 'loginAction'])->name('auth.login-action');
Route::post('/api/auth/login', [LoginController::class, 'login']);
Route::post('/auth/logout', [LogoutController::class, 'logout'])->name('auth.logout');

// Rutas de activación de cuenta (públicas)
Route::get('/activate-account', [ActivateAccountController::class, 'show'])->name('activate-account.show');
Route::post('/api/auth/activate-account', [ActivateAccountController::class, 'store'])->name('activate-account.store');

// Rutas sociales
Route::get('/instagram', function () {
    return redirect()->away('https://instagram.com/');
})->name('social.instagram');

Route::get('/x-twitter', function () {
    return redirect()->away('https://x.com/');
})->name('social.twitter');

Route::get('/facebook', function () {
    return redirect()->away('https://facebook.com/');
})->name('social.facebook');

require __DIR__.'/auth.php';
