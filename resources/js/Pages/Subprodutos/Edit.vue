<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    subproduto: Object,
    subprodutoOptions: Array,
    user: Object,
});

const form = useForm({
    rodovia: props.subproduto?.rodovia || '',
    data_aprovacao: props.subproduto?.data_aprovacao || '',
    sei: props.subproduto?.sei || '',
    oficio_numero: props.subproduto?.oficio_numero || '',
    sei_versao_aprovada: props.subproduto?.sei_versao_aprovada || '',
    subproduto: props.subproduto?.subproduto || '',
    cod_siac: props.subproduto?.cod_siac || '',
});

const flashSuccess = ref(null);
const selectedSubproduto = ref(props.subproduto?.subproduto || '');

// Atualizar o formulário se os props mudarem
watch(() => props.subproduto, (newSubproduto) => {
    console.log('Subproduto recebido:', newSubproduto);
    if (newSubproduto && Object.keys(newSubproduto).length > 0) {
        form.rodovia = newSubproduto.rodovia || '';
        form.data_aprovacao = newSubproduto.data_aprovacao || '';
        form.sei = newSubproduto.sei || '';
        form.oficio_numero = newSubproduto.oficio_numero || '';
        form.sei_versao_aprovada = newSubproduto.sei_versao_aprovada || '';
        form.subproduto = newSubproduto.subproduto || '';
        form.cod_siac = newSubproduto.cod_siac || '';
        selectedSubproduto.value = newSubproduto.subproduto || '';
    } else {
        console.error('Subproduto inválido ou vazio:', newSubproduto);
    }
}, { immediate: true });

const fetchSubproduto = async (subproduto) => {
    if (subproduto) {
        try {
            const response = await fetch('/subprodutos/fetch/' + encodeURIComponent(subproduto));
            const data = await response.json();
            form.subproduto = data.subproduto || subproduto;
            form.cod_siac = data.cod_siac || '';
        } catch (error) {
            console.log('Erro na requisição:', error);
        }
    }
};

const submit = () => {
    if (props.subproduto && props.subproduto.id) {
        form.put(`/subprodutos/${props.subproduto.id}`, {
            onSuccess: (page) => {
                flashSuccess.value = page.props.flash?.success || 'Subproduto atualizado!';
            },
            onError: (errors) => {
                console.log('Erros de validação:', errors);
            },
        });
    } else {
        console.error('ID do subproduto não encontrado');
    }
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
                    <span class="navbar-text text-white dropdown-toggle d-flex align-items-center" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle mr-2" style="font-size: 1.5rem;"></i>
                        {{ user?.name || 'N/A' }}
                    </span>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a v-if="user" class="dropdown-item" href="#" @click.prevent="$inertia.post('/logout', {}, { onSuccess: () => window.location.href = '/subprodutos' })">Sair</a>
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
                <div class="container-fluid bg-white rounded-lg shadow p-4" style="max-width: 1400px;">
                    <h3 class="text-center mb-4 font-weight-bold" style="color: #4B5563; font-size: 1.3rem;">Editar Subproduto</h3>
                    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ flashSuccess }}
                        <button type="button" class="close" @click="flashSuccess = null" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="submit" class="needs-validation" novalidate>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">Ofício Nº</label>
                            <input type="text" v-model="form.oficio_numero" class="form-control" placeholder="Digite o número do ofício">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">SEI</label>
                            <input type="text" v-model="form.sei" class="form-control" placeholder="Digite o SEI">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">Data</label>
                            <input type="date" v-model="form.data_aprovacao" class="form-control" required>
                            <div class="invalid-feedback">Por favor, selecione uma data.</div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">Rodovia</label>
                            <select v-model="form.rodovia" class="form-control" required>
                                <option value="">Selecione</option>
                                <option value="BR-230/MA">01 - BR-230/MA</option>
                                <option value="BR-437 CE/RN">02 - BR-437 CE/RN</option>
                                <option value="BR-402 MA/PI">03 - BR-402 MA/PI</option>
                                <option value="BR-116 CE">04 - BR-116 CE</option>
                                <option value="BR-020 GO/BA">05 - BR-020 GO/BA</option>
                                <option value="BR-304 RN">06 - BR-304 RN</option>
                                <option value="BR-316 PI">07 - BR-316 PI</option>
                                <option value="BR-104 RN">08 - BR-104 RN</option>
                                <option value="BR-030 BA">09 - BR-030 BA</option>
                                <option value="BR-122 BA">10 - BR-122 BA</option>
                                <option value="BR-316 PI (km 33,54 ao km 55,60)">11 - BR-316 PI (km 33,54 ao km 55,60)</option>
                                <option value="BR-110/316/PE">12 - BR-110/316/PE</option>
                                <option value="BR-349/SE/AL">13 - BR-349/SE/AL</option>
                                <option value="BR-135/BA">14 - BR-135/BA</option>
                                <option value="BR-324/BA">15 - BR-324/BA</option>
                                <option value="BR-316/MA">16 - BR-316/MA</option>
                                <option value="BR-226/CE">17 - BR-226/CE</option>
                                <option value="BR-010/MA">18 - BR-010/MA</option>
                                <option value="BR-104/AL">19 - BR-104/AL</option>
                                <option value="BR-222/CE">20 - BR-222/CE</option>
                                <option value="BR-423, BR-424, BR-316 PE/AL">21 - BR-423, BR-424, BR-316 PE/AL</option>
                                <option value="BR-232 PE">22 - BR-232 PE</option>
                                <option value="BR-407, BR-324 BA">23 - BR-407, BR-324 BA</option>
                                <option value="BR-230 PI/CE">24 - BR-230 PI/CE</option>
                            </select>
                            <div class="invalid-feedback">Por favor, selecione uma rodovia.</div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">Subproduto</label>
                            <select v-model="selectedSubproduto" @change="fetchSubproduto(selectedSubproduto)" class="form-control" required>
                                <option value="">Selecione</option>
                                <option v-for="option in subprodutoOptions" :key="option.subproduto" :value="option.subproduto">
                                    {{ option.subproduto + ' - ' + option.descricao_revisada }}
                                </option>
                            </select>
                            <div class="invalid-feedback">Por favor, selecione um subproduto.</div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">Código SIAC</label>
                            <input type="text" v-model="form.cod_siac" class="form-control bg-light" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-semibold">SEI - Versão Aprovada</label>
                            <input type="text" v-model="form.sei_versao_aprovada" class="form-control" placeholder="Digite o SEI da versão aprovada">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary mr-2" :disabled="form.processing">Salvar</button>
                            <a href="/subprodutos" class="btn btn-outline-secondary">Cancelar</a>
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
</style>