import { createRouter, createWebHistory } from "vue-router";
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';
import Caja from '../components/Caja.vue';

const routes = [
    {
        path: "/",
        component: () => import("../components/HomePage.vue"),
    },
    {
        path: "/login",
        name: "login",
        component: () => import("../components/LoginForm.vue"),
    },
    {
        path: "/usuarios",
        name: "Usuarios",
        component: () => import("../components/UsuariosCrud.vue"),
    },
    {
        path: "/medicos",
        name: "Medicos",
        component: () => import("../components/MedicosCrud.vue"),
    },
    {
        path: "/pacientes",
        name: "Pacientes",
        component: () => import("../components/PacientesCrud.vue"),
    },
    {
        path: "/registro-citas",
        name: "RegistroCitas",
        component: () => import("../components/RegistroCitasCrud.vue"),
    },
    {
        path: "/control-stock",
        name: "ControlStock",
        component: () => import("../components/ControlStockCrud.vue"),
    },
    {
        path: "/catalogo-lentes",
        name: "CatalogoLentes",
        component: () => import("../components/CatalogoLentes.vue"),
    },
    {
        path: "/tipos-citas",
        name: "TiposCitas",
        component: () => import("../components/TiposCitasCrud.vue"),
    },
    {
        path: '/caja',
        name: 'caja',
        component: Caja,
        meta: { requiresAuth: true }
    },
    {
        path: '/metodos-pago',
        name: 'metodos-pago',
        component: () => import('../components/MetodosPago.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: "/change-password",
        name: "ChangePassword",
        component: () => import("../views/ChangePassword.vue"),
        meta: { requiresAuth: true }
    },
    {
        path: "/proveedores",
        name: "Proveedores",
        component: () => import("../components/ProveedoresCrud.vue"),
    },
    {
        path: "/:pathMatch(.*)*",
        component: () => import("../components/NotfoundPage.vue"),
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// This function is a global navigation guard for the Vue Router.
// It runs before each route change.
router.beforeEach((to, from, next) => {
    // Start the progress bar animation.
    NProgress.start();

    // Retrieve the 'user' item from local storage.
    const storedUser = localStorage.getItem('user');

    // Determine if the user is authenticated based on the presence of 'user' in local storage.
    const isAuthenticated = !!storedUser;

    // If the user is authenticated and trying to access the login page, redirect to the home page.
    if (isAuthenticated && to.path === '/login') {
        next('/');
    } 
    // If the user is not authenticated and trying to access any page other than the login page, redirect to the login page.
    else if (!isAuthenticated && to.path !== '/login') {
        next('/login');
    } 
    // Otherwise, allow the navigation to proceed.
    else {
        next();
    }
});

router.afterEach(() => {
    NProgress.done();
});

export default router;