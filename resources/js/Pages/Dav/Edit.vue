<script setup>
import Form from './Form.vue'

defineProps({
  dav: Object,
  empreendimentos: Array,
  produtos: Array,
  profissionais: Array
})
</script>

<template>
  <Form
    :dav="dav"
    :empreendimentos="empreendimentos"
    :produtos="produtos"
    :profissionais="profissionais"
  />
</template>