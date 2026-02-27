<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'

const props = defineProps({
  user: Object,
  dav: Object,
  resumo: Object,

})

const statusClass = computed(() => {
  if (props.dav.status === 'Pendente') return 'bg-warning'
  if (props.dav.status === 'Aprovado') return 'bg-success'
  if (props.dav.status === 'Reprovado') return 'bg-danger'
  return ''
})

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-BR')
}

function saldoClass(valor) {
    if (valor < 0) {
        return 'text-red-600 font-bold'
    }
    return 'text-gray-800'
}

</script>

<template>
  <AuthenticatedLayout :user="user">

    <div class="document-wrapper">
      <div class="document">

        <!-- CABEÇALHO -->
        <div class="doc-header">
          <a :href="`/dav/${dav.id}/download`" class="btn-download">
            Baixar PDF
          </a>

          <h2>DOCUMENTO DE AUTORIZAÇÃO DE VIAGEM</h2>

          <div class="doc-status" :class="statusClass">
            {{ dav.status }}
          </div>
        </div>

        <!-- DADOS GERAIS -->
        <div class="section">
          <div class="section-title">Dados Gerais</div>

          <!-- Linha 1 -->
          <div class="grid-2">
            <div class="campo-linha">
              <strong>Coordenador:</strong>
              <span>{{ dav.coordenador }}</span>
            </div>

            <div class="campo-linha">
              <strong>Nº OSE:</strong>
              <span>{{ dav.n_ose }}</span>
            </div>
          </div>

          <!-- Linha 2 -->
          <div class="grid-2 mt">
            <div class="campo-linha">
              <strong>Empreendimento:</strong>
              <span>{{ dav.empreendimento?.cod_emp }}</span>
            </div>

            <div class="campo-linha">
              <strong>Produto:</strong>
              <span>{{ dav.produto }}</span>
            </div>
          </div>

          <!-- Linha 3 -->
          <div class="mt">
            <div class="campo-linha">
              <strong>Subproduto:</strong>
              <span>{{ dav.subproduto }}</span>
            </div>
          </div>
        </div>

        <!-- PROFISSIONAIS -->
        <div
          v-for="(prof, index) in dav.profissionais"
          :key="prof.id"
          class="section profissional-bloco"
        >
          <div class="section-title">
            Profissional {{ index + 1 }}
          </div>

          <div class="grid-3">

            <div class="campo-linha">
              <strong>Nome:</strong>
              <span>{{ prof.profissional?.nome }}</span>
            </div>

            <div class="campo-linha">
              <strong>Formação:</strong>
              <span>{{ prof.profissional?.formacao }}</span>
            </div>

            <div class="campo-linha">
              <strong>Função:</strong>
              <span>{{ prof.funcao }}</span>
            </div>

            <div class="campo-linha">
              <strong>Período:</strong>
              <span>
                {{ formatDate(prof.data_ini) }} até
                {{ formatDate(prof.data_fim) }}
              </span>
            </div>

            <div class="campo-linha">
              <strong>Diárias:</strong>
              <span>{{ prof.diarias }}</span>
            </div>

          </div>

          <!-- TRECHOS -->
          <div class="mt trechos-bloco">

            <div class="trechos-titulo">
              Trechos da Viagem
            </div>

            <div
              v-for="(trecho, tIndex) in prof.trechos"
              :key="trecho.id || tIndex"
              class="trecho-item"
            >
              <div class="trecho-numero">
                {{ tIndex + 1 }}
              </div>

              <div class="trecho-conteudo">

                <div class="trecho-dados">

                  <div class="campo-linha">
                    <strong>Origem:</strong>
                    <span>{{ trecho.origem }}</span>
                  </div>

                  <div class="campo-linha">
                    <strong>Destino:</strong>
                    <span>{{ trecho.destino }}</span>
                  </div>

                </div>

                <div class="transporte mt" v-if="
                  trecho.aereo_qtd ||
                  trecho.aquatico_qtd ||
                  trecho.terrestre_pickup_qtd ||
                  trecho.terrestre_hatch_qtd
                ">

                  <div v-if="trecho.aereo_qtd">
                    ✈ Aéreo: {{ trecho.aereo_qtd }}
                  </div>

                  <div v-if="trecho.aquatico_qtd">
                    🚢 Aquático: {{ trecho.aquatico_qtd }}
                  </div>

                  <div v-if="trecho.terrestre_pickup_qtd">
                    🚗 Pickup: {{ trecho.terrestre_pickup_qtd }}
                  </div>

                  <div v-if="trecho.terrestre_hatch_qtd">
                    🚙 Hatch: {{ trecho.terrestre_hatch_qtd }}
                  </div>

                </div>

              </div>
            </div>
          </div>
        </div>

        <div class="section resumo-bloco">
          <div class="section-title">
            Quadro Resumo
          </div>

          <div class="resumo-table-wrapper">

            <table class="resumo-table">
              <thead>
                <tr>
                  <th></th>

                  <th>
                    <div class="th-coluna">
                      <span class="codigo">14.1.1</span>
                      <span class="titulo">Diárias</span>
                    </div>
                  </th>

                  <th>
                    <div class="th-coluna">
                      <span class="codigo">14.1.3</span>
                      <span class="titulo">Passagem Aérea</span>
                    </div>
                  </th>

                  <th>
                    <div class="th-coluna">
                      <span class="codigo">14.1.7</span>
                      <span class="titulo">Veículo Aquático</span>
                    </div>
                  </th>

                  <th>
                    <div class="th-coluna">
                      <span class="codigo">14.1.5</span>
                      <span class="titulo">Veículo Hatch</span>
                    </div>
                  </th>

                  <th>
                    <div class="th-coluna">
                      <span class="codigo">14.1.6</span>
                      <span class="titulo">Veículo Pickup</span>
                    </div>
                  </th>

                </tr>
              </thead>

              <tbody>
                <tr>
                  <td class="linha-titulo">Solicitado nesta DAV</td>
                  <td>{{ resumo.totais.diarias }}</td>
                  <td>{{ resumo.totais.aereo }}</td>
                  <td>{{ resumo.totais.aquatico }}</td>
                  <td>{{ resumo.totais.hatch }}</td>
                  <td>{{ resumo.totais.pickup }}</td>
                </tr>

                <tr>
                  <td class="linha-titulo">Saldo no momento da emissão</td>
                  <td :class="saldoClass(dav.diarias_total)">
                    {{ dav.diarias_total }}
                  </td>
                  <td :class="saldoClass(dav.aereo_total)">
                    {{ dav.aereo_total }}
                  </td>
                  <td :class="saldoClass(dav.aquatico_total)">
                    {{ dav.aquatico_total }}
                  </td>
                  <td :class="saldoClass(dav.hatch_total)">
                    {{ dav.hatch_total }}
                  </td>
                  <td :class="saldoClass(dav.pickup_total)">
                    {{ dav.pickup_total }}
                  </td>
                </tr>

              </tbody>

            </table>
          </div>
        </div>

        <!-- ASSINATURA -->
        <div class="section signature">

          <div class="assinatura-bloco">
            <div class="signature-line"></div>
            <div>Assinatura do Responsável</div>
          </div>

          <div class="data-emissao">
            <strong>Data de Emissão:</strong>
            {{ formatDate(dav.created_at) }}
          </div>

        </div>

      </div>
    </div>

  </AuthenticatedLayout>
</template>

<style scoped>

/* CAMPO EM LINHA */
.campo-linha {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.campo-linha strong {
  font-size: 11px;
  text-transform: uppercase;
  color: #666;
  letter-spacing: 0.5px;
}

.campo-linha span {
  font-size: 14px;
  color: #333;
}

.transporte {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 15px;
  font-size: 13px;
  color: #333;
}

.trecho-conteudo {
  width: 100%;
}

</style>

<style scoped>

.document-wrapper {
  display: flex;
  justify-content: center;
  padding: 40px 0;
  background: #f1f1f1;
}

.document {
  width: 90%;
  max-width: 1200px; /* deixa bem mais larga na tela */
  min-height: 297mm;
  background: white;
  padding: 60px;
  box-shadow: 0 0 30px rgba(0,0,0,0.08);
  font-family: 'Segoe UI', sans-serif;
  color: #333;
  border-radius: 10px;
}

/* CABEÇALHO */
.doc-header {
  text-align: center;
  border-bottom: 3px solid #198754;
  padding-bottom: 20px;
  margin-bottom: 40px;
  position: relative;
}

.doc-header h2 {
  font-size: 20px;
  letter-spacing: 1px;
  font-weight: 700;
}

.doc-status {
  position: absolute;
  top: 0;
  right: 0;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.bg-warning {
  background: #ffc107;
  color: #000;
}

.bg-success {
  background: #198754;
  color: white;
}

.bg-danger {
  background: #dc3545;
  color: white;
}

/* SEÇÕES */
.section {
  margin-bottom: 40px;
  padding: 25px;
  border: 1px solid #e3e3e3;
  border-radius: 8px;
  background: #fafafa;
}

.profissional-bloco {
  border-left: 5px solid #198754;
}

.section-title {
  font-weight: 700;
  margin-bottom: 20px;
  font-size: 15px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #198754;
  border-bottom: 1px solid #ddd;
  padding-bottom: 8px;
}

/* GRIDS */
.grid-2, .grid-3, .grid-4 {
  display: grid;
  gap: 25px;
}

.grid-2 { grid-template-columns: 1fr 1fr; }
.grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

.mt {
  margin-top: 20px;
}

/* CAMPOS */
.section strong {
  font-size: 13px;
  text-transform: uppercase;
  color: #666;
  letter-spacing: 0.5px;
  display: block;
}

.section div > div {
  margin-top: 6px;
  font-size: 14px;
}

/* ASSINATURA */
.signature {
  margin-top: 80px;
  text-align: center;
  background: white;
}

.assinatura-bloco {
  margin-bottom: 25px;
}

.signature-line {
  border-top: 1px solid #000;
  width: 300px;
  margin: 0 auto 8px;
}

.data-emissao {
  font-size: 13px;
  color: #444;
}

/* IMPRESSÃO */
@media print {

  body {
    background: white;
  }

  .document-wrapper {
    padding: 0;
  }

  .document {
    width: 210mm;
    max-width: none;
    min-height: 297mm;
    box-shadow: none;
    border-radius: 0;
    padding: 40px;
  }

}

.btn-download {
  position: absolute;
  left: 0;
  top: 0;
  background: #198754;
  color: white;
  padding: 6px 14px;
  border-radius: 4px;
  text-decoration: none;
  font-size: 12px;
}

/* TRECHOS */
.trechos-bloco {
  margin-top: 25px;
}

.trechos-titulo {
  font-weight: 600;
  margin-bottom: 15px;
  font-size: 3px;
  text-transform: uppercase;
  color: #444;
}

.trecho-item {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 15px;
  background: white;
  border: 1px solid #e3e3e3;
  border-radius: 6px;
  margin-bottom: 12px;
}

.trecho-numero {
  background: #198754;
  color: white;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 13px;
}

.trecho-dados {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 25px;
  width: 100%;
}

/* =========================
   QUADRO RESUMO
========================= */

.resumo-bloco {
  border-left: 5px solid #0d6efd;
}

.resumo-table-wrapper {
  overflow-x: auto;
}

.resumo-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
  font-size: 14px;
}

.resumo-table th {
  background: #e9ecef;
  padding: 12px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #555;
  border: 1px solid #ddd;
}

.resumo-table td {
  padding: 12px;
  text-align: center;
  vertical-align: middle;
  border: 1px solid #ddd;
}

.linha-titulo {
  text-align: left !important;
  font-weight: 600;
  background: #f8f9fa;
  width: 220px;
}

/* Cabeçalho em duas linhas */
.th-coluna {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1.2;
}

.codigo {
  font-size: 12px;
  font-weight: 700;
  color: #455e85;
  margin-bottom: 2px;
}

.titulo {
  font-size: 12px;
  font-weight: 600;
  color: #555;
  text-align: center;
}

</style>