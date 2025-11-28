<script setup>
import { ref, onMounted, watch, defineProps } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import PizZip from 'pizzip';
import Docxtemplater from 'docxtemplater';
import { saveAs } from 'file-saver'; // ← ADICIONADO
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    user: Object,
});

const oficios = ref([]);
const filtroRodovia = ref('');
const carregando = ref(false);
const flashSuccess = ref(null);
const modalEditar = ref(false);
const editando = ref(null);
const modalUpload = ref(false);
const oficioUpload = ref(null);
const inputArquivo = ref(null);

const formEditar = ref({
    id: null,
    rodovia: '',
    assunto: '',
    texto_oficio: '',
    oficio_sede: false,
    oficio_dnit: false
});

const abrirModalEditar = (oficio) => {
    editando.value = oficio;

    formEditar.value = {
        id: oficio.id,
        rodovia: oficio.rodovia,
        assunto: oficio.assunto,
        texto_oficio: oficio.texto,
        oficio_sede: oficio.oficio_sede == 1,
        oficio_dnit: oficio.oficio_dnit == 1
    };

    modalEditar.value = true;
};

const salvarEdicao = async () => {
    try {
        const response = await fetch(`/oficios/${formEditar.value.id}`, {
            method: "PUT",
            headers: { 
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formEditar.value)
        });

        if (!response.ok) throw new Error("Erro ao atualizar");

        modalEditar.value = false;
        carregarOficios(); // recarrega tabela
    } catch (error) {
        alert("Erro ao salvar.");
        console.error(error);
    }
};

const abrirUpload = (oficio) => {
    oficioUpload.value = oficio;
    modalUpload.value = true;
};

const enviarArquivoPersonalizado = async () => {
    if (!inputArquivo.value.files.length) {
        alert("Selecione um arquivo .doc ou .docx");
        return;
    }

    const formData = new FormData();
    formData.append("arquivo", inputArquivo.value.files[0]);

    try {
        const response = await fetch(`/oficios/${oficioUpload.value.id}/upload-final`, {


            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        if (!response.ok) throw new Error("Erro ao enviar arquivo.");

        modalUpload.value = false;
        carregarOficios(); // atualizar tabela

    } catch (error) {
        alert("Erro ao enviar arquivo.");
        console.error(error);
    }
};


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

// 🔹 Modelo de ofício por usuário
const modelosPorUsuario = {
    4: '/Bruno_Modelo_Oficio_Placeholders.docx',     // Bruno
    5: '/Elenito_Modelo_Oficio_Placeholders.docx',   // Elenito
    6: '/Vinicius_Modelo_Oficio_Placeholders.docx',  // Vinícius
    8: '/Barco_Modelo_Oficio_Placeholders.docx',     // Guilherme Barco
    9: "/Juan_Modelo_Oficio_Placeholders.docx",     // Michelle Menezes
};

// 🔹 Modelo padrão (fallback)
const modeloPadrao = '/Modelo_Oficio_Placeholders.docx';


// 📌 BAIXAR DOCX DIRETO DA LISTAGEM
const baixarOficio = async (oficio) => {
    try {
        // 🔹 Escolher modelo com base no AUTOR do ofício
        const modelo = modelosPorUsuario[oficio.autor] ?? modeloPadrao;

        // 1. Busca o modelo certo
        const response = await fetch(modelo);
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
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });

        const processoSEI = {
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
        }[oficio.rodovia] || '';

        doc.setData({
            oficio_numero: oficio.oficio_num,
            data_oficio: dataExtenso,
            assunto: oficio.assunto,
            texto_oficio: (oficio.texto ?? '').replace(/\r\n|\r|\n/g, '\n'),
            processo_sei: processoSEI
        });

        doc.render();

        // 4. Gera o blob
        const blob = doc.getZip().generate({
            type: "blob",
            mimeType: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        });

        const nomeLimpo = oficio.oficio_num.replace(/[\/\\]/g, "-");
        saveAs(blob, `Oficio_${nomeLimpo}.docx`);

    } catch (error) {
        console.error("Erro ao gerar DOCX:", error);
        alert("Erro ao gerar o documento.");
    }
};


const baixar = (oficio) => {
    if (oficio.arquivo_personalizado && oficio.arquivo_personalizado !== "") {
        // Tem arquivo enviado → baixa do backend
        window.location.href = `/oficios/${oficio.id}/download`;
    } else {
        // Não tem arquivo → gera no front usando docxtemplater
        baixarOficio(oficio);
    }
};

const removerArquivo = async () => {
    if (!confirm("Tem certeza que deseja remover o arquivo enviado?")) return;

    try {
        const response = await fetch(`/oficios/${oficioUpload.value.id}/arquivo-personalizado`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) throw new Error("Erro ao remover arquivo.");

        // atualizar tabela
        modalUpload.value = false;
        carregarOficios();

    } catch (error) {
        alert("Erro ao remover arquivo.");
        console.error(error);
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
                <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1900px;">
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
                                    <th>Autor</th>
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
                                    <td>{{ oficio.autor_nome }}</td>
                                    <td class="text-center">
                                        <button 
                                            @click="baixar(oficio)"
                                            class="btn btn-sm"
                                            style="color: white; background-color: #28a745;"
                                            title="Baixar"
                                        >
                                            <i class="fas fa-download"></i>
                                        </button>

                                        <button
                                            v-if="oficio.autor === user.id"
                                            @click="abrirModalEditar(oficio)"
                                            class="btn btn-sm"
                                            style="color: white; background-color: #f0ad4e;"
                                            title="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button 
                                            v-if="oficio.autor === user.id"
                                            @click="abrirUpload(oficio)"
                                            class="btn btn-sm"
                                            style="color: white; background-color: #6f42c1;"
                                            title="Enviar arquivo personalizado"
                                        >
                                            <i class="fas fa-file-upload"></i>
                                        </button>

                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- MODAL EDITAR -->
        <div
            class="modal fade"
            :class="{ show: modalEditar }"
            v-show="modalEditar"
            style="display: block;"
            tabindex="-1"
            role="dialog"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                <div class="modal-header" 
                    style="background-color: #4b79a1; color: white; justify-content: center;">

                    <h5 class="modal-title m-0">EDITAR OFÍCIO</h5>

                    <button type="button" class="close position-absolute" style="right: 15px; color:white;"
                        @click="modalEditar = false">
                        &times;
                    </button>
                </div>


                    <div class="modal-body">
                        <!-- Rodovia -->
                        <div class="form-group">
                            <label class="font-weight-bold">Rodovia</label>
                            <select v-model="formEditar.rodovia" class="form-control">
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
                                <option value="BR-316 PI (km 33,54 ao km 55,60)">
                                    BR-316 PI (km 33,54 ao km 55,60)
                                </option>
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

                        <!-- Assunto -->
                        <div class="form-group">
                            <label class="font-weight-bold">Assunto</label>
                            <textarea rows="6" v-model="formEditar.assunto" class="form-control" ></textarea>
                        </div>

                        <!-- Texto -->
                        <div class="form-group">
                            <label class="font-weight-bold">Texto do Ofício</label>
                            <textarea rows="6" v-model="formEditar.texto_oficio" class="form-control"></textarea>
                        </div>

                        <!-- SEDE / DNIT -->
                        <div class="form-group d-flex">
                            <div class="custom-control custom-checkbox mr-4">
                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="editOficioSede"
                                    v-model="formEditar.oficio_sede"
                                />
                                <label class="custom-control-label" for="editOficioSede">Ofício SEDE</label>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="editOficioDnit"
                                    v-model="formEditar.oficio_dnit"
                                />
                                <label class="custom-control-label" for="editOficioDnit">Ofício DNIT</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="modalEditar = false">Cancelar</button>
                        <button class="btn btn-warning text-white" @click="salvarEdicao">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- backdrop do modal -->
        <div class="modal-backdrop fade show" v-if="modalEditar"></div>

        <!-- MODAL UPLOAD -->
        <div 
            class="modal fade" 
            :class="{ show: modalUpload }" 
            v-show="modalUpload"
            style="display:block;"
        >
            <div class="modal-dialog modal-md modal-dialog-centered"> <!-- AUMENTEI DE modal-sm → modal-md -->
                <div class="modal-content">

                    <div class="modal-header" style="background:#6f42c1; color:white; justify-content:center;">
                        <h5 class="modal-title m-0">Enviar Word</h5>
                        <button type="button" class="close" style="color:white;" @click="modalUpload = false">
                            &times;
                        </button>
                    </div>

                    <div class="modal-body">

                        <!-- SE EXISTE ARQUIVO MOSTRA A LIXEIRA -->
                        <div v-if="oficioUpload?.arquivo_personalizado" 
                            class="alert alert-info d-flex justify-content-between align-items-center">
                            <span>Já existe um arquivo enviado.</span>

                            <button class="btn btn-danger btn-sm" @click="removerArquivo">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <label class="font-weight-bold">Selecione o arquivo</label>
                        <input type="file" ref="inputArquivo" class="form-control" accept=".doc,.docx">
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" @click="modalUpload = false">Cancelar</button>
                        <button class="btn btn-primary btn-sm" @click="enviarArquivoPersonalizado">Enviar</button>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal-backdrop fade show" v-if="modalUpload"></div>



    </div>
    
</template>

<style>
@import 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css';
@import 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';

.table th,
.table td {
    vertical-align: middle;
}
thead.thead-light th {
    background-color: #f8f9fa;
    font-weight: 600;
}
.custom-select {
    appearance: none;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
}

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
.btn-outline-primary i {
    font-size: 1rem;
}
</style>