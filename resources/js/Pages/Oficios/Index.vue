<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { ref, onMounted, defineProps, computed, watch, nextTick } from 'vue';
import PizZip from 'pizzip';
import Docxtemplater from 'docxtemplater';
import { renderAsync } from 'docx-preview';
import { saveAs } from 'file-saver';


const { user } = defineProps({
  user: Object,
});

const doc = ref(null); // ← adicione isso no topo

const form = useForm({
  rodovia: '',
  data_oficio: new Date().toISOString().split('T')[0],
  oficio_dnit: false,
  oficio_sede: false,
  modelo_oficio: '',
  assunto: '',
  texto_oficio: '',
  oficio_num: '',
  contador: null,
});

const processosPorBR = {
  'CT-94-2022': '50600.011613/2022-03',
  'BR-230/MA': '50600.010066/2018-54',
  'BR-437 CE/RN': '50600.003544/2020-94',
  'BR-402 MA/PI': '50600.029435/2022-69',
  'BR-116 CE': '50603.002112/2022-06',
  'BR-020 GO/BA': '50600.010068/2018-43',
  'BR-304 RN': '50614.001281/2015-62',
  'BR-316 PI': '50618.000831/2023-04',
  'BR-104 RN': '50614.000423/2024-65',
  'BR-030 BA': '50600.032816/2023-14',
  'BR-122 BA': '50605.000071/2019-90',
  'BR-316 PI (km 33,54 ao km 55,60)': '50618.000831/2023-04',
  'BR-110/316/PE': '50600.043127/2022-46',
  'BR-349/SE/AL': '50600.036707/2023-68',
  'BR-135/BA': '50600.510964/2017-27',
  'BR-324/BA': '50605.002443/2024-80',
  'BR-316/MA': '50600.034479/2024-72',
  'BR-226/CE': '50603.001120/2024-99',
  'BR-010/MA': '50600.033749/2024-28',
  'BR-104/AL': '50600.005357/2025-50',
  'BR-222/CE': '50600.034578/2024-54'
};

const processoSEI = computed(() => processosPorBR[form.rodovia] || '');

const flashSuccess = ref(null);
const oficiosSalvos = ref([]);
const precisaContadorManual = ref(false);
const mostrarModalContador = ref(false);
const contadorManualTemp = ref(null);
const mostrarModalVisualizacao = ref(false);
const docxContainer = ref(null);

// 📌 Contador sequencial
const carregarProximoContador = async (ano) => {
  try {

    // Determina o tipo com base nos checkboxes
    let tipo = null;
    if (form.oficio_sede) tipo = "sede";
    if (form.oficio_dnit) tipo = "dnit";

    // Se não escolheu sede nem dnit → não faz nada
    if (!tipo) {
      form.contador = null;
      return;
    }

    const res = await fetch(`/oficios/ultimo-contador?ano=${ano}&tipo=${tipo}`);
    if (!res.ok) throw new Error('Falha ao consultar contador');

    const data = await res.json();

    // Backend já devolve o último contador adequado ao tipo
    form.contador = Number(data.ultimo_contador) + 1;

    precisaContadorManual.value = false;
    mostrarModalContador.value = false;

  } catch (e) {
    console.error(e);
    form.contador = null;
  }
};


const confirmarContadorManual = () => {
  if (!contadorManualTemp.value || contadorManualTemp.value < 1) return;
  form.contador = Number(contadorManualTemp.value);
  mostrarModalContador.value = false;
};

// 📌 Número de ofício automático
const generateOficioNumero = computed(() => {
  const ano = new Date(form.data_oficio).getFullYear();
  const tipo = form.oficio_dnit ? '02' : form.oficio_sede ? '01' : '';
  const seq = form.contador ? String(form.contador) : '__';

  // ⚡ EXCEÇÃO → CT-94-2022 formatação especial
  if (form.rodovia === 'CT-94-2022') {
    return tipo
      ? `OF_JGP.${tipo}.${seq}/${ano}-CT-94-2022`
      : `OF_JGP.__.${seq}/${ano}-CT-94-2022`;
  }

  // Rodovias normais → sanitiza
  const rodoviaSan = form.rodovia ? form.rodovia.replace(/[\s\/-]/g, '') : '';

  return tipo
    ? `OF_JGP.${tipo}.${seq}/${ano}${rodoviaSan ? '_' + rodoviaSan : ''}`
    : '';
});


// 📌 Gerar e visualizar DOCX
const formatarDataPorExtenso = (isoDate) => {
  const meses = [
    'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
    'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
  ];
  const [yyyy, mm, dd] = isoDate.split('-');
  return `${parseInt(dd)} de ${meses[parseInt(mm) - 1]} de ${yyyy}`;
};

const gerarDocumento = async () => {
  try {
    // 🔥 Seleciona o modelo conforme o usuário
    const modelos = {
      4: "/Bruno_Modelo_Oficio_Placeholders.docx",
      5: "/Elenito_Modelo_Oficio_Placeholders.docx",
      6: "/Vinicius_Modelo_Oficio_Placeholders.docx",
      8: "/Barco_Modelo_Oficio_Placeholders.docx",
      9: "/Juan_Modelo_Oficio_Placeholders.docx",
      10: "/Adriana_Modelo_Oficio_Placeholders.docx",
    };

    // se não estiver listado → usa o padrão
    const modelo = modelos[user.id] || "/Modelo_Oficio_Placeholders.docx";

    // 📌 Agora carrega o modelo correto
    const response = await fetch(modelo);
    if (!response.ok) throw new Error("Modelo não encontrado");

    const arrayBuffer = await response.arrayBuffer();
    const zip = new PizZip(arrayBuffer);

    doc.value = new Docxtemplater(zip, {
      paragraphLoop: true,
      linebreaks: true,
      delimiters: { start: "[[", end: "]]" },
    });

    doc.value.setData({
      assunto: form.assunto ?? "",
      texto_oficio: (form.texto_oficio ?? "").replace(/\r\n|\r|\n/g, "\n"),
      oficio_numero: generateOficioNumero.value ?? "",
      data_oficio: formatarDataPorExtenso(form.data_oficio),
      processo_sei: processoSEI.value ?? "",
    });

    doc.value.render();

    const out = doc.value.getZip().generate({
      type: "blob",
      mimeType: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    });

    mostrarModalVisualizacao.value = true;
    await nextTick();
    if (docxContainer.value) {
      docxContainer.value.innerHTML = "";
      renderAsync(out, docxContainer.value, null, { className: "docx" });
    }
  } catch (error) {
    console.error("Erro:", error);
    alert("Erro ao gerar documento.");
  }
};


const baixarDocumento = () => {
  if (!doc.value) {
    alert('Gere o documento primeiro!');
    return;
  }

  const out = doc.value.getZip().generate({
    type: 'blob',
    mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  });

  const nomeArquivo = `Oficio_${generateOficioNumero.value || 'sem_numero'}.docx`;
  saveAs(out, nomeArquivo);
};

// 📌 Salvar no banco (agora no modal)
const salvarOficio = () => {
  form.oficio_num = generateOficioNumero.value;
  form.post('/oficios', {
    onSuccess: () => {
      window.location.href = '/oficios-listar';
    },
    onError: (errors) => {
      console.log('Erros de validação:', errors);
    },
  });
};

// 📌 Ofícios modelo
const carregarOficiosSalvos = async () => {
  try {
    const response = await fetch('/oficios-lista');
    if (!response.ok) throw new Error('Erro ao carregar ofícios salvos');
    oficiosSalvos.value = await response.json();
  } catch (error) {
    console.error('Erro ao carregar ofícios:', error);
  }
};

const preencherOficio = async (id) => {
  if (!id) return;
  try {
    const response = await fetch(`/oficios/${id}`);
    if (!response.ok) throw new Error('Erro ao carregar ofício selecionado');
    const data = await response.json();
    form.assunto = data.assunto;
    form.texto_oficio = data.texto;
  } catch (error) {
    console.error('Erro ao preencher ofício:', error);
  }
};

onMounted(() => {
  carregarOficiosSalvos();
  carregarProximoContador(new Date(form.data_oficio).getFullYear());
});

watch(() => form.oficio_sede, (v) => {
  if (v) {
    form.oficio_dnit = false;
    carregarProximoContador(new Date(form.data_oficio).getFullYear());
  } else {
    form.contador = null;
  }
});

watch(() => form.oficio_dnit, (v) => {
  if (v) {
    form.oficio_sede = false;
    carregarProximoContador(new Date(form.data_oficio).getFullYear());
  } else {
    form.contador = null;
  }
});

watch(() => form.oficio_dnit, (v) => {
  if (v) {
    form.oficio_sede = false;
    carregarProximoContador(new Date(form.data_oficio).getFullYear());
  } else {
    form.contador = null;
  }
});



// watch(() => form.oficio_sede, (v) => { if (v) form.oficio_dnit = false; });
// watch(() => form.oficio_dnit, (v) => { if (v) form.oficio_sede = false; });
// watch(() => form.data_oficio, (newVal, oldVal) => {
//   if (!newVal) return;
//   const yNew = new Date(newVal).getFullYear();
//   const yOld = oldVal ? new Date(oldVal).getFullYear() : yNew;
//   if (yNew !== yOld) carregarProximoContador(yNew);
// });
</script>

<template>
  <div class="min-vh-100 bg-light">
    <!-- Barra superior -->
    <nav class="navbar navbar-dark" style="background-color: #3d85c6;">
      <div class="container-fluid d-flex align-items-center">
        <img src="/images/logo.jpg" alt="Logo" style="height: 40px; margin-right: 10px;">
        <span class="navbar-brand mb-0 h1">Sistema de Controle JGP - DNIT</span>
        <div class="dropdown ml-auto">
          <span class="navbar-text text-white dropdown-toggle d-flex align-items-center" id="userDropdown" data-toggle="dropdown">
            <i class="fas fa-user-circle mr-2" style="font-size: 1.5rem;"></i>
            {{ user?.name || 'N/A' }}
          </span>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#" @click.prevent="logout">Sair</a>
          </div>
        </div>
      </div>
    </nav>

    <div class="d-flex">
      <!-- Menu lateral -->
      <div class="bg-white border-right shadow-sm" style="width: 250px; min-height: calc(100vh - 56px);">
        <ul class="nav flex-column p-3">
          <li class="nav-item">
            <a class="nav-link text-uppercase font-weight-bold" style="color: #4B5563; font-size: 0.9rem;" href="/subprodutos">
              <i class="fas fa-search mr-2" style="color: #007BFF;"></i> CONSULTAR SUBPRODUTOS
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-uppercase font-weight-bold" style="color: #4B5563; font-size: 0.9rem;" href="/subprodutos/create">
              <i class="fas fa-plus-circle mr-2" style="color: #28A745;"></i> CADASTRAR SUBPRODUTOS
            </a>
          </li>
          <li class="nav-item active">
            <a class="nav-link text-uppercase font-weight-bold" style="color: #4B5563; font-size: 0.9rem;" href="/oficios-listar">
              <i class="fas fa-file-alt mr-2" style="color: #007BFF;"></i> OFÍCIOS
            </a>
          </li>
        </ul>
      </div>

      <!-- Conteúdo principal -->
      <div class="flex-grow-1 p-4">
        <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1900px;">
          <h3 class="text-center mb-4 font-weight-bold text-dark">CADASTRAR OFÍCIOS</h3>

          <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="close" @click="flashSuccess = null" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <form @submit.prevent="submit" class="needs-validation" novalidate>
            <div class="form-row align-items-center">
              <div class="form-group col-md-4 mb-3">
                <label for="rodovia" class="form-label font-weight-semibold text-dark">Rodovia</label>
                <select v-model="form.rodovia" id="rodovia" class="form-control custom-select">
                  <option value="">Escolher rodovia</option>
                  <option value="CT-94-2022">CT-94-2022</option>
                  <option value="BR-230/MA">BR-230/MA</option>
                  <option value="BR-437 CE/RN">BR-437 CE/RN</option>
                  <option value="BR-402 MA/PI">BR-402 MA/PI</option>
                  <option value="BR-116 CE">BR-116 CE</option>
                  <option value="BR-020 GO/BA">BR-020 GO/BA</option>
                  <option value="BR-304 RN">BR-304 RN</option>
                  <option value="BR-316 PI">BR-316 PI</option>
                  <option value="BR-104 RN">BR-104 RN</option>
                  <option value="BR-030 BA">BR-030 BA</option>
                  <option value="BR-122 BA">BR-122 BA</option>
                  <option value="BR-316 PI (km 33,54 ao km 55,60)">BR-316 PI (km 33,54 ao km 55,60)</option>
                  <option value="BR-110/316/PE">BR-110/316/PE</option>
                  <option value="BR-349/SE/AL">BR-349/SE/AL</option>
                  <option value="BR-135/BA">BR-135/BA</option>
                  <option value="BR-324/BA">BR-324/BA</option>
                  <option value="BR-316/MA">BR-316/MA</option>
                  <option value="BR-226/CE">BR-226/CE</option>
                  <option value="BR-010/MA">BR-010/MA</option>
                  <option value="BR-104/AL">BR-104/AL</option>
                  <option value="BR-222/CE">BR-222/CE</option>
                  <option value="BR-423, BR-424, BR-316 PE/AL">BR-423, BR-424, BR-316 PE/AL</option>
                  <option value="BR-232 PE">BR-232 PE</option>
                  <option value="BR-407, BR-324 BA">BR-407, BR-324 BA</option>
                  <option value="BR-230 PI/CE">BR-230 PI/CE</option>
                </select>
              </div>
              <div class="form-group col-md-4 mb-3">
                <label for="data_oficio" class="form-label font-weight-semibold text-dark">Data</label>
                <input type="date" v-model="form.data_oficio" id="data_oficio" class="form-control" readonly>
              </div>
            </div>

            <div class="form-row align-items-center">
              <div class="form-group col-md-6 mb-3 d-flex align-items-center">
                <div class="custom-control custom-checkbox mr-3">
                  <input type="checkbox" v-model="form.oficio_sede" id="oficioSede" class="custom-control-input">
                  <label for="oficioSede" class="custom-control-label text-dark">Ofício SEDE</label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" v-model="form.oficio_dnit" id="oficioDnit" class="custom-control-input">
                  <label for="oficioDnit" class="custom-control-label text-dark">Ofício DNIT</label>
                </div>
              </div>
            </div>

            <div class="form-row align-items-center">
              <div class="form-group col-md-6 mb-3">
                <label for="oficioNumero" class="form-label font-weight-semibold text-dark">Ofício nº</label>
                <input
                  type="text"
                  v-model="generateOficioNumero"
                  id="oficioNumero"
                  class="form-control"
                  readonly
                >
              </div>
            </div>

            <div class="card mb-4 border-light shadow-sm" style="background: #f8f9fa;">
              <div class="card-body p-3">
                <label for="oficioAnterior" class="form-label font-weight-semibold text-dark">
                  <i class="fas fa-copy mr-2" style="color: #007BFF;"></i> Escolher Ofício Modelo
                </label>
                <select id="oficioAnterior" class="form-control custom-select" @change="preencherOficio($event.target.value)">
                  <option value="">Selecione um modelo</option>
                  <option 
                    v-for="o in oficiosSalvos" 
                    :key="o.id" 
                    :value="o.id"
                    :title="o.oficio_num + ' - ' + o.assunto"
                  >
                    <span class="font-weight-bold">{{ o.oficio_num }}</span> - 
                    {{ o.assunto.length > 160 ? o.assunto.substring(0, 160) + '...' : o.assunto }}
                  </option>

                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-12 mb-3">
                <label for="assunto" class="form-label font-weight-semibold text-dark">Assunto</label>
                <textarea v-model="form.assunto" id="assunto" class="form-control" rows="3" placeholder="Assunto do ofício"></textarea>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-12 mb-3">
                <label for="textoOficio" class="form-label font-weight-semibold text-dark">Texto do Ofício</label>
                <textarea v-model="form.texto_oficio" id="textoOficio" class="form-control" rows="8" placeholder="Texto do ofício"></textarea>
              </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-danger mr-2" @click.prevent="form.reset()">
                    Cancelar
                </button>
                <button type="button" class="btn btn-outline-primary mr-2" @click="gerarDocumento">
                    Avançar para Visualização
                </button>

            </div>

          </form>
        </div>
      </div>
    </div>


  <div 
      class="modal fade" 
      :class="{ show: mostrarModalContador }" 
      v-show="mostrarModalContador"
      style="z-index: 1050; display: block;"
      aria-modal="true" role="dialog"
    >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Número inicial da sequência</h5>
        </div>
        <div class="modal-body">
          <p class="text-dark mb-3">
            Este é o primeiro ofício gerado neste ano.
            Informe o número inicial da sequência. Os demais serão gerados automaticamente.
          </p>
          <input type="number" v-model.number="contadorManualTemp" min="1" class="form-control" placeholder="Ex: 1 ou 221" />
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="mostrarModalContador = false">Cancelar</button>
          <button class="btn btn-primary" @click="confirmarContadorManual">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" v-show="mostrarModalContador" style="z-index: 1040;"></div>

  <!-- 🔹 Novo modal de visualização -->
  <div 
      class="modal fade" 
      :class="{ show: mostrarModalVisualizacao }" 
      v-show="mostrarModalVisualizacao"
      style="z-index:1060; display:block;"
      aria-modal="true" role="dialog"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Visualizar Ofício</h5>
            <button type="button" class="close text-white" @click="mostrarModalVisualizacao = false">&times;</button>
          </div>
          <div class="modal-body" style="height:80vh; overflow:auto;">
            <div ref="docxContainer" class="p-3 bg-white rounded shadow-sm"></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="mostrarModalVisualizacao = false">Voltar</button>
            <button class="btn btn-success" @click="salvarOficio">Salvar Ofício</button>
            <!-- <button class="btn btn-primary mr-2" @click="baixarDocumento">
              Baixar DOCX
            </button> -->
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-show="mostrarModalVisualizacao" style="z-index:1055;"></div>

  </div>
</template>

<style>
@import 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css';
@import 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';

.custom-select {
  appearance: none;
  background-position: right 0.75rem center;
  background-size: 16px 12px;
}

.custom-control-input:checked ~ .custom-control-label::before {
  background-color: #007bff;
  border-color: #007bff;
}

.custom-control-label::before {
  border: 1px solid #ced4da;
  background-color: #fff;
}

.custom-control-label {
  margin-left: 0.5rem;
  color: #4B5563;
}

.form-row {
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.btn {
  min-width: 120px;
  text-align: center;
}

.modal {
  overflow: auto;
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.4);
}
</style>
