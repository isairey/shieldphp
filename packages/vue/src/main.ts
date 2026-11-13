import { createApp } from 'vue';

import App from './App.vue';
import router from './router';

import { shield } from './shield';

const app = createApp(App);

app.provide('shieldphp', shield);

shield.install(router);

await shield.initialize();

app.use(router);

app.mount('#app');