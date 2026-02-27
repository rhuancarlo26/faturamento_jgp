<script setup>
import { defineProps, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'


const { user } = defineProps({
   user: Object,
  documentos: Array,
  profissionais: Array,
  user: Object,
  relatorioAtual: Object,
  relatorioEmUso: Object,
  relatorios: Array,
})


const profissionalId = ref('')
const arquivo = ref(null)

// modal pdf
const pdfUrl = ref(null)

// modal gerar relatório
const showModalRelatorio = ref(false)

const submit = () => {
  const formData = new FormData()
  formData.append('profissional_id', profissionalId.value)
  formData.append('arquivo', arquivo.value)

  router.post('/documentos', formData, {
    forceFormData: true,
    onSuccess: () => {
      profissionalId.value = ''
      arquivo.value = null
    }
  })
}

const excluir = (id) => {
  if (!confirm('Tem certeza que deseja excluir este documento?')) return
  router.delete(`/documentos/${id}`)
}

const visualizar = (id) => {
  pdfUrl.value = `/documentos/${id}/visualizar`
  new bootstrap.Modal(document.getElementById('modalPdf')).show()
}

// trocar relatório pelo histórico
const selecionarRelatorio = (relatorioId) => {
  router.get('/documentos', {
    relatorio_id: relatorioId
  }, {
    preserveScroll: true
  })
}

// gerar novo relatório
const gerarNovoRelatorio = () => {
  router.post('/relatorios/gerar-novo', {}, {
    onFinish: () => showModalRelatorio.value = false
  })
}
</script>


<template>
  <AuthenticatedLayout :user="user">
    <div class="min-vh-100 bg-light">
      <div class="d-flex">
        <!-- Conteúdo principal -->
        <div class="flex-grow-1 p-4">
          <div
            class="container-fluid bg-white rounded-lg shadow p-4"
            style="max-width: 1900px;"
          >
            <!-- Header -->
            <div class="position-relative d-flex justify-content-center align-items-center mb-4">
              <h4 class="font-weight-bold text-secondary mb-0 text-center">
                DECLARAÇÕES -
                {{ relatorioEmUso.numero }}º Relatório
                <span
                  v-if="!relatorioEmUso.ativo"
                  class="badge badge-secondary ml-2"
                >
                  HISTÓRICO
                </span>
              </h4>

              <button
                v-if="relatorioAtual.ativo"
                class="btn btn-outline-primary position-absolute"
                style="right: 0;"
                @click="showModalRelatorio = true"
              >
                Gerar
              </button>
            </div>


            <!-- Histórico de Relatórios -->
            <div class="mb-4">
              <h6 class="font-weight-bold text-secondary mb-3">
                Histórico de Declarações
              </h6>

              <div class="row">
                <div
                  v-for="rel in relatorios"
                  :key="rel.id"
                  class="col-md-2 col-sm-3 mb-2"
                >
                  <div
                    class="card h-100 shadow-sm"
                    style="cursor: pointer; font-size: .85rem;"
                    :class="{
                      'border-primary bg-light': rel.id === relatorioEmUso.id
                    }"
                    @click="selecionarRelatorio(rel.id)"
                  >
                    <div class="card-body py-2 px-3">

                      <div class="d-flex align-items-center mb-1">
                        <i
                          class="fas fa-folder mr-2"
                          :class="rel.ativo ? 'text-primary' : 'text-warning'"
                          style="font-size: 1.1rem;"
                        ></i>

                        <span class="font-weight-bold">
                          {{ rel.numero }}º Relatório
                        </span>
                      </div>

                      <div class="text-muted small">
                        {{ rel.documentos_count }} doc(s)
                      </div>

                      <span
                        v-if="rel.ativo"
                        class="badge badge-primary mt-1"
                        style="font-size: .65rem;"
                      >
                        ATUAL
                      </span>

                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Upload (somente relatório ativo) -->
            <div
              v-if="relatorioEmUso.ativo"
              class="card mb-4 shadow-sm"
            >
              <div class="card-body">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                  <div class="form-row align-items-end">

                    <div class="form-group col-md-5">
                      <label class="font-weight-bold">Profissional</label>
                      <select
                        class="form-control"
                        v-model="profissionalId"
                        required
                      >
                        <option value="">Selecione um profissional</option>

                        <option
                          v-for="p in profissionais"
                          :key="p.id"
                          :value="p.id"
                        >
                          {{ p.nome }}
                        </option>
                      </select>
                    </div>

                    <div class="form-group col-md-5">
                      <label class="font-weight-bold">Arquivo (PDF)</label>
                      <input
                        type="file"
                        class="form-control"
                        accept="application/pdf"
                        @change="e => arquivo = e.target.files[0]"
                        required
                      />
                    </div>

                    <div class="form-group col-md-2">
                      <button class="btn btn-primary btn-block">
                        Enviar
                      </button>
                    </div>

                  </div>
                </form>
              </div>
            </div>

            <!-- Lista de documentos -->
            <div class="card shadow-sm">
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">

                  <li
                    v-for="doc in documentos"
                    :key="doc.id"
                    class="list-group-item d-flex justify-content-between align-items-center"
                  >
                    <div class="d-flex align-items-center">
                      <i class="fas fa-file-pdf text-danger mr-3"></i>

                      <strong>
                        {{ doc.profissional?.nome }}
                      </strong>
                    </div>

                    <div class="btn-group btn-group-sm">
                      <button
                        class="btn btn-outline-secondary"
                        @click="visualizar(doc.id)"
                      >
                        <i class="fas fa-eye"></i>
                      </button>

                      <a
                        :href="`/documentos/${doc.id}/download`"
                        class="btn btn-outline-primary"
                      >
                        <i class="fas fa-download"></i>
                      </a>

                      <button
                        v-if="relatorioEmUso.ativo"
                        class="btn btn-outline-danger"
                        @click="excluir(doc.id)"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </li>

                  <li
                    v-if="!documentos.length"
                    class="list-group-item text-center text-muted py-4"
                  >
                    Nenhum documento neste relatório.
                  </li>

                </ul>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Modal PDF -->
      <div class="modal fade" id="modalPdf" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-body p-0" style="height:80vh">
              <iframe
                v-if="pdfUrl"
                :src="pdfUrl"
                width="100%"
                height="100%"
                style="border:none"
              ></iframe>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Gerar Novo Relatório -->
      <div
        class="modal fade"
        :class="{ show: showModalRelatorio }"
        v-show="showModalRelatorio"
        style="z-index: 1050; display: block;"
        aria-modal="true"
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content shadow">

            <!-- Header -->
            <div class="modal-header modal-header-custom">
              <h5 class="modal-title w-100 text-center font-weight-bold mb-0">
                Gerar Nova Pasta
              </h5>

              <button
                type="button"
                class="close-btn"
                @click="showModalRelatorio = false"
              >
                &times;
              </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
              <p class="mb-0 text-dark">
                Ao gerar uma nova pasta:
              </p>

              <ul class="mt-2 text-dark">
                <li>A pasta atual vai para o <strong>histórico</strong></li>
                <li>Uma nova pasta será criada com a sequência numérica</li>
                <li>Apenas a nova pasta ficará ativa para inserir e editar</li>
              </ul>

              <p class="mt-3 text-danger font-weight-bold mb-0">
                Deseja continuar?
              </p>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
              <button
                class="btn btn-secondary"
                @click="showModalRelatorio = false"
              >
                Cancelar
              </button>

              <button
                class="btn btn-success"
                @click="gerarNovoRelatorio"
              >
                Confirmar
              </button>
            </div>

          </div>
        </div>
      </div>

      <div class="modal-backdrop fade show" v-show="showModalRelatorio" style="z-index: 1040;"></div>

    </div>
  </AuthenticatedLayout>
</template>

<style>
.modal-header-custom {
  background-color: #2986cc;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 15px 20px;
  border-bottom: none;
}

.close-btn {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 22px;
  font-weight: bold;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.close-btn:hover {
  opacity: 0.7;
}
</style>