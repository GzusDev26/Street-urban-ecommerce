<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import ArticleLayout from '@/Layouts/Shop/ArticleLayout.vue';
</script>

<template>
  <Head title="Términos y condiciones" />

  <ShopLayout>
    <div class="pt-20"></div>

    <!-- Título de la página -->
    <section class="relative py-16 px-4 md:px-8 lg:px-16 overflow-hidden">
      <!-- Background Gradient -->
      <div class="absolute inset-0 bg-gradient-to-b from-fg/5 via-transparent to-transparent"></div>
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-fg/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-fg/5 rounded-full blur-3xl"></div>

      <div class="relative max-w-full mx-auto">
        <div>
          <!-- Título Principal -->
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-tight italic leading-tight mb-4 max-w-4xl">
            Términos <span class="text-transparent bg-clip-text bg-gradient-to-r from-fg to-gray-500">y condiciones</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-fg via-fg to-gray-600 rounded-full"></div>
        </div>
      </div>
    </section>

    <ArticleLayout>
      <p><strong>¡Gracias por visitar Street Urban!</strong></p>
      
      <p>Los presentes <strong><em>Términos y condiciones</em></strong> regulan el acceso, navegación y uso de la tienda virtual <strong>STREET URBAN,</strong> así como las condiciones aplicables a la compra de los productos ofrecidos a través de la plataforma.</p>
      
      <p>Al acceder a la tienda o utilizar sus funcionalidades, aceptas estos <strong><em>Términos y condiciones</em></strong>. Si no estás de acuerdo con alguna de sus disposiciones, te recomendamos no utilizar la plataforma.</p>

      <h2>1. Identificación del titular</h2>

      <p>La titularidad de la presente plataforma virtual corresponde a la tienda <strong>STREET URBAN,</strong> de San Ramón, Chanchamayo (Junín, Perú). Nuestra dirección de contacto es <code>contacto@gmail.com</code>.</p>

      <h2>2. Uso de la tienda</h2>

      <h3>2.1. Condición de usuario</h3>

      <p>El acceso y uso de la tienda virtual atribuye la condición de <strong>USUARIO</strong> a la persona que navega o utiliza sus funcionalidades.</p>

      <p>El <strong>USUARIO</strong> se compromete a utilizar la plataforma de forma lícita, responsable y conforme a los presentes <strong><em>Términos y condiciones.</em></strong></p>

      <h3>2.2. Edad mínima</h3>

      <p>Para realizar compras a través de <strong>STREET URBAN</strong> deberás tener al menos dieciocho (18) años.</p>

      <p>Al utilizar el proceso de compra, declaras que cumples con este requisito y que la información proporcionada es válida, exacta y actualizada.</p>

      <h3>2.3. Uso prohibido</h3>

      <p>El <strong>USUARIO</strong> se compromete a no utilizar la tienda para:</p>

      <ol>
        <li>Realizar actividades contrarias a la legislación vigente.</li>
        <li>Intentar acceder sin autorización a sistemas, cuentas o información de <strong>STREET URBAN</strong> o de terceros.</li>
        <li>Introducir virus, código malicioso o cualquier otro elemento que pueda afectar el funcionamiento de la plataforma.</li>
        <li>Utilizar datos falsos o suplantar la identidad de otra persona.</li>
        <li>Interferir con el funcionamiento normal de la tienda o manipular indebidamente sus procesos de compra, inventario o pago.</li>
      </ol>

      <p>El tratamiento de los datos personales proporcionados por el <strong>USUARIO</strong> se regirá por la correspondiente <Link :href="route('shop.privacidad')">Política de privacidad.</Link></p>

      <h2>3. Productos y disponibilidad</h2>

      <p><strong>STREET URBAN</strong> ofrece prendas de vestir y calzado a través de su tienda virtual.</p>

      <p>La información de los productos, incluyendo su nombre, descripción, imágenes y precio, se muestra con la finalidad de proporcionar al <strong>USUARIO</strong> información sobre los productos ofrecidos.</p>

      <p><strong>STREET URBAN</strong> procurará que la información publicada sea correcta y se encuentre actualizada.</p>

      <p>Las imágenes de los productos buscan representar los artículos ofrecidos. No obstante, pueden existir pequeñas variaciones derivadas de las condiciones de visualización de la pantalla o de las características propias del producto.</p>

      <p>La disponibilidad de los productos se encuentra sujeta al stock existente. Cuando un producto se encuentre agotado, <strong>STREET URBAN</strong> podrá indicarlo en la plataforma y limitar o impedir su compra.</p>

      <p>En caso de detectarse una discrepancia entre la disponibilidad mostrada en la plataforma y el inventario real, <strong>STREET URBAN</strong> informará al <strong>USUARIO</strong> y podrá detener el proceso de compra correspondiente antes de completar el pago.</p>

      <h2>4. Precios</h2>

      <p>Los precios de los productos serán los indicados en la tienda virtual al momento de realizar la compra.</p>

      <p><strong>STREET URBAN</strong> se reserva el derecho de actualizar los precios de los productos publicados en la plataforma. Estas modificaciones no afectarán a las compras cuyo proceso haya sido correctamente confirmado y pagado, salvo que se trate de un error manifiesto que deba ser corregido conforme a la normativa aplicable.</p>

      <h2>5. Proceso de compra</h2>

      <p>El proceso de compra en <strong>STREET URBAN</strong> comprende, de forma general, las siguientes etapas:</p>

      <ol>
        <li>Selección de los productos y adición al carrito.</li>
        <li>Registro de los datos requeridos para la compra.</li>
        <li>Revisión y validación de la información proporcionada.</li>
        <li>Realización del pago mediante los medios disponibles en la plataforma.</li>
        <li>Emisión del comprobante de pago correspondiente.</li>
        <li>Confirmación de la compra y comunicación de los siguientes pasos para el recojo del producto.</li>
      </ol>

      <p>El <strong>USUARIO</strong> deberá proporcionar información válida, exacta y actualizada durante el proceso de compra.</p>

      <p>La revisión de la información antes de confirmar la compra es responsabilidad del <strong>USUARIO.</strong></p>

      <p><strong>STREET URBAN</strong> podrá solicitar la corrección de información incompleta o inconsistente antes de continuar con el proceso de compra.</p>

      <h2>6. Pagos</h2>

      <p>Los pagos se realizarán mediante los medios habilitados en la tienda virtual. El procesamiento de las operaciones de pago puede involucrar a proveedores externos especializados en servicios de pago.</p>

      <p><strong>STREET URBAN</strong> no almacena directamente los datos completos de las tarjetas bancarias del <strong>USUARIO,</strong> salvo que ello corresponda a una funcionalidad expresamente informada y habilitada conforme a la normativa aplicable y a las condiciones del proveedor de pago correspondiente.</p>

      <p>Si una operación de pago es rechazada, cancelada o presenta un error, la compra no se considerará correctamente completada hasta que el pago haya sido confirmado por el sistema correspondiente.</p>

      <h2>7. Comprobantes de pago</h2>

      <p><strong>STREET URBAN</strong> emitirá el comprobante de pago que corresponda de acuerdo con la información proporcionada por el <strong>USUARIO</strong> y la normativa tributaria aplicable. El <strong>USUARIO</strong> deberá proporcionar correctamente los datos requeridos para la emisión del comprobante.</p>

      <h2>8. Recojo de productos</h2>

      <p>Las compras realizadas a través de la tienda virtual estarán sujetas a la modalidad de recojo de productos establecida por <strong>STREET URBAN.</strong></p>

      <p>Una vez confirmada la compra, <strong>STREET URBAN</strong> informará al <strong>USUARIO</strong> los pasos y condiciones aplicables para el recojo del producto.</p>

      <h2>9. Cambios y devoluciones</h2>

      <p>Los cambios y devoluciones de productos se regirán por la <strong><em>Política de cambios y devoluciones</em></strong> correspondiente, que establecerá los supuestos, requisitos y condiciones aplicables. El <strong>USUARIO</strong> deberá revisarla antes de realizar una compra.</p>

      <p><strong>STREET URBAN</strong> atenderá las solicitudes relacionadas con cambios, devoluciones y productos adquiridos conforme a la política vigente y a la normativa aplicable.</p>

      <h2>10. Propiedad intelectual</h2>

      <p>Los elementos que forman parte de la tienda virtual, incluyendo textos, logotipos, diseños, interfaces, gráficos y demás contenidos propios de <strong>STREET URBAN</strong> se encuentran protegidos por la normativa aplicable en materia de propiedad intelectual.</p>

      <p>El uso de fotografías, imágenes u otros contenidos de terceros se realizará conforme a las autorizaciones, licencias o condiciones de uso que correspondan.</p>

      <p>El <strong>USUARIO</strong> no podrá reproducir, distribuir, modificar o utilizar los contenidos de la plataforma con fines comerciales sin la autorización correspondiente.</p>

      <h2>11. Disponibilidad y modificaciones de la tienda</h2>

      <p><strong>STREET URBAN</strong> podrá modificar, actualizar o retirar contenidos, productos o funcionalidades de la tienda virtual cuando resulte necesario. Asimismo, podrá realizar labores de mantenimiento, actualización o mejora de la plataforma.</p>

      <p><strong>STREET URBAN</strong> procurará mantener la disponibilidad de la tienda, pero no garantiza que el acceso sea permanente o completamente libre de errores o interrupciones.</p>

      <h2>12. Limitación de responsabilidad</h2>

      <p><strong>STREET URBAN</strong> adoptará medidas razonables para mantener el correcto funcionamiento de la tienda virtual. No obstante, no será responsable por interrupciones o fallos ocasionados por circunstancias ajenas a su control razonable, incluyendo fallos de proveedores externos, servicios de pago, redes de comunicación, servicios de internet o situaciones de fuerza mayor.</p>

      <p>Lo anterior se entiende sin perjuicio de las responsabilidades y derechos que correspondan conforme a la normativa aplicable en materia de protección y defensa del consumidor.</p>

      <h2>13. Libro de reclamaciones y contacto</h2>

      <p>Si tienes alguna consulta sobre estos <strong><em>Términos y condiciones,</em></strong> puedes comunicarte con <strong>STREET URBAN</strong> a través del correo electrónico: <code>contacto@gmail.com</code></p>

      <p>Para presentar una queja o reclamo, puedes acceder al <Link :href="route('shop.reclamaciones')">Libro de reclamaciones virtual</Link>. <strong>STREET URBAN</strong> atenderá los reclamos y quejas conforme a la normativa aplicable.</p>

      <h2>14. Modificación de los términos y condiciones</h2>

      <p><strong>STREET URBAN</strong> podrá modificar o actualizar los presentes <strong><em>Términos y condiciones</em></strong> cuando resulte necesario.</p>

      <p>La versión vigente será la publicada en la tienda virtual.</p>

      <h2>15. Legislación aplicable</h2>

      <p>Los presentes <strong><em>Términos y condiciones</em></strong> se rigen por las leyes de la República del Perú. Las relaciones de consumo derivadas del uso de la tienda virtual y de la compra de productos se regirán por la normativa peruana aplicable en materia de protección y defensa del consumidor.</p>

      <p>Cualquier controversia será atendida conforme a los mecanismos y autoridades competentes establecidos por la legislación vigente.</p>
    </ArticleLayout>
  </ShopLayout>
</template>
