<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { ref, onMounted, defineProps, computed, watch } from 'vue';

import PizZip from 'pizzip';
import Docxtemplater from 'docxtemplater';
import { saveAs } from 'file-saver';

defineProps({
    user: Object,
});

const form = useForm({
    rodovia: '',
    data_oficio: new Date().toISOString().split('T')[0],
    oficio_dnit: false,
    oficio_sede: false,
    modelo_oficio: '',
    assunto: '',
    texto_oficio: '',
    oficio_num: ''
});

const flashSuccess = ref(null);
const oficiosSalvos = ref([]);


const generateOficioNumero = computed(() => {
    const ano = new Date().getFullYear();
    const tipo = form.oficio_dnit ? '02' : form.oficio_sede ? '01' : '';
    const sequencia = 154; 
    const rodovia = form.rodovia ? form.rodovia.replace(/[\s/-]/g, '') : '';
    return tipo ? `OF_JGP.${tipo}.${sequencia}/${ano}_${rodovia}` : '';
});

const submit = () => {
    form.oficio_num = generateOficioNumero.value;

    form.post('/oficios', {
        onSuccess: (page) => {
            form.reset();
            flashSuccess.value = page.props.flash?.success || 'Ofício salvo e PDF gerado!';
            carregarOficiosSalvos();
        },
        onError: (errors) => {
            console.log('Erros de validação:', errors);
        },
    });
};

const formatarDataPorExtenso = (isoDate) => {
    const meses = [
        'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
    ];
    const partes = isoDate.split('-');
    const ano = parseInt(partes[0]);
    const mes = meses[parseInt(partes[1]) - 1];
    const dia = parseInt(partes[2]);
    return `${dia} de ${mes} de ${ano}`;
};

const gerarDocumento = async () => {
    try {
        const response = await fetch('/Modelo_Oficio_Placeholders.docx');
        if (!response.ok) throw new Error('Não foi possível carregar o modelo .docx');

        const arrayBuffer = await response.arrayBuffer();
        const zip = new PizZip(arrayBuffer);
        const doc = new Docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true,
            delimiters: { start: '[[', end: ']]' },
        });

        doc.setData({
            assunto: form.assunto ?? '',
            texto_oficio: (form.texto_oficio ?? '').replace(/\r\n|\r|\n/g, '\n'),
            oficio_numero: generateOficioNumero.value ?? '',
            data_oficio: formatarDataPorExtenso(form.data_oficio),
        });

        doc.render();

        const out = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        });

        saveAs(out, `Oficio-${Date.now()}.docx`);
    } catch (error) {
        console.error('Erro ao gerar documento:', error);

        if (error.properties && Array.isArray(error.properties.errors)) {
            error.properties.errors.forEach((e, i) => {
                console.error(`[Docxtemplater Error ${i + 1}] id: ${e.properties?.id}`);
                console.error(`[Docxtemplater Error ${i + 1}] explanation: ${e.properties?.explanation}`);
                console.error(`[Docxtemplater Error ${i + 1}] message: ${e.properties?.message}`);
            });
        }

        alert('Erro ao gerar o documento. Verifique os placeholders no modelo.');
    }
};

const logout = () => {
    form.post('/logout', {
        onSuccess: () => {
            window.location.href = '/subprodutos';
        },
    });
};

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
});

watch(() => form.oficio_sede, (newValue) => {
    if (newValue) form.oficio_dnit = false;
});
watch(() => form.oficio_dnit, (newValue) => {
    if (newValue) form.oficio_sede = false;
});
</script>

<template>
    <div class="min-vh-100 bg-light">
        <!-- Barra superior -->
        <nav class="navbar navbar-dark" style="background-color: #3d85c6;">
            <div class="container-fluid d-flex align-items-center">
                <img src="/images/logo.jpg" alt="Logo" style="height: 40px; margin-right: 10px;">
                <span class="navbar-brand mb-0 h1">Sistema de Controle JGP - DNIT</span>
                <div class="dropdown ml-auto">
                    <span class="navbar-text text-white dropdown-toggle d-flex align-items-center" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                        <a class="nav-link text-uppercase font-weight-bold" href="/subprodutos">
                            <i class="fas fa-search mr-2" style="color: #007BFF;"></i> CONSULTAR SUBPRODUTOS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-uppercase font-weight-bold" href="/subprodutos/create">
                            <i class="fas fa-plus-circle mr-2" style="color: #28A745;"></i> CADASTRAR SUBPRODUTOS
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link text-uppercase font-weight-bold" href="/oficios">
                            <i class="fas fa-file-alt mr-2" style="color: #007BFF;"></i> OFÍCIOS
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Conteúdo principal -->
            <div class="flex-grow-1 p-4">
                <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1400px;">
                    <h3 class="text-center mb-4 font-weight-bold text-dark">OFÍCIOS</h3>

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
                                <input type="text" v-model="generateOficioNumero" id="oficioNumero" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="card mb-4 border-light shadow-sm" style="background: #f8f9fa;">
                            <div class="card-body p-3">
                                <label for="oficioAnterior" class="form-label font-weight-semibold text-dark">
                                    <i class="fas fa-copy mr-2" style="color: #007BFF;"></i> Escolher Ofício Modelo
                                </label>
                                <select id="oficioAnterior" class="form-control custom-select" @change="preencherOficio($event.target.value)">
                                    <option value="">Selecione um modelo</option>
                                    <option v-for="o in oficiosSalvos" :key="o.id" :value="o.id">
                                        {{ o.oficio_num }}
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
                            <button type="button" class="btn btn-outline-primary mr-2" @click="gerarDocumento">Gerar Documento</button>
                            <button type="button" class="btn btn-danger mr-2" @click.prevent="form.reset()">Cancelar</button>
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
</style>
