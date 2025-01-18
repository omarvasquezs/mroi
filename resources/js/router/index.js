import { createRouter, createWebHistory } from "vue-router";
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

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
        path: "/:pathMatch(.*)*",
        component: () => import("../components/NotfoundPage.vue"),
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    NProgress.start();
    next();
});

router.afterEach(() => {
    NProgress.done();
});

export default router;