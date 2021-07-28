import 'vite/dynamic-import-polyfill';
import App from '@/vue/App.vue';
import { createApp } from 'vue';
import 'alpinejs';

const modules = import.meta.globEager('./components/*.js')

// Import our CSS
import '@/css/main.css';

// App main
const main = async () => {
    // Create our vue instance
    const app = createApp(App);
    // Mount the app
    const root = app.mount('#component-container');

    return root;
};

// Execute async function
main().then( (root) => {
    console.log('Loaded.');

// Init sliders
    window.sliders()
});
