<template>
    <div>
        <template v-if="$route.path === '/login'">
            <LoginForm />
        </template>
        <template v-else>
            <Layout />
        </template>
    </div>
</template>

<script>
import LoginForm from './LoginForm.vue';
import Layout from '@/layouts/index.vue';

export default {
    components: {
        LoginForm,
        Layout
    }
}
</script>

<style scoped></style>