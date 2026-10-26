<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import ArticleLayout from '@/Layouts/Shop/ArticleLayout.vue';
</script>

<template>
  <Head title="Política de privacidad" />

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
            Política <span class="text-transparent bg-clip-text bg-gradient-to-r from-fg to-gray-500">de privacidad</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-fg via-fg to-gray-600 rounded-full"></div>
        </div>
      </div>
    </section>

    <ArticleLayout>
      <h2>Resumen</h2>

      <p>En <strong>STREET URBAN</strong> respetamos tu privacidad y nos comprometemos a proteger los datos personales que nos confías.</p>

      <p>En pocas palabras:</p>

      <ul>
        <li>Utilizamos tus datos para atender consultas, gestionar tus compras, emitir comprobantes de pago, coordinar el recojo de tus productos y cumplir las obligaciones legales aplicables.</li>
        <li>No es necesario crear una cuenta de usuario para realizar compras en nuestra tienda virtual.</li>
        <li>Los pagos electrónicos son procesados por la pasarela de pago que implemente <strong>STREET URBAN</strong>. En ningún momento almacenamos ni tenemos acceso a los datos completos de tu tarjeta bancaria u otro medio de pago equivalente.</li>
        <li>Los datos introducidos durante el proceso de compra solo permanecen temporalmente almacenados en tu navegador mientras completas el proceso de compra. Una vez finalizado correctamente, dicha información temporal es eliminada.</li>
        <li>Actualmente no utilizamos tus datos personales para enviar publicidad o comunicaciones comerciales.</li>
      </ul>

      <p>Si deseas conocer con mayor detalle cómo tratamos tu información, te invitamos a leer la presente <strong><em>Política de privacidad.</em></strong></p>

      <hr />

      <p>La presente <strong><em>Política de privacidad</em></strong> describe la forma en que <strong>STREET URBAN</strong> recopila, utiliza, conserva y protege los datos personales obtenidos a través de su tienda virtual.</p>

      <p>El tratamiento de los datos personales se realiza de conformidad con lo dispuesto por la Ley N.º 29733, Ley de Protección de Datos Personales, y su reglamento aprobado mediante el Decreto Supremo N.º 016-2024-JUS, así como con las demás normas peruanas que resulten aplicables.</p>

      <p>Esta política es aplicable únicamente a la información obtenida a través del sitio web de <strong>STREET URBAN</strong> y no comprende los tratamientos realizados por terceros a través de páginas o servicios externos enlazados desde nuestro sitio.</p>

      <h2>1. Responsable del tratamiento</h2>

      <p>El responsable del tratamiento de los datos personales es la tienda <strong>STREET URBAN,</strong> de San Ramón, Chanchamayo (Junín, Perú). Nuestra dirección de contacto general es <code>contacto@gmail.com</code>; para consultas relacionadas con datos personales, el correo es <code>datos@gmail.com</code></p>
      
      <h2>2. ¿Qué datos personales tratamos?</h2>

      <p>Dependiendo de la interacción que tengas con nuestra tienda virtual, <strong>STREET URBAN</strong> podrá tratar distintas categorías de datos personales.</p>

      <h3>2.1. Datos proporcionados mediante el formulario de contacto</h3>

      <p>Cuando utilizas el formulario de contacto podremos tratar:</p>

      <ul>
        <li>nombre;</li>
        <li>dirección de correo electrónico; y</li>
        <li>contenido del mensaje enviado.</li>
      </ul>

      <p>Estos datos se utilizan exclusivamente para atender la consulta realizada.</p>

      <h3>2.2. Datos proporcionados durante el proceso de compra</h3>

      <p>Cuando realizas una compra podremos solicitar los siguientes datos:</p>

      <ul>
        <li>tipo de documento de identidad;</li>
        <li>número de documento;</li>
        <li>nombre completo o razón social;</li>
        <li>dirección de correo electrónico; y</li>
        <li>número telefónico.</li>
      </ul>

      <p>Esta información resulta necesaria para identificar al comprador, emitir el comprobante de pago correspondiente, coordinar el recojo del pedido y cumplir las obligaciones legales aplicables.</p>

      <h3>2.3. Datos relacionados con la operación de pago</h3>

      <p>El procesamiento de los pagos electrónicos será realizado directamente por la pasarela de pago que <strong>STREET URBAN</strong> implemente. En consecuencia:</p>

      <ul>
        <li><strong>STREET URBAN</strong> no almacena números completos de tarjetas bancarias ni otros datos sensibles de los medios de pago utilizados.</li>
        <li><strong>STREET URBAN</strong> únicamente podrá recibir la información necesaria para confirmar el resultado de la operación, como identificadores de transacción, estado del pago u otros datos técnicos proporcionados por la pasarela correspondiente.</li>
      </ul>
      
      <h3>2.4. Datos del libro de reclamaciones</h3>

      <p>Cuando presentes una queja o un reclamo mediante nuestro <Link :href="route('shop.reclamaciones')">Libro de reclamaciones virtual</Link>, trataremos los datos personales exigidos por la normativa peruana con la única finalidad de gestionar y responder adecuadamente tu solicitud.</p>

      <h3>2.5. Datos técnicos</h3>

      <p>Durante la navegación podrán recopilarse determinados datos técnicos indispensables para el funcionamiento de la tienda virtual, tales como:</p>

      <ul>
        <li>dirección IP;</li>
        <li>tipo de navegador;</li>
        <li>sistema operativo;</li>
        <li>identificadores técnicos de sesión;</li>
        <li>registros de acceso; y</li>
        <li>otra información técnica necesaria para garantizar el funcionamiento y la seguridad del servicio.</li>
      </ul>

      <h2>3. ¿Para qué utilizamos tus datos personales?</h2>

      <p><strong>STREET URBAN</strong> trata los datos personales únicamente para finalidades legítimas relacionadas con el funcionamiento de la tienda virtual. En particular, utilizamos la información para:</p>

      <ul>
        <li>atender consultas realizadas mediante el formulario de contacto;</li>
        <li>gestionar el proceso de compra de productos;</li>
        <li>identificar al comprador cuando resulte necesario;</li>
        <li>emitir comprobantes de pago conforme a la normativa tributaria aplicable;</li>
        <li>coordinar el recojo de los productos adquiridos;</li>
        <li>responder consultas relacionadas con pedidos ya realizados;</li>
        <li>atender quejas y reclamos presentados mediante el <Link :href="route('shop.reclamaciones')">Libro de reclamaciones virtual</Link>;</li>
        <li>responder solicitudes relacionadas con el ejercicio de los derechos reconocidos por la normativa sobre protección de datos personales;</li>
        <li>prevenir fraudes, abusos o usos indebidos de la plataforma; y</li>
        <li>cumplir obligaciones legales o requerimientos emitidos por autoridades competentes.</li>
      </ul>

      <p>Por el momento, <strong>STREET URBAN</strong> no utiliza los datos personales de sus clientes para realizar campañas publicitarias o desarrollar actividades de mercadotecnia directa. En caso de que dichos servicios sean implementados en el futuro, se informará oportunamente a los usuarios y, cuando corresponda, se solicitará el consentimiento exigido por la legislación vigente.</p>

      <h2>4. Base legal para el tratamiento de los datos personales</h2>

      <p><strong>STREET URBAN</strong> trata los datos personales únicamente cuando existe una base jurídica que lo permita, conforme a la ley N.º 29733 y su reglamento.</p>

      <p>Dependiendo de la finalidad del tratamiento, la base legal podrá ser una o varias de las siguientes:</p>

      <ul>
        <li>el consentimiento otorgado por el titular de los datos;</li>
        <li>la ejecución de la relación contractual derivada de la compra de productos;</li>
        <li>el cumplimiento de obligaciones legales aplicables a <strong>STREET URBAN;</strong> y</li>
        <li>el ejercicio legítimo de derechos relacionados con la atención de consultas, reclamos, prevención del fraude o defensa frente a posibles controversias.</li>
      </ul>

      <p>Cuando el tratamiento se base en el consentimiento, este podrá ser revocado en cualquier momento, sin afectar la licitud del tratamiento realizado con anterioridad.</p>

      <h2>5. Conservación de los datos personales</h2>

      <p><strong>STREET URBAN</strong> conserva los datos personales únicamente durante el tiempo necesario para cumplir las finalidades descritas en esta política o mientras exista una obligación legal que así lo exija.</p>

      <p>Con carácter general, se aplicarán los siguientes criterios de conservación:</p>

      <h3>5.1. Consultas realizadas mediante el formulario de contacto</h3>

      <p>Los datos proporcionados mediante el formulario de contacto podrán conservarse por un plazo máximo de un (1) año, contado desde la atención de la consulta, salvo que exista una obligación legal o una controversia que justifique un plazo mayor.</p>

      <h3>5.2. Datos relacionados con compras</h3>

      <p>Los datos necesarios para gestionar una compra podrán conservarse durante el tiempo que resulte necesario para:</p>

      <ul>
        <li>ejecutar el contrato celebrado con el cliente;</li>
        <li>emitir y conservar los comprobantes de pago;</li>
        <li>atender solicitudes posteriores relacionadas con la compra;</li>
        <li>cumplir obligaciones tributarias;</li>
        <li>cumplir obligaciones derivadas de la normativa de protección al consumidor; y</li>
        <li>atender requerimientos formulados por autoridades competentes.</li>
      </ul>

      <h3>5.3. Libro de reclamaciones</h3>

      <p>Los datos contenidos en el <Link :href="route('shop.reclamaciones')">Libro de reclamaciones virtual</Link> serán conservados durante el plazo establecido por la legislación peruana, siendo este actualmente de dos (2) años desde la presentación de la reclamación.</p>

      <h3>5.4. Compras no finalizadas</h3>

      <p>Cuando un usuario inicie un proceso de compra, pero este no llegue a completarse, <strong>STREET URBAN</strong> podrá conservar temporalmente determinados registros técnicos relacionados con dicha operación con fines de auditoría, prevención del fraude, diagnóstico de incidencias y mejora del servicio.</p>

      <h3>5.5. Eliminación de los datos</h3>

      <p>Una vez vencidos los plazos de conservación aplicables, <strong>STREET URBAN</strong> procederá a eliminar o anonimizar los datos personales, salvo que exista una obligación legal que exija su conservación por un período adicional.</p>

      <h2>6. ¿Cómo recopilamos los datos personales?</h2>

      <p><strong>STREET URBAN</strong> obtiene los datos personales directamente del propio usuario mediante los distintos servicios ofrecidos en la tienda virtual.</p>

      <p>Actualmente se emplean los siguientes mecanismos de recopilación.</p>

      <h3>6.1. Formulario de contacto</h3>

      <p>Cuando utilizas el formulario de contacto, recopilamos los datos que ingresas voluntariamente para responder tu consulta. Dicho formulario solicita:</p>

      <ul>
        <li>nombre;</li>
        <li>correo electrónico; y</li>
        <li>mensaje.</li>
      </ul>

      <p>Estos datos no serán utilizados para campañas publicitarias ni para el envío de comunicaciones comerciales.</p>

      <h3>6.2. Proceso de compra</h3>

      <p>Durante el proceso de compra recopilamos únicamente los datos necesarios para identificar al comprador y gestionar correctamente el pedido. El flujo general del proceso es el siguiente:</p>

      <ul>
        <li>selección de productos;</li>
        <li>revisión del carrito de compras;</li>
        <li>registro de los datos del comprador;</li>
        <li>confirmación de la información registrada;</li>
        <li>procesamiento del pago mediante la pasarela correspondiente; y</li>
        <li>emisión del comprobante y coordinación del recojo del producto.</li>
      </ul>

      <p><strong>STREET URBAN</strong> podrá solicitar únicamente la información estrictamente necesaria para completar estas etapas.</p>

      <h3>6.3. Almacenamiento temporal durante el proceso de compra</h3>

      <p>Con la finalidad de facilitar el proceso de compra, determinados datos ingresados por el usuario podrán almacenarse temporalmente en el navegador mediante mecanismos de almacenamiento local (<code>localStorage</code>).</p>
      
      <p>Esta información se utiliza exclusivamente para mantener la continuidad del proceso de compra entre las distintas etapas del <em>checkout.</em> Una vez que la compra finaliza correctamente, dichos datos temporales son eliminados del navegador.</p>

      <p>El almacenamiento local no constituye una base de datos permanente de <strong>STREET URBAN</strong>.</p>

      <h3>6.4. Libro de reclamaciones virtual</h3>

      <p>Cuando presentes una queja o reclamo mediante nuestro <Link :href="route('shop.reclamaciones')">Libro de reclamaciones virtual</Link>, recopilaremos solo los datos exigidos por la legislación peruana para tramitar correctamente tu solicitud.</p>

      <h3>6.5. Cookies</h3>

      <p>Actualmente la tienda virtual utiliza únicamente las cookies técnicas indispensables para el funcionamiento del sitio.</p>

      <h3>6.6. Herramientas de seguridad</h3>

      <p><strong>STREET URBAN</strong> podrá incorporar mecanismos automáticos destinados a prevenir el uso abusivo de la plataforma, incluyendo captchas u otras tecnologías equivalentes. Estas podrán tratar información técnica relacionada con la navegación exclusivamente para fines de seguridad informática, prevención del fraude y protección de nuestros servicios.</p>

      <h2>7. Destinatarios de los datos personales</h2>

      <p><strong>STREET URBAN</strong> trata los datos personales de forma confidencial y no los vende, alquila ni comercializa con terceros. No obstante, para el correcto funcionamiento de la tienda virtual podrá ser necesario comunicar determinada información a terceros en los siguientes casos:</p>

      <ul>
        <li>cuando resulte indispensable para ejecutar una compra;</li>
        <li>cuando exista una obligación legal;</li>
        <li>cuando sea requerido por una autoridad competente; o</li>
        <li>cuando intervengan proveedores tecnológicos que actúen por cuenta de <strong>STREET URBAN.</strong></li>
      </ul>

      <p>En todos los casos, solo se comunicarán los datos estrictamente necesarios para cumplir la finalidad correspondiente.</p>

      <h3>7.1. Proveedores tecnológicos</h3>

      <p><strong>STREET URBAN</strong> podrá contratar proveedores especializados para prestar servicios relacionados con el funcionamiento de la tienda virtual, entre ellos:</p>

      <ul>
        <li>alojamiento web;</li>
        <li>infraestructura en la nube;</li>
        <li>correo electrónico;</li>
        <li>almacenamiento y respaldo de información;</li>
        <li>pasarelas de pago;</li>
        <li>herramientas de seguridad informática;</li>
        <li>sistemas de prevención del fraude;</li>
        <li>analítica web; y</li>
        <li>otros servicios tecnológicos necesarios para la operación del sitio.</li>
      </ul>

      <p>Cuando dichos proveedores tengan acceso a datos personales, actuarán en calidad de encargados del tratamiento y deberán ofrecer garantías adecuadas para proteger la información conforme a la legislación aplicable.</p>

      <h3>7.2. Pasarelas de pago</h3>

      <p>Los pagos realizados a través de la tienda virtual serán procesados por la pasarela de pago que <strong>STREET URBAN</strong> implemente. La entidad responsable del procesamiento del pago podrá tratar la información necesaria para:</p>

      <ul>
        <li>autorizar la operación;</li>
        <li>verificar la identidad del titular del medio de pago;</li>
        <li>prevenir operaciones fraudulentas;</li>
        <li>ejecutar la transacción; y</li>
        <li>comunicar el resultado de la operación.</li>
      </ul>

      <p><strong>STREET URBAN</strong> no almacena los datos completos de tarjetas bancarias ni controla directamente el tratamiento realizado por la pasarela de pago, la cual actuará conforme a sus propias políticas de privacidad y condiciones de uso.</p>

      <h3>7.3. Servicios de mensajería electrónica</h3>

      <p>Con la finalidad de enviar confirmaciones de compra, comunicaciones relacionadas con pedidos u otros avisos necesarios para la prestación del servicio, <strong>STREET URBAN</strong> podrá utilizar proveedores especializados de correo electrónico. En caso de implementarse dichos servicios, solo se tratarán los datos necesarios para remitir las comunicaciones correspondientes.</p>

      <h3>7.4. Transferencias internacionales de datos</h3>

      <p>Algunos de los proveedores tecnológicos contratados por <strong>STREET URBAN</strong> podrían almacenar o procesar información en servidores ubicados fuera del territorio peruano. Cuando ello ocurra, <strong>STREET URBAN</strong> procurará contratar proveedores que ofrezcan garantías razonables para la protección de los datos personales y adoptará las medidas exigidas por la legislación vigente.</p>

      <h2>8. Seguridad de la información</h2>

      <p><strong>STREET URBAN</strong> adopta medidas técnicas y organizativas razonables para proteger los datos personales frente a pérdida, alteración, destrucción, acceso no autorizado o cualquier otra forma de tratamiento indebido. Entre otras medidas, podrán implementarse mecanismos destinados a:</p>

      <ul>
        <li>proteger las comunicaciones mediante protocolos seguros;</li>
        <li>restringir el acceso a la información únicamente al personal autorizado;</li>
        <li>mantener registros de acceso cuando resulte necesario;</li>
        <li>prevenir ataques automatizados contra la plataforma; y</li>
        <li>reducir el riesgo de accesos no autorizados.</li>
      </ul>

      <p>No obstante, ningún sistema informático conectado a Internet puede garantizar una seguridad absoluta. En consecuencia, <strong>STREET URBAN</strong> no puede asegurar que terceros no autorizados logren vulnerar las medidas de seguridad implementadas. Sin embargo, se compromete a adoptar las acciones razonables necesarias para prevenir este tipo de incidentes y responder oportunamente cuando estos ocurran.</p>

      <h2>9. Derechos de los titulares de los datos personales</h2>

      <p>Como titular de los datos personales, puedes ejercer en cualquier momento los derechos reconocidos por la legislación peruana sobre protección de datos personales. Entre ellos se encuentran:</p>

      <ul>
        <li><strong>Derecho de información,</strong> para conocer cómo se tratan tus datos personales.</li>
        <li><strong>Derecho de acceso,</strong> para solicitar una copia de la información que mantenemos sobre ti.</li>
        <li><strong>Derecho de rectificación,</strong> para corregir datos inexactos o incompletos.</li>
        <li><strong>Derecho de cancelación,</strong> para solicitar la eliminación de tus datos cuando resulte legalmente procedente.</li>
        <li><strong>Derecho de oposición,</strong> para oponerte al tratamiento de tus datos en los supuestos previstos por la normativa.</li>
      </ul>

      <p>El ejercicio de estos derechos será gratuito, salvo los casos expresamente previstos por la legislación aplicable.</p>

      <h3>9.1. ¿Cómo ejercer tus derechos?</h3>

      <p>Para ejercer cualquiera de los derechos antes mencionados, puedes enviar una solicitud a <code>datos@gmail.com</code>. En el asunto del mensaje deberás indicar <code>Solicitud de ejercicio de derechos ARCO</code>.</p>

      <p>La solicitud deberá contener, como mínimo:</p>

      <ul>
        <li>nombre completo;</li>
        <li>documento de identidad;</li>
        <li>derecho que deseas ejercer;</li>
        <li>descripción de tu solicitud; y</li>
        <li>cualquier información que permita identificar los datos sobre los cuales versa tu petición.</li>
      </ul>

      <p><strong>STREET URBAN</strong> podrá solicitar información adicional cuando resulte necesaria para verificar la identidad del solicitante y evitar accesos indebidos a información personal.</p>

      <h3>9.2. Limitaciones</h3>

      <p>El ejercicio de los derechos reconocidos por la normativa podrá verse limitado cuando exista una obligación legal que exija conservar determinada información. Por ejemplo, <strong>STREET URBAN</strong> podría no eliminar inmediatamente los datos asociados a una compra mientras sea necesario conservarlos para:</p>

      <ul>
        <li>emitir o conservar comprobantes de pago;</li>
        <li>cumplir obligaciones tributarias;</li>
        <li>atender reclamos relacionados con la compra;</li>
        <li>cumplir requerimientos de autoridades competentes; o</li>
        <li>ejercer o defender derechos derivados de la relación contractual.</li>
      </ul>

      <p>En tales casos, los datos permanecerán conservados únicamente durante el tiempo que resulte legalmente necesario.</p>

      <h2>10. Autoridad competente</h2>

      <p>Si consideras que <strong>STREET URBAN</strong> no ha atendido adecuadamente una solicitud relacionada con el tratamiento de tus datos personales o que se ha producido un incumplimiento de la normativa vigente, puedes comunicarte primero con nosotros para que podamos revisar tu caso y brindarte una solución.</p>

      <p>Sin perjuicio de ello, si consideras que tus derechos han sido vulnerados, podrás presentar la denuncia o reclamación correspondiente ante la Autoridad Nacional de Protección de Datos Personales, de conformidad con los procedimientos establecidos por la legislación peruana.</p>

      <h2>11. Modificaciones de la política de privacidad</h2>

      <p><strong>STREET URBAN</strong> podrá actualizar o modificar la presente <strong><em>Política de privacidad</em></strong> cuando resulte necesario, entre otros motivos, por:</p>

      <ul>
        <li>cambios en la legislación aplicable;</li>
        <li>incorporación de nuevos servicios o funcionalidades;</li>
        <li>implementación de nuevas tecnologías;</li>
        <li>modificaciones en los procesos internos de tratamiento de datos personales; o</li>
        <li>cualquier otra circunstancia que haga necesaria su actualización.</li>
      </ul>

      <p>Cuando las modificaciones afecten de manera significativa la forma en que tratamos los datos personales, <strong>STREET URBAN</strong> podrá informar dichos cambios mediante avisos publicados en la tienda virtual o a través de otros medios apropiados, cuando ello resulte razonable.</p>

      <h2>12. Contacto</h2>

      <p>Si tienes consultas relacionadas con esta <strong><em>Política de privacidad</em></strong> o deseas comunicarte con <strong>STREET URBAN</strong> respecto del tratamiento de tus datos personales, puedes utilizar cualquiera de los siguientes canales:</p>

      <ul>
        <li>Consultas generales: <code>contacto@gmail.com</code></li>
        <li>Protección de datos personales y ejercicio de derechos ARCO: <code>datos@gmail.com</code></li>
      </ul>

      <p>Las solicitudes serán atendidas conforme a los plazos y procedimientos establecidos por la legislación peruana sobre protección de datos personales.</p>

      <h2>13. Aceptación de la política de privacidad</h2>

      <p>Al utilizar la tienda virtual de <strong>STREET URBAN</strong> y proporcionar voluntariamente tus datos personales mediante cualquiera de los formularios o servicios disponibles, declaras haber leído y comprendido la presente <strong><em>Política de privacidad.</em></strong> Asimismo, reconoces que tus datos serán tratados únicamente para las finalidades descritas en este documento y conforme a las bases legales previstas por la normativa aplicable.</p>

      <p>El uso de la tienda virtual no implica, por sí mismo, el consentimiento para cualquier tratamiento de datos personales. Cuando la legislación exija el consentimiento del usuario para una finalidad específica, <strong>STREET URBAN</strong> lo solicitará de manera previa, libre, informada, expresa e inequívoca mediante los mecanismos correspondientes.</p>

      <h2>Disposición final</h2>

      <p>La presente Política de Privacidad y Protección de Datos Personales entrará en vigor desde su publicación en la tienda virtual de <strong>STREET URBAN</strong> y permanecerá vigente hasta que sea sustituida por una versión posterior.</p>

      <p><strong>STREET URBAN</strong> reafirma su compromiso con la protección de la privacidad de sus clientes, usuarios y visitantes, procurando que el tratamiento de los datos personales se realice de manera transparente, responsable y conforme a la legislación peruana vigente.</p>
    </ArticleLayout>
  </ShopLayout>
</template>
