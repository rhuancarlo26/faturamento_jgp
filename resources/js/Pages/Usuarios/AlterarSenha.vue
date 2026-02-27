<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()

const props = defineProps({
  users: Array,
  auth: Object
})

const errors = computed(() => page.props.errors || {})
const success = computed(() => page.props.flash?.success)

const mostrarSenhaAtual = ref(false)
const mostrarNovaSenha = ref(false)
const mostrarConfirmacao = ref(false)

const loading = ref(false)

const form = ref({
  senha_atual: '',
  nova_senha: '',
  nova_senha_confirmation: ''
})

function alterar() {
  loading.value = true

  router.post('/alterar-senha', form.value, {
    onSuccess: () => {
      form.value.senha_atual = ''
      form.value.nova_senha = ''
      form.value.nova_senha_confirmation = ''
    },
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>

<template>
  <AuthenticatedLayout :user="auth.user">

    <div class="container mt-4">

      <div class="card shadow-sm border-0">

        <!-- Header -->
        <div class="card-header bg-white border-bottom text-center py-3">
          <h4 class="mb-0 fw-semibold text-dark">
            Alterar Senha
          </h4>
        </div>

        <!-- Body -->
        <div class="card-body">

          <!-- Sucesso -->
          <div v-if="success" class="alert alert-success">
            {{ success }}
          </div>

          <div class="row justify-content-center">
            <div class="col-md-6">

              <!-- Senha Atual -->
              <div class="mb-3">
                <label class="form-label fw-semibold">
                  Senha Atual
                </label>

                <div class="input-group">
                  <input
                    v-model="form.senha_atual"
                    :type="mostrarSenhaAtual ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': errors.senha_atual }"
                  />

                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="mostrarSenhaAtual = !mostrarSenhaAtual"
                  >
                    <i :class="mostrarSenhaAtual ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>

                  <div v-if="errors.senha_atual" class="invalid-feedback">
                    {{ errors.senha_atual }}
                  </div>
                </div>
              </div>

              <!-- Nova Senha -->
              <div class="mb-3">
                <label class="form-label fw-semibold">
                  Nova Senha
                </label>

                <div class="input-group">
                  <input
                    v-model="form.nova_senha"
                    :type="mostrarNovaSenha ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': errors.nova_senha }"
                  />

                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="mostrarNovaSenha = !mostrarNovaSenha"
                  >
                    <i :class="mostrarNovaSenha ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>

                  <div v-if="errors.nova_senha" class="invalid-feedback">
                    {{ errors.nova_senha }}
                  </div>
                </div>
              </div>

              <!-- Confirmação -->
              <div class="mb-4">
                <label class="form-label fw-semibold">
                  Confirmar Nova Senha
                </label>

                <div class="input-group">
                  <input
                    v-model="form.nova_senha_confirmation"
                    :type="mostrarConfirmacao ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': errors.nova_senha_confirmation }"
                  />

                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="mostrarConfirmacao = !mostrarConfirmacao"
                  >
                    <i :class="mostrarConfirmacao ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>

                  <div v-if="errors.nova_senha_confirmation" class="invalid-feedback">
                    {{ errors.nova_senha_confirmation }}
                  </div>
                </div>
              </div>

              <!-- Botão -->
              <div class="text-end">
                <button
                  class="btn btn-primary px-4"
                  @click="alterar"
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Alterando...' : 'Alterar Senha' }}
                </button>
              </div>

            </div>
          </div>

        </div>

      </div>

    </div>
  </AuthenticatedLayout>
</template>
