<script setup>
defineProps({
  user: Object
})
</script>

<template>
  <nav class="navbar navbar-dark" style="background-color: #3d85c6;">
    <div class="container-fluid d-flex align-items-center">
      <img src="/images/logo.jpg" alt="Logo" style="height: 40px; margin-right: 10px;">
      <span class="navbar-brand mb-0 h1">Sistema de Controle JGP - DNIT</span>

      <div class="dropdown ml-auto">
        <span class="navbar-text text-white dropdown-toggle d-flex align-items-center"
              id="userDropdown"
              data-toggle="dropdown">
          <i class="fas fa-user-circle mr-2" style="font-size: 1.5rem;"></i>
          {{ user?.name || 'N/A' }}
        </span>

        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item" href="#" @click.prevent="$emit('logout')">
            Sair
          </a>
        </div>
      </div>
    </div>
  </nav>
</template>
