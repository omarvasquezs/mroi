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

document.addEventListener('scroll', () => {
  const backToTopButton = document.querySelector('.back-to-top');
  if (backToTopButton && window.scrollY > 200) {
    backToTopButton.classList.add('show');
  } else if (backToTopButton) {
    backToTopButton.classList.remove('show');
  }
});

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('[data-toggle="sb-sidenav-toggle"]');
    const sidebarWrapper = document.getElementById('sb-sidenav-wrapper');

    if (menuToggle && sidebarWrapper) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebarWrapper.classList.toggle('toggled');
        });
    }

    // Handle window resize
    let windowWidth = window.innerWidth;
    window.addEventListener('resize', function() {
        if (sidebarWrapper && windowWidth !== window.innerWidth) {
            windowWidth = window.innerWidth;
            if (window.innerWidth < 768) {
                sidebarWrapper.classList.add('toggled');
            } else {
                sidebarWrapper.classList.remove('toggled');
            }
        }
    });
});