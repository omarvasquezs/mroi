import './bootstrap';
import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import Footer from '@/layouts/Footer.vue';
import Navbar from '@/layouts/Navbar.vue';
import HomePage from './components/HomePage.vue';
import Layout from '@/layouts/index.vue';
import 'v-calendar/dist/style.css';

const app = createApp(App);

// Make bootstrap globally available
window.bootstrap = bootstrap;

// Register components globally
app.component('Footer', Footer);
app.component('Navbar', Navbar);
app.component('HomePage', HomePage);
app.component('Layout', Layout);

app.use(router).mount('#app');