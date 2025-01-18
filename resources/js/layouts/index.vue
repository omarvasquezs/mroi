<template>
    <div class="d-flex flex-column min-vh-100">
        <Navbar />
        <div class="container flex-grow-1 pt-5 mt-5 mb-5 pb-5">
            <router-view />
        </div>
        <Footer />
    </div>
</template>