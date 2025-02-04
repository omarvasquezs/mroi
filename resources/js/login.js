import './bootstrap';
import { createApp } from 'vue';
import App from './components/LoginForm.vue';
import router from './router';

const app = createApp(App);

if (document.getElementById('login')) {
    app.use(router).mount('#login');
}