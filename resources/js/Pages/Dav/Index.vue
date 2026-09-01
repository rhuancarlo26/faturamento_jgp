<script setup>
import { computed, reactive, watch } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()

const user = computed(() => page.props.auth?.user)

const props = defineProps({
  davs: Array,
  empreendimentos: Array,
  produtos: Array,
  statusList: Array,
  filtros: Object
})

const filtros = reactive({
  empreendimento_id: props.filtros?.empreendimento_id || '',
  produto: props.filtros?.produto || '',
  status: props.filtros?.status || ''
})

watch(filtros, () => {
  router.get('/dav', filtros, {
    preserveState: true,
    replace: true
  })
})

function statusExibicao(status) {
  if (status === 'Pendente' && user.value?.role !== 'Fiscal') {
    return 'Em análise'
  }
  return status
}

function textoPrazo(prazo) {
  if (!prazo) return '-'
  if (prazo.situacao === 'pos_periodo') return 'Pós Período'

  return `${prazo.dias} ${prazo.dias === 1 ? 'dia' : 'dias'}`
}

function classePrazo(prazo) {
  if (!prazo) return ''
  return prazo.situacao === 'no_prazo' ? 'prazo-verde' : 'prazo-vermelho'
}

function possuiAviso(prazo) {
  return prazo && prazo.situacao !== 'no_prazo'
}

function formatarData(data) {
  if (!data) return '-'

  const [ano, mes, dia] = data.split('-')
  return `${dia}/${mes}/${ano}`
}

</script>

<template>
  <AuthenticatedLayout :user="user">

    <div class="container-fluid mt-4 px-4">

      <!-- CARD TÍTULO -->
      <div class="card shadow-sm border-0 card-header-dav mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">

          <h4 class="mb-0 fw-bold text-dark text-center w-100">
            DAV - Documento de Autorização de Viagem
          </h4>

          <Link
            href="/dav/create"
            class="btn btn-success btn-sm position-absolute end-0 me-4"
          >
            <i class="fas fa-plus me-1"></i>
            Novo DAV
          </Link>

        </div>
      </div>

      <!-- CARD FILTROS -->
      <div class="card shadow border-0 card-filtro mb-4">
        <div class="card-body">
          <div class="row align-items-end g-3">

            <!-- Empreendimento -->
            <div class="col-md-4">

              <label class="filtro-label-inline">
                Empreendimento
              </label>

              <select v-model="filtros.empreendimento_id" class="form-select filtro-select">

                <option value="">Todos</option>

                <option
                  v-for="e in empreendimentos"
                  :key="e.id"
                  :value="e.id"
                >
                  {{ e.cod_emp }}
                </option>

              </select>

            </div>


            <!-- Produto -->
            <div class="col-md-3">

              <label class="filtro-label-inline">
                Produto
              </label>

              <select v-model="filtros.produto" class="form-select filtro-select">

                <option value="">Todos</option>

                <option
                  v-for="p in produtos"
                  :key="p"
                  :value="p"
                >
                  {{ p }}
                </option>

              </select>
            </div>


            <!-- Status -->
            <div class="col-md-3">

              <label class="filtro-label-inline">
                Status
              </label>

              <select v-model="filtros.status" class="form-select filtro-select">

                <option value="">Todos</option>

                <option
                  v-for="s in statusList"
                  :key="s"
                  :value="s"
                >
                  {{ s }}
                </option>

              </select>

            </div>

            <!-- Limpar -->
            <div class="col-md-2">

              <button
                class="btn btn-outline-secondary w-100"
                @click="router.get('/dav')"
              >
                <i class="fas fa-eraser me-1"></i>
                Limpar
              </button>

            </div>
          </div>
        </div>
      </div>

      <!-- CARD LISTAGEM -->
      <div class="card shadow-sm border-0 card-dav">

        <div v-if="!davs.length" class="text-center text-muted py-4">

          Nenhum DAV cadastrado ainda.

          <div class="mt-3">
            <Link
              href="/dav/create"
              class="btn btn-success"
            >
              <i class="fas fa-plus me-1"></i>
              Cadastrar DAV
            </Link>
          </div>

        </div>


        <div v-else class="table-responsive shadow">

          <table class="table table-hover align-middle">

            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Empreendimento</th>
                <th>Produto</th>
                <th>Versão</th>
                <th>Status</th>
                <th>Prazo de Solicitação</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>

            <tbody>

              <template v-for="grupo in davs" :key="grupo[0].id">

                <!-- Linha título do grupo -->
                <tr class="linha-grupo">
                  <td colspan="7" class="grupo-titulo">
                    <strong>
                      DAV 
                    </strong>
                  </td>
                </tr>

                <!-- versões -->
                <tr
                  v-for="d in grupo"
                  :key="d.id"
                  class="linha-versao"
                >

                  <td class="versao-indent">-</td>

                  <td>{{ d.empreendimento?.cod_emp }}</td>

                  <td>{{ d.produto }}</td>

                  <td>
                    <span class="">
                      V{{ d.versao }}
                    </span>
                  </td>

                  <td>
                    <span
                      :class="{
                        'badge bg-warning text-black': d.status === 'Pendente',
                        'badge bg-success text-white': d.status === 'Aprovado',
                        'badge bg-danger text-white': d.status === 'Reprovado'
                      }"
                    >
                      {{ statusExibicao(d.status) }}
                    </span>
                  </td>

                  <td>
                    <div class="prazo-solicitacao">
                      <span :class="classePrazo(d.prazo_solicitacao)">
                        {{ textoPrazo(d.prazo_solicitacao) }}
                      </span>

                      <span
                        v-if="possuiAviso(d.prazo_solicitacao)"
                        class="prazo-info"
                        aria-label="Ver data da solicitação"
                      >
                        i
                        <span class="prazo-tooltip">
                          Solicitação registrada em {{ formatarData(d.prazo_solicitacao.data_solicitacao) }}. Início da viagem em {{ formatarData(d.prazo_solicitacao.data_viagem) }}.
                        </span>
                      </span>
                    </div>
                  </td>

                  <td class="text-end">
                    <Link
                      :href="`/dav/${d.id}`"
                      class="btn btn-sm btn-outline-primary"
                    >
                      Visualizar
                    </Link>
                  </td>

                </tr>

              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>


<style>

.card-dav{
  border-top:4px solid #198754;
  border-radius:10px;
}

.card-filtro{
  border-left:4px solid #198754;
  border-radius:7px;
}

.card-header-dav{
  border-top:4px solid #198754;
  border-radius:10px;
  position:relative;
}

.filtro-select{
  border-radius:8px;
  padding:8px 10px;
  border:1px solid #dee2e6;
  transition:all .15s;
}

.filtro-select:focus{
  border-color:#198754;
  box-shadow:0 0 0 0.15rem rgba(25,135,84,.2);
}

/* label alinhado com pequeno espaço */
.filtro-label-inline{
  display:block;
  font-weight:600;
  margin-bottom:6px;
  font-size:0.9rem;
}

/* linha título do grupo */
.linha-grupo{
  background:#e8ecef;
}

.grupo-titulo{
  font-size:0.85rem;
  font-weight:600;
  color:#495057;
  padding-top:6px !important;
  padding-bottom:6px !important;
  
}

/* leve separação entre grupos */
.linha-grupo + .linha-versao{
  border-top:2px solid #dee2e6;
}

/* identação das versões */
.versao-indent{
  padding-left:18px !important;
}

/* hover suave nas versões */
.linha-versao:hover{
  background:#f8f9fa;
}

.prazo-solicitacao {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  font-weight: 600;
}

.prazo-verde {
  color: #198754;
}

.prazo-vermelho {
  color: #dc3545;
}

.prazo-info {
  position: relative;
  width: 18px;
  height: 18px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: #0d6efd;
  color: #fff;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  line-height: 18px;
  text-align: center;
  cursor: help;
}

.prazo-tooltip {
  position: absolute;
  z-index: 10;
  bottom: calc(100% + 8px);
  left: 50%;
  display: none;
  width: 260px;
  padding: 7px 9px;
  border-radius: 5px;
  background: #212529;
  color: #fff;
  font: inherit;
  font-size: 12px;
  font-weight: 400;
  line-height: 1.3;
  text-align: left;
  white-space: normal;
  transform: translateX(-50%);
}

.prazo-tooltip::after {
  position: absolute;
  top: 100%;
  left: 50%;
  border: 5px solid transparent;
  border-top-color: #212529;
  content: '';
  transform: translateX(-50%);
}

.prazo-info:hover .prazo-tooltip {
  display: block;
}

</style>
