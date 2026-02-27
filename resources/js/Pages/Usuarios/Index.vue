<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  users: Array,
  auth: Object
})

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'usuario', 
})

const editForm = ref({
  id: null,
  name: '',
  email: ''
})

let modalInstance = null

function abrirEdicao(user) {
  editForm.value = {
    id: user.id,
    name: user.name,
    email: user.email
  }

  const modalElement = document.getElementById('modalEditar')
  modalInstance = new bootstrap.Modal(modalElement)
  modalInstance.show()
}

function atualizar() {

  if (modalInstance) {
    modalInstance.hide()
  }

  router.put(`/usuarios/${editForm.value.id}`, {
    name: editForm.value.name,
    email: editForm.value.email
  }, {
    preserveScroll: true
  })
}

function salvar() {
  router.post('/usuarios', form.value)
}

const roleLevel = {
  usuario: 1,
  coordenador: 2,
  admin: 3,
  master: 4,
  fiscal: 5
}

function podeExcluir() {
  return props.auth.user.role === 'master'
}

function confirmarExclusao(id) {
  if (confirm('Tem certeza que deseja excluir este usuário?')) {
    router.delete(`/usuarios/${id}`)
  }
}
</script>

<template>
  <AuthenticatedLayout :user="auth.user">

    <div class="container mt-4">

      <div class="card shadow-sm border-0">

        <div class="card-header bg-white border-bottom text-center">
          <h4 class="mb-0 fw-bold text-dark">
            Gerenciar Usuários
          </h4>
        </div>

        <div class="card-body">
        
          <div
            v-if="$page.props.flash?.success"
            class="alert alert-success"
            >
            {{ $page.props.flash.success }}
          </div>

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0 fw-bold">
                Novo Usuário
              </h6>
            </div>

            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                  <input v-model="form.name" placeholder="Nome" class="form-control" />
                </div>

                <div class="col-md-3">
                  <input v-model="form.email" placeholder="Email" class="form-control" />
                </div>

                <div class="col-md-2">
                  <input v-model="form.password" type="password" placeholder="Senha" class="form-control" />
                </div>

                <div class="col-md-2">
                  <select v-model="form.role" class="form-control">
                    <option value="admin">Admin</option>
                    <option value="coordenador">Coordenador</option>
                    <option value="usuario">Usuário</option>
                    <option value="fiscal">Fiscal</option>
                  </select>
                </div>

                <div class="col-md-2 d-grid">
                  <button class="btn btn-success" @click="salvar">
                    Criar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th>Email</th>
                  <th>Perfil</th>
                  <th width="120">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id">
                  <td>{{ user.name }}</td>
                  <td>{{ user.email }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="{
                        'bg-danger text-white': user.role === 'master',
                        'bg-success text-white': user.role === 'admin',
                        'bg-warning text-dark': user.role === 'coordenador',
                        'bg-secondary text-white': user.role === 'usuario',
                        'bg-info text-white': user.role === 'fiscal'
                      }"
                    >
                      {{ user.role }}
                    </span>
                  </td>

                  <td class="text-center">
                    <button
                      class="btn btn-sm btn-outline-primary btn-icon me-1"
                      @click="abrirEdicao(user)"
                      title="Editar usuário"
                    >
                      <i class="bi bi-pencil-fill"></i>
                    </button>

                    <button
                      v-if="podeExcluir(user.role)"
                      class="btn btn-sm btn-outline-danger btn-icon"
                      @click="confirmarExclusao(user.id)"
                      title="Excluir usuário"
                    >
                      <i class="bi bi-trash3-fill"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

    <div class="modal fade" id="modalEditar" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">Editar Usuário</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nome</label>
              <input v-model="editForm.name" class="form-control" />
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input v-model="editForm.email" class="form-control" />
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>
            <button class="btn btn-primary" @click="atualizar">
              Salvar Alterações
            </button>
          </div>

        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
