<template>
    <div class="d-flex flex-column min-vh-100">
        <Navbar />
        <div class="container flex-grow-1 mt-5">
            <router-view />
        </div>
        <Footer />
    </div>
</template>

<script>
export default {
    methods: {
        handleScroll() {
            // Handle navbar spacing consistently across all pages
            const navbarHeight = document.querySelector('.navbar')?.offsetHeight || 56;
            document.documentElement.style.setProperty('--navbar-height', `${navbarHeight}px`);
            
            // If there's a submenu on the page, handle its positioning
            const submenuBlock = document.getElementById('submenu-block-area');
            if (submenuBlock) {
                const scrollPosition = window.scrollY;
                if (scrollPosition < 10) {
                    submenuBlock.style.top = '10px';
                } else {
                    submenuBlock.style.top = `${navbarHeight}px`;
                }
            }
        }
    },
    mounted() {
        // Check if page has fixed navbar and add appropriate class to body
        if (document.querySelector('.navbar.fixed-top')) {
            document.body.classList.add('has-navbar-fixed-top');
        }
        
        // Add scroll event listener to handle dynamic positioning
        window.addEventListener('scroll', this.handleScroll);
        // Initialize on page load
        this.handleScroll();
    },
    beforeUnmount() {
        // Clean up event listener
        window.removeEventListener('scroll', this.handleScroll);
    }
};
</script>

<style>
/* Global styles for consistent spacing */
body.has-navbar-fixed-top {
    padding-top: 56px; /* Same as navbar height */
}

@media (min-width: 992px) {
    body.has-navbar-fixed-top {
        padding-top: 60px; /* Adjust based on your actual navbar height on desktop */
    }
}

/* Sticky submenu styles moved from HomePage.vue */
.sticky-submenu {
    position: sticky;
    z-index: 999;
    border-bottom: 1px solid #dee2e6;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    min-height: 100px;
    display: block;
    background-color: #f8f9fa;
    transition: top 0.2s ease;
    /* top value will be set dynamically by JavaScript */
}

/* Space to prevent content jump when submenu becomes sticky */
.submenu-spacer {
    min-height: 10px;
}
</style>