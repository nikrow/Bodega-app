<!-- resources/js/Pages/WaterLive.vue -->
<script setup>
import { ref, onMounted } from 'vue';

const message = ref('Esperando datos...');

onMounted(() => {
    window.Echo.channel('icc-pro-data')
        .listen('IccProDataUpdated', (event) => {
            message.value = event.message;
        });
});
</script>

<template>
    <div class="p-6 bg-gray-100 min-h-screen">
        <h1 class="text-3xl font-bold mb-6">WaterLive Dashboard</h1>
        <p class="text-lg text-blue-600">{{ message }}</p>
    </div>
</template>
