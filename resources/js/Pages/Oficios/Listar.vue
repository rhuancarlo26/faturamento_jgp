<script setup>
import { ref, onMounted, watch, defineProps } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import PizZip from 'pizzip';
import Docxtemplater from 'docxtemplater';
import { saveAs } from 'file-saver'; // ← ADICIONADO

defineProps({
    user: Object,
});

const oficios = ref([]);
const filtroRodovia = ref('');
const carregando = ref(false);
const flashSuccess = ref(null);

// 📌 Carregar ofícios
const carregarOficios = async () => {
    try {
        carregando.value = true;
        let url = '/oficios-lista';
        if (filtroRodovia.value) {
            url += `?rodovia=${encodeURIComponent(filtroRodovia.value)}`;
        }
        const response = await fetch(url);
        if (!response.ok) throw new Error('Erro ao carregar ofícios');
        oficios.value = await response.json();
    } catch (error) {
        console.error('Erro ao carregar ofícios:', error);
    } finally {
        carregando.value = false;
    }
};

// 📌 BAIXAR DOCX DIRETO DA LISTAGEM
const baixarOficio = async (oficio) => {
    try {
        // 1. Busca o modelo
        const response = await fetch('/Modelo_Oficio_Placeholders.docx');
        if (!response.ok) throw new Error('Modelo não encontrado');
        const arrayBuffer = await response.arrayBuffer();
        const zip = new PizZip(arrayBuffer);

        // 2. Cria o documento
        const doc = new Docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true,
            delimiters: { start: '[[', end: ']]' },
        });

        // 3. Dados do ofício
        const dataExtenso = new Date(oficio.data_registro).toLocaleDateString('pt-BR', {
            day: 'numeric', month: 'long', year: 'numeric'
        }).replace(/ de /g, ' de ');

        const processoSEI = {
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
        }[oficio.rodovia] || '';

        doc.setData({
            oficio_numero: oficio.oficio_num || '',
            data_oficio: dataExtenso,
            assunto: oficio.assunto || '',
            texto_oficio: (oficio.texto || '').replace(/\r\n|\r|\n/g, '\n'),
            processo_sei: processoSEI
        });

        doc.render();

        // 4. Gera o blob
        const blob = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        });

        // 5. Baixa
        const nomeLimpo = (oficio.oficio_num || 'oficio').replace(/[\/\\]/g, '-');
        saveAs(blob, `Oficio_${nomeLimpo}.docx`);

    } catch (error) {
        console.error('Erro ao gerar DOCX:', error);
        alert('Erro ao baixar o ofício. Verifique o modelo .docx.');
    }
};

onMounted(() => {
    carregarOficios();
});

watch(filtroRodovia, () => {
    carregarOficios();
});

// 📌 Novo ofício
const novoOficio = () => {
    Inertia.visit('/oficios');
};

// 📌 Logout
const logout = () => {
    Inertia.post('/logout', {}, { onSuccess: () => (window.location.href = '/subprodutos') });
};
</script>

<template>
    <div class="min-vh-100 bg-light">
        <!-- Barra superior -->
        <nav class="navbar navbar-dark" style="background-color: #3d85c6;">
            <div class="container-fluid d-flex align-items-center">
                <img src="/images/logo.jpg" alt="Logo" style="height: 40px; margin-right: 10px;">
                <span class="navbar-brand mb-0 h1">Sistema de Controle JGP - DNIT</span>
                <div class="dropdown ml-auto">
                    <span
                        class="navbar-text text-white dropdown-toggle d-flex align-items-center"
                        id="userDropdown"
                        data-toggle="dropdown"
                    >
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
                <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1600px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="font-weight-bold text-dark m-0">Ofícios Cadastrados</h3>
                        <button class="btn btn-primary" @click="novoOficio">
                            Novo Ofício
                        </button>
                    </div>

                    <!-- Alerta -->
                    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ flashSuccess }}
                        <button type="button" class="close" @click="flashSuccess = null" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <!-- Filtro -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="filtroRodovia" class="font-weight-semibold text-dark">Filtrar por BR</label>
                            <select v-model="filtroRodovia" id="filtroRodovia" class="form-control custom-select">
                                <option value="">Todas as rodovias</option>
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
                                <option value="BR-232 PE">BR-232 PE</option>
                                <option value="BR-230 PI/CE">BR-230 PI/CE</option>
                            </select>
                        </div>
                    </div>

                    <!-- Loader -->
                    <div v-if="carregando" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                    </div>

                    <!-- Tabela -->
                    <div v-else>
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 80px;">#</th>
                                    <th>Ofício nº</th>
                                    <th>Rodovia</th>
                                    <th>Assunto</th>
                                    <th>Data</th>
                                    <th style="width: 120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="oficios.length === 0">
                                    <td colspan="6" class="text-center text-muted">Nenhum ofício encontrado</td>
                                </tr>
                                <tr v-for="(oficio, index) in oficios" :key="oficio.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ oficio.oficio_num }}</td>
                                    <td>{{ oficio.rodovia }}</td>
                                    <td>{{ oficio.assunto }}</td>
                                    <td>{{ new Date(oficio.data_registro).toLocaleDateString('pt-BR') }}</td>
                                    <td class="text-center">
                                        <button 
                                            @click="baixarOficio(oficio)" 
                                            class="btn btn-sm btn-success mr-1"
                                            title="Baixar Word"
                                        >
                                            Baixar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@import 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css';
@import 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';

.table th, .table td { vertical-align: middle; }
thead.thead-light th { background-color: #f8f9fa; font-weight: 600; }
.custom-select { appearance: none; background-position: right 0.75rem center; background-size: 16px 12px; }

.btn-outline-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-color: #007bff;
    color: #007bff;
}
.btn-outline-primary:hover {
    background-color: #007bff;
    color: white;
}
.btn-outline-primary i { font-size: 1rem; }
</style>