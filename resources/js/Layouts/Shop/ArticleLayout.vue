<script setup></script>

<style>
  article h2 {
    text-transform: uppercase;
  }

  article code {
    background-color: rgb(var(--gray-800));
  }
</style>

<template>
  <section class="px-4 md:px-8 lg:px-16 max-w-[1400px] mx-auto pt-24 pb-24">
    <div class="max-w-4xl mx-auto">
      <article class="text-gray-400 prose prose-invert max-w-none prose-headings:font-black text-lg">
        <slot />
      </article>
    </div>
  </section>
</template>