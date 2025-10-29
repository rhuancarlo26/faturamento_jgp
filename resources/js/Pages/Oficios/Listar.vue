<script setup>
import { ref, onMounted, watch, defineProps } from 'vue';
import { Inertia } from '@inertiajs/inertia';

defineProps({
    user: Object,
});

const oficios = ref([]);
const filtroRodovia = ref('');
const carregando = ref(false);
const flashSuccess = ref(null);

// 📌 Carregar ofícios com ou sem filtro
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

// 📌 Carrega ao montar
onMounted(() => {
    carregarOficios();
});

// 📌 Atualiza ao mudar filtro
watch(filtroRodovia, () => {
    carregarOficios();
});

// 📌 Redirecionar para formulário de novo ofício
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
                        aria-haspopup="true"
                        aria-expanded="false"
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
                        <a
                            class="nav-link text-uppercase font-weight-bold"
                            style="color: #4B5563; font-size: 0.9rem;"
                            href="/subprodutos"
                        >
                            <i class="fas fa-search mr-2" style="color: #007BFF;"></i> CONSULTAR SUBPRODUTOS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link text-uppercase font-weight-bold"
                            style="color: #4B5563; font-size: 0.9rem;"
                            href="/subprodutos/create"
                        >
                            <i class="fas fa-plus-circle mr-2" style="color: #28A745;"></i> CADASTRAR SUBPRODUTOS
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a
                            class="nav-link text-uppercase font-weight-bold"
                            style="color: #007BFF; font-size: 0.9rem;"
                            href="/oficios-listar"
                        >
                            <i class="fas fa-file-alt mr-2" style="color: #007BFF;"></i> OFÍCIOS
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Conteúdo principal -->
            <div class="flex-grow-1 p-4">
                <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1600px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="font-weight-bold text-dark m-0">📄 Ofícios Cadastrados</h3>
                        <button class="btn btn-primary" @click="novoOficio">
                            <i class="fas fa-plus mr-2"></i> Novo Ofício
                        </button>
                    </div>

                    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ flashSuccess }}
                        <button type="button" class="close" @click="flashSuccess = null" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Filtros -->
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
                                        <a
                                            :href="`/oficios/pdf/${oficio.id}`"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Baixar PDF"
                                        >
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
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

.btn-outline-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-outline-danger i {
    font-size: 1rem;
}
</style>
