<script setup>
import { useTheme } from '@/theme';

const { isDark } = useTheme();
</script>

<template>
    <img :src="isDark ? '/image/logo/street-urban-icono-dark.png' : '/image/logo/street-urban-icono-light.png'" alt="Street Urban" />
</template>
