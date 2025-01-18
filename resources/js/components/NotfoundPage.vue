<template>
  <div class="notfound-container">
    <div class="notfound-content">
      <h1 class="notfound-title">404</h1>
      <p class="notfound-message">Página no encontrada</p>
      <button @click="goBack" class="btn btn-primary">Regresar</button>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    goBack() {
      window.history.back();
    }
  }
};
</script>

<style scoped>
.notfound-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px; /* Added padding */
  background-color: #f8f9fa;
  animation: fadeIn 1s ease-in-out;
}

.notfound-content {
  text-align: center;
  animation: slideIn 1s ease-in-out;
}

.notfound-title {
  font-size: 6rem;
  color: #343a40;
}

.notfound-message {
  font-size: 1.5rem;
  color: #6c757d;
  margin-bottom: 1.5rem;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    transform: translateY(-50px);
  }
  to {
    transform: translateY(0);
  }
}
</style>