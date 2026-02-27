<script setup>
import { Inertia } from '@inertiajs/inertia'
import { defineProps, computed } from 'vue'

const props = defineProps({
  user: Object,
})

const currentPath = window.location.pathname

const isActive = (path) => currentPath.startsWith(path)

const isSubprodutosActive = computed(() =>
  isActive('/subprodutos')
)

// 🔒 Controle de acesso ao menu Usuários
const podeVerUsuarios = computed(() => {
  if (!props.user) return false
  return props.user.role === 'admin' || props.user.role === 'master'
})

const logout = () => {
  Inertia.post('/logout', {}, {
    onSuccess: () => (window.location.href = '/subprodutos')
  })
}
</script>

<template>
  <div class="min-vh-100 bg-light">
    <!-- 🔹 Navbar -->
    <nav class="navbar navbar-dark" style="background-color: #3d85c6;">
      <div class="container-fluid d-flex align-items-center">
        <img src="/images/logo.jpg" alt="Logo" style="height: 40px; margin-right: 10px;">
        <span class="navbar-brand mb-0 h1">Sistema de Controle JGP - DNIT</span>

        <div class="dropdown ml-auto">
          <span
            class="navbar-text text-white dropdown-toggle d-flex align-items-center"
            data-toggle="dropdown"
            style="cursor: pointer;"
          >
            <i class="fas fa-user-circle mr-2" style="font-size: 1.5rem;"></i>
            {{ user?.name || 'N/A' }}
          </span>

          <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" href="#" @click.prevent="logout">Sair</a>
            <a class="dropdown-item" href="/alterar-senha">Alterar Senha</a>
          </div>
        </div>
      </div>
    </nav>

    <div class="d-flex">
      <!-- 🔹 Menu lateral -->
      <aside class="bg-white border-right shadow-sm" style="width: 250px; min-height: calc(100vh - 56px);">
        <ul class="nav flex-column p-3 mt-4">

          <!-- SUBPRODUTOS -->
          <li class="nav-item" :class="{ active: isSubprodutosActive }">
            <a
              class="nav-link text-uppercase font-weight-bold d-flex justify-content-between align-items-center"
              style="color: #4B5563; font-size: 0.9rem; cursor: pointer;"
              data-toggle="collapse"
              href="#menuSubprodutos"
              role="button"
              :aria-expanded="isSubprodutosActive"
              aria-controls="menuSubprodutos"
            >
              <span>
                <i class="fas fa-box mr-2" style="color: #007BFF;"></i>
                SUBPRODUTOS
              </span>
              <i class="fas fa-chevron-down"></i>
            </a>

            <div
              class="collapse"
              :class="{ show: isSubprodutosActive }"
              id="menuSubprodutos"
            >
              <ul class="nav flex-column ml-3 mt-0">
                <li
                  class="nav-item"
                  :class="{ active: isActive('/subprodutos') && !isActive('/subprodutos/create') }"
                >
                  <a
                    class="nav-link font-weight-bold"
                    style="color: #4B5563; font-size: 0.85rem;"
                    href="/subprodutos"
                  >
                    <i class="fas fa-search mr-2 text-primary"></i>
                    Consultar
                  </a>
                </li>

                <li
                  class="nav-item"
                  :class="{ active: isActive('/subprodutos/create') }"
                >
                  <a
                    class="nav-link font-weight-bold"
                    style="color: #4B5563; font-size: 0.85rem;"
                    href="/subprodutos/create"
                  >
                    <i class="fas fa-plus-circle mr-2 text-success"></i>
                    Cadastrar
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- OFÍCIOS -->
          <li
            class="nav-item"
            :class="{ active: isActive('/oficios') || isActive('/oficios-listar') }"
          >
            <a
              class="nav-link text-uppercase font-weight-bold"
              style="color: #4B5563; font-size: 0.9rem;"
              href="/oficios-listar"
            >
              <i class="fas fa-file-alt mr-2" style="color: #007BFF;"></i>
              OFÍCIOS
            </a>
          </li>

          <li
            class="nav-item"
            :class="{ active: isActive('/dav') }"
          >
            <a
              class="nav-link text-uppercase font-weight-bold"
              style="color: #4B5563; font-size: 0.9rem;"
              href="/dav"
            >
              <i class="fas fa-file-invoice-dollar mr-2" style="color: #28a745;"></i>
              DAV
            </a>
          </li>

          <!-- DOCUMENTAÇÃO -->
          <li
            class="nav-item"
            :class="{ active: isActive('/documentos') }"
          >
            <a
              class="nav-link text-uppercase font-weight-bold"
              style="color: #4B5563; font-size: 0.9rem;"
              href="/documentos"
            >
              <i class="fas fa-file-pdf mr-2" style="color: #dc3545;"></i>
              DOCUMENTAÇÃO
            </a>
          </li>

          <!-- 🔐 USUÁRIOS (apenas admin e master) -->
          <li
            v-if="podeVerUsuarios"
            class="nav-item"
            :class="{ active: isActive('/usuarios') }"
          >
            <a
              class="nav-link text-uppercase font-weight-bold"
              style="color: #4B5563; font-size: 0.9rem;"
              href="/usuarios"
            >
              <i class="fas fa-users mr-2" style="color: #6f42c1;"></i>
              USUÁRIOS
            </a>
          </li>

        </ul>
      </aside>

      <!-- 🔹 Conteúdo -->
      <main class="flex-grow-1 p-4">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.nav-item.active > .nav-link {
  background-color: #e9f2ff;
  border-left: 4px solid #007BFF;
  color: #1f2937 !important;
  border-radius: 4px;
}

.nav-item.active .nav-item.active > .nav-link {
  background-color: #f1f5f9;
  border-left: 3px solid #60a5fa;
}

.nav-link:hover {
  background-color: #f1f5f9;
  border-radius: 4px;
}
</style>
