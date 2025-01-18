import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import Footer from '@/layouts/Footer.vue';
import Navbar from '@/layouts/Navbar.vue';
import HomePage from './components/HomePage.vue';
import Layout from '@/layouts/index.vue';

const app = createApp(App);

// Register components globally
app.component('Footer', Footer);
app.component('Navbar', Navbar);
app.component('HomePage', HomePage);
app.component('Layout', Layout);

app.use(router).mount('#app');