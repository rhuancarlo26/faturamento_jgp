<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { ref, onMounted, defineProps } from 'vue';

// Definir props explicitamente
const props = defineProps({
    subprodutos: Array,
    dataInicial: String,
    dataFinal: String,
    user: Object,
});

const form = useForm({
    data_inicial: '',
    data_final: '',
});

const flashSuccess = ref(null);

const submit = () => {
    form.get('/subprodutos', { preserveState: true });
};

const clear = () => {
    form.reset();
    form.get('/subprodutos');
};

const deleteSubproduto = (id) => {
    if (confirm('Tem certeza que deseja excluir este subproduto?')) {
        form.delete(`/subprodutos/${id}`, {
            onSuccess: (page) => {
                flashSuccess.value = page.props.flash?.success || 'Subproduto excluído!';
            },
            onFinish: () => {
                form.reset();
            },
        });
    }
};

const logout = () => {
    form.post('/logout', {
        onSuccess: () => {
            window.location.href = '/subprodutos';
        },
    });
};

// Função para baixar a tabela em Excel
const downloadExcel = () => {
    if (typeof XLSX === 'undefined') {
        console.error('XLSX library not loaded');
        return;
    }
    const worksheet = XLSX.utils.json_to_sheet(props.subprodutos.map(item => ({
        Rodovia: item.rodovia,
        'Prod. Subproduto': item.id_subproduto,
        'Cod.SIAC': item.cod_siac,
        'Descrição do Serviço': item.subproduto,
        Quantidade: item.quantidade,
        Unidade: item.unidade,
        'Qtd. Medida': item.quantidade_medida,
        'SEI Versão Aprovada': item.sei_versao_aprovada,
        'Parecer Técnico/Portaria/Ofício Aprovação - SEI': item.oficio_numero
    })));
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Subprodutos');
    XLSX.writeFile(workbook, 'subprodutos.xlsx');
};

// Carregar a biblioteca XLSX quando o componente for montado
onMounted(() => {
    if (!window.XLSX) {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.async = true;
        script.onload = () => console.log('XLSX loaded successfully');
        script.onerror = () => console.error('Failed to load XLSX library');
        document.head.appendChild(script);
    }
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
                        {{ user?.name || 'Visitante' }}
                    </span>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a v-if="user" class="dropdown-item" href="#" @click.prevent="logout">Sair</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Layout principal -->
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
                </ul>
            </div>

            <!-- Conteúdo principal -->
            <div class="flex-grow-1 p-4">
                <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1690px;">
                    <h3 class="text-center mb-4 font-weight-bold" style="color: #4B5563; font-size: 1.3rem;">CONSULTAR SUBPRODUTOS APROVADOS</h3>
                    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ flashSuccess }}
                        <button type="button" class="close" @click="flashSuccess = null" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="submit" class="form-row mb-5 p-4 bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="form-group col-md-4">
                            <label class="form-label font-weight-semibold">Data Inicial</label>
                            <input type="date" v-model="form.data_inicial" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label font-weight-semibold">Data Final</label>
                            <input type="date" v-model="form.data_final" class="form-control">
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2" :disabled="form.processing">CONSULTAR</button>
                            <button type="type" class="btn btn-outline-secondary" @click="clear">LIMPAR</button>
                        </div>
                    </form>
                    <h3 class="text-center mb-3 font-weight-bold" style="color: #4B5563; font-size: 1.25rem;">RELAÇÃO DOS SUBPRODUTOS APROVADOS</h3>
                    <div class="mb-3 text-right">
                        <button @click="downloadExcel" class="btn btn-success">
                            <i class="fas fa-file-excel mr-2"></i> Baixar
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center align-middle">Rodovia</th>
                                    <th class="text-center align-middle">Prod. Subproduto</th>
                                    <th class="text-center align-middle">Cod.SIAC</th>
                                    <th class="text-center align-middle">Descrição do Serviço</th>
                                    <th class="text-center align-middle">Quantidade</th>
                                    <th class="text-center align-middle">Unidade</th>
                                    <th class="text-center align-middle">Qtd. Medida</th>
                                    <th class="text-center align-middle">SEI Versão Aprovada</th>
                                    <th class="text-center align-middle">Parecer Técnico/Portaria/Ofício Aprovação - SEI</th>
                                    <th v-if="user" class="text-center align-middle">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="subproduto in subprodutos" :key="subproduto.id">
                                    <td class="text-center align-middle">{{ subproduto.rodovia }}</td>
                                    <td class="text-center align-middle">{{ subproduto.id_subproduto || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.cod_siac || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.subproduto || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.quantidade || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.unidade || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.quantidade_medida || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.sei_versao_aprovada || '-' }}</td>
                                    <td class="text-center align-middle">{{ subproduto.oficio_numero || '-' }}</td>
                                    <td v-if="user" class="text-center align-middle">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <button class="btn btn-danger btn-sm mr-1" @click="deleteSubproduto(subproduto.id)">Excluir</button>
                                            <a :href="`/subprodutos/${subproduto.id}/edit`" class="btn btn-warning btn-sm">Editar</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!subprodutos.length">
                                    <td :colspan="user ? 10 : 9" class="text-center align-middle">Nenhum subproduto encontrado.</td>
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

/* Ajustes personalizados para a tabela */
.table td {
    vertical-align: middle !important;
    text-align: center !important;
}

.table th {
    vertical-align: middle !important;
    text-align: center !important;
}

/* Ajustes para os botões na coluna de ações */
.action-buttons {
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-buttons .btn {
    white-space: nowrap;
    padding: 0.25rem 0.5rem;
    margin: 0 0.25rem;
}

.action-buttons .btn:first-child {
    margin-left: 0;
}

.action-buttons .btn:last-child {
    margin-right: 0;
}
</style>