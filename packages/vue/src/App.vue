
<script setup lang="ts">
import { RouterView } from 'vue-router';
</script>

<template>
    <div id="shieldphp-app">
        <RouterView />
    </div>
</template>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-width: 320px;
    font-family: Inter, system-ui, -apple-system, sans-serif;
    background: #f8fafc;
    color: #1e293b;
}

button,
input {
    font: inherit;
}

button {
    cursor: pointer;
}
</style>
