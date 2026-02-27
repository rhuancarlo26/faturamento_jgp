

<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()

const user = computed(() => page.props.auth?.user)
const success = computed(() => page.props.flash?.success)

const props = defineProps({
  davs: Array
})


</script>

<template>
  <AuthenticatedLayout :user="user">

    <div class="container-fluid mt-4 px-4">
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


      
      <div v-else class="table-responsive">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
          <h4 class="mb-0 fw-bold text-dark text-center">
           DAV - Documento de Autorização de Viagem
          </h4>

          <Link
            href="/dav/create"
            class="btn btn-success btn-sm"
          >
            <i class="fas fa-plus me-1"></i>
            Novo DAV
          </Link>
        </div>
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>OSE</th>
              <th>Coordenador</th>
              <th>Status</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in davs" :key="d.id">
              <td>{{ d.id }}</td>
              <td>{{ d.n_ose }}</td>
              <td>{{ d.coordenador }}</td>
              <td>
                <span
                  :class="{
                    'badge bg-warning': d.status === 'Pendente',
                    'badge bg-success': d.status === 'Aprovado',
                    'badge bg-danger': d.status === 'Reprovado'
                  }"
                >
                  {{ d.status }}
                </span>
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
          </tbody>
        </table>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>

<style>
.card-dav {
  border-top: 4px solid #198754;
  border-radius: 10px;
}

</style>