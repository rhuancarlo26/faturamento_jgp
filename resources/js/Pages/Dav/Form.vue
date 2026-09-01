<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const props = defineProps({
  dav: Object, // 👈 agora pode existir
  empreendimentos: Array,
  produtos: Array,
  profissionais: Array
})

/* ================================
   FORM (CREATE OU EDIT)
================================ */

const form = ref({
  coordenador: '',
  empreendimento_id: '',
  ose: '',
  n_sei_ose: '',
  produto: '',
  subproduto: '',
  profissionais: []
})

function estruturaProfissionalVazia() {
  return {
    profissional_id: '',
    funcao: '',
    data_ini: '',
    data_fim: '',
    diarias: null,
    desc_trecho: '',
    trechos: [
      {
        origem: '',
        destino: '',
        aereo_qtd: null,
        aquatico_qtd: null,
        terrestre_pickup_qtd: null,
        terrestre_hatch_qtd: null,
      }
    ]
  }
}

const subprodutos = ref([])
const loadingSubprodutos = ref(false)

/* ================================
   CARREGAR DADOS SE FOR EDIÇÃO
================================ */

onMounted(async () => {

  if (!props.dav) {
    form.value.profissionais = [estruturaProfissionalVazia()]
    return
  }

  // Preenche dados básicos
  form.value.coordenador = props.dav.coordenador
  form.value.empreendimento_id = props.dav.empreendimento_id
  form.value.ose = props.dav.n_ose
  form.value.n_sei_ose = props.dav.n_sei_ose || ''
  form.value.produto = props.dav.produto
  form.value.subproduto = props.dav.subproduto

  // Carrega subprodutos do produto
  if (props.dav.produto) {
    const response = await axios.get('/dav/subprodutos', {
      params: { produto: props.dav.produto }
    })
    subprodutos.value = response.data
  }

  // Profissionais
  form.value.profissionais = props.dav.profissionais.map(p => ({
    profissional_id: p.profissional_id,
    funcao: p.funcao,
    data_ini: p.data_ini,
    data_fim: p.data_fim,
    diarias: p.diarias,
    desc_trecho: p.desc_trecho || '',
    trechos: p.trechos.map(t => ({
      origem: t.origem,
      destino: t.destino,
      aereo_qtd: t.aereo_qtd,
      aquatico_qtd: t.aquatico_qtd,
      terrestre_pickup_qtd: t.terrestre_pickup_qtd,
      terrestre_hatch_qtd: t.terrestre_hatch_qtd,
    }))
  }))
})

/* ================================
   WATCHERS
================================ */

watch(() => form.value.empreendimento_id, (id) => {
  const emp = props.empreendimentos.find(e => e.id == id)
  form.value.ose = emp ? emp.ose_emp : ''
})

watch(() => form.value.produto, async (produto) => {

  form.value.subproduto = ''
  subprodutos.value = []

  if (!produto) return

  loadingSubprodutos.value = true

  try {
    const response = await axios.get('/dav/subprodutos', {
      params: { produto }
    })
    subprodutos.value = response.data
  } catch (error) {
    console.error(error)
  } finally {
    loadingSubprodutos.value = false
  }
})

/* ================================
   MODAL PROFISSIONAL
================================ */

const showModalProfissional = ref(false)

const novoProfissional = ref({
  nome: '',
  formacao: ''
})

async function salvarProfissional() {

  try {

    const response = await axios.post('/dav/profissionais', novoProfissional.value)

    props.profissionais.push(response.data)

    novoProfissional.value.nome = ''
    novoProfissional.value.formacao = ''
    showModalProfissional.value = false

  } catch (error) {
    console.error(error)
    alert('Erro ao cadastrar profissional')
  }
}

/* ================================
   PROFISSIONAIS
================================ */

function adicionarProfissional() {
  form.value.profissionais.push(estruturaProfissionalVazia())
}

function removerProfissional(index) {
  form.value.profissionais.splice(index, 1)
}

function adicionarTrecho(prof) {
  prof.trechos.push({
    origem: '',
    destino: '',
    aereo_qtd: null,
    aquatico_qtd: null,
    terrestre_pickup_qtd: null,
    terrestre_hatch_qtd: null,
  })
}

function removerTrecho(prof, index) {
  if (prof.trechos.length > 1) {
    prof.trechos.splice(index, 1)
  }
}

/* ================================
   SALVAR
================================ */

function salvar() {
  if (props.dav) {
    router.put(`/dav/${props.dav.id}`, form.value)
  } else {
    router.post('/dav', form.value)
  }
}
</script>

<template>
<AuthenticatedLayout :user="user">

<div class="container-fluid mt-4 px-4">
  <div class="card shadow-sm border-0 card-dav">

    <div class="card-header bg-white border-bottom text-center py-3">
      <h4 class="mb-0 fw-bold">
        Documento de Autorização de Viagem (DAV)
      </h4>
    </div>

    <div class="card-body">

      <form @submit.prevent="salvar">

        <!-- ================= DADOS GERAIS ================= -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Coordenador Setorial</label>
            <input v-model="form.coordenador"
                   type="text"
                   class="form-control campo-moderno"
                   required />
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Empreendimento</label>
            <select v-model="form.empreendimento_id"
                    class="form-control campo-moderno"
                    required>
              <option value="">Selecione</option>
              <option v-for="emp in empreendimentos"
                      :key="emp.id"
                      :value="emp.id">
                {{ emp.cod_emp }}
              </option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nº OSE</label>
            <input v-model="form.ose"
                   type="text"
                   class="form-control campo-moderno bg-light text-muted"
                   readonly />
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nº SEI</label>
            <input v-model="form.n_sei_ose"
                   type="text"
                   class="form-control campo-moderno" />
          </div>

        </div>

        <!-- ================= PRODUTO ================= -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Produto</label>
            <select v-model="form.produto"
                    class="form-control campo-moderno"
                    required>
              <option value="">Selecione</option>
              <option v-for="produto in produtos"
                      :key="produto"
                      :value="produto">
                {{ produto }}
              </option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Subproduto</label>
            <select v-model="form.subproduto"
                    class="form-control campo-moderno"
                    :disabled="!form.produto || loadingSubprodutos"
                    required>
              <option value="">Selecione</option>
              <option v-for="sub in subprodutos"
                      :key="sub"
                      :value="sub">
                {{ sub }}
              </option>
            </select>

            <div v-if="loadingSubprodutos"
                 class="text-muted small mt-1">
              Carregando subprodutos...
            </div>
          </div>

        </div>

        <!-- ================= PROFISSIONAIS ================= -->
        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold text-secondary mb-0">
            Profissionais
          </h5>

          <button type="button"
                  class="btn btn-outline-primary"
                  @click="adicionarProfissional">
            + Adicionar Profissional
          </button>
        </div>

        <div v-for="(prof, index) in form.profissionais"
            :key="index"
            class="card border rounded p-3 mb-4">

          <div class="d-flex justify-content-between mb-3">
            <strong>Profissional {{ index + 1 }}</strong>

            <button v-if="form.profissionais.length > 1"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="removerProfissional(index)">
              Remover
            </button>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Profissional</label>

              <div class="d-flex">
                <select v-model="prof.profissional_id"
                        class="form-control campo-moderno"
                        required>
                  <option value="">Selecione</option>
                  <option v-for="p in profissionais"
                          :key="p.id"
                          :value="p.id">
                    {{ p.nome }} - {{ p.formacao }}
                  </option>
                </select>

                <button type="button"
                        class="btn btn-outline-success ms-2"
                        @click="showModalProfissional = true">
                  +
                </button>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label fw-semibold">Função</label>
              <input v-model="prof.funcao"
                    type="text"
                    class="form-control campo-moderno"
                    required />
            </div>

            <div class="col-md-2 mb-3">
              <label class="form-label fw-semibold">Data Início</label>
              <input v-model="prof.data_ini"
                    type="date"
                    class="form-control campo-moderno"
                    required />
            </div>

            <div class="col-md-2 mb-3">
              <label class="form-label fw-semibold">Data Fim</label>
              <input v-model="prof.data_fim"
                    type="date"
                    class="form-control campo-moderno"
                    required />
            </div>

            <div class="col-md-1 mb-2">
              <label class="form-label">Diárias</label>
              <input v-model="prof.diarias"
                    type="number"
                    class="form-control campo-moderno" />
            </div>
          </div>

          <!-- TRECHOS -->
          <div class="mt-3">

            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>Trechos</strong>

              <button type="button"
                      class="btn btn-sm btn-outline-success"
                      @click="adicionarTrecho(prof)">
                + Adicionar Trecho
              </button>
            </div>

            <div v-for="(trecho, tIndex) in prof.trechos"
                :key="tIndex"
                class="border rounded p-3 mb-3 bg-light">

              <div class="d-flex justify-content-between mb-2">
                <small class="fw-semibold">
                  Trecho {{ tIndex + 1 }}
                </small>

                <button v-if="prof.trechos.length > 1"
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        @click="removerTrecho(prof, tIndex)">
                  Remover
                </button>
              </div>

              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label">Origem</label>
                  <input v-model="trecho.origem"
                        type="text"
                        class="form-control campo-moderno"
                        required />
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Destino</label>
                  <input v-model="trecho.destino"
                        type="text"
                        class="form-control campo-moderno"
                        required />
                </div>
              </div>

              <div class="row mt-2">
                <div class="col-md-3 mb-2">
                  <label class="form-label">Aéreo (Qtd)</label>
                  <input v-model="trecho.aereo_qtd"
                        type="number"
                        class="form-control campo-moderno" />
                </div>

                <div class="col-md-3 mb-2">
                  <label class="form-label">Aquático (Qtd)</label>
                  <input v-model="trecho.aquatico_qtd"
                        type="number"
                        class="form-control campo-moderno" />
                </div>

                <div class="col-md-3 mb-2">
                  <label class="form-label">Terrestre Pickup (Qtd)</label>
                  <input v-model="trecho.terrestre_pickup_qtd"
                        type="number"
                        class="form-control campo-moderno" />
                </div>

                <div class="col-md-3 mb-2">
                  <label class="form-label">Terrestre Hatch (Qtd)</label>
                  <input v-model="trecho.terrestre_hatch_qtd"
                        type="number"
                        class="form-control campo-moderno" />
                </div>
              </div>

            </div>
          </div>
          <div class="mt-3">
            <label class="form-label fw-semibold">Descrição do trecho</label>
            <textarea v-model="prof.desc_trecho"
                      class="form-control campo-moderno"
                      rows="3"
                      placeholder="Informe uma descrição complementar do trecho"></textarea>
          </div>
        </div>

        <!-- BOTÕES -->
        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
            <button type="button"
                  class="btn btn-outline-secondary me-2 px-4"
                  @click="$inertia.visit('/dav')">
                Cancelar
            </button>

            <button type="submit" class="btn btn-success px-4">
                {{ props.dav ? 'Atualizar DAV' : 'Salvar DAV' }}
            </button>
        </div>

      </form>

    </div>
  </div>
</div>

<!-- MODAL PROFISSIONAL -->
<div v-if="showModalProfissional" class="modal-overlay">
  <div class="modal-custom">

    <h5 class="fw-bold mb-3">
      Cadastrar Profissional
    </h5>

    <div class="mb-3">
      <label class="form-label">Nome</label>
      <input v-model="novoProfissional.nome"
             type="text"
             class="form-control campo-moderno">
    </div>

    <div class="mb-3">
      <label class="form-label">Formação</label>
      <input v-model="novoProfissional.formacao"
             type="text"
             class="form-control campo-moderno">
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-outline-secondary me-2"
              @click="showModalProfissional = false">
        Cancelar
      </button>

      <button class="btn btn-success"
              @click="salvarProfissional">
        Salvar
      </button>
    </div>

  </div>
</div>

</AuthenticatedLayout>
</template>

<style scoped>
.card-dav {
  border-top: 4px solid #198754;
  border-radius: 10px;
}

.campo-moderno {
  height: 42px;
  border-radius: 8px;
  border: 1px solid #dee2e6;
  background-color: #fff;
  transition: all 0.2s ease;
}

.campo-moderno:focus {
  border-color: #198754;
  box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, 0.15);
}

select.campo-moderno {
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-custom {
  background: white;
  padding: 25px;
  border-radius: 12px;
  width: 400px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
</style>
