<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';

defineProps({
    user: Object,
});

const form = useForm({
    rodovia: '',
    data: '',
    oficio_dnit: false,
    oficio_sede: false,
    modelo_oficio: '',
    assunto: '',
    texto_oficio: '',
});

const flashSuccess = ref(null);

const submit = () => {
    form.post('/oficios', {
        onSuccess: (page) => {
            form.reset();
            flashSuccess.value = page.props.flash?.success || 'Ofício salvo e PDF gerado!';
        },
        onError: (errors) => {
            console.log('Erros de validação:', errors);
        },
    });
};

const logout = () => {
    form.post('/logout', {
        onSuccess: () => {
            window.location.href = '/subprodutos';
        },
    });
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
                        <a class="dropdown-item" href="#" @click.prevent="logout">Sair</a>
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
                    <li class="nav-item active">
                        <a class="nav-link text-uppercase font-weight-bold" style="color: #007BFF; font-size: 0.9rem;" href="/oficios">
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
                                <label for="data" class="form-label font-weight-semibold text-dark">Data</label>
                                <input type="date" v-model="form.data" id="data" class="form-control">
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
                            <div class="form-group col-md-6 mb-3">
                                <label for="modeloOficio" class="form-label font-weight-semibold text-dark">Escolher Ofício Modelo</label>
                                <select v-model="form.modelo_oficio" id="modeloOficio" class="form-control custom-select">
                                    <option value="">Escolher Ofício Modelo</option>
                                    <option value="modelo1">Modelo 1</option>
                                    <option value="modelo2">Modelo 2</option>
                                    <option value="modelo3">Modelo 3</option>
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
                            <button type="button" class="btn btn-outline-primary mr-2">Visualizar Ofício</button>
                            <button type="button" class="btn btn-danger mr-2" @click.prevent="form.reset()">Cancelar</button>
                            <button type="submit" class="btn btn-success">Salvar e Gerar PDF</button>
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