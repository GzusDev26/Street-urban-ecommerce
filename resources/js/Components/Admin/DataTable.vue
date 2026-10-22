<!-- Tabla con el formato estándar del panel. Las filas van en el slot por defecto (<tr>),
     la cabecera en #head. Las <th> deben llevar su propia alineación (text-left / text-right). -->
<template>
  <div class="px-3 sm:px-6 pb-6">
    <div class="overflow-x-auto rounded-xl border border-gray-100">
      <table class="w-full text-sm">
        <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-gray-500 [&>tr>th]:font-semibold [&>tr>th]:py-3 [&>tr>th]:px-5">
          <slot name="head" />
        </thead>
        <tbody class="divide-y divide-gray-100 [&>tr]:transition-colors [&>tr:hover]:bg-orange-50/40 [&>tr>td]:py-3.5 [&>tr>td]:px-5">
          <slot />
        </tbody>
      </table>
    </div>

    <slot name="footer" />
  </div>
</template>
