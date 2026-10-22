<template>
  <UsuariosAdmin solo-docentes />
</template>

<script setup>
import UsuariosAdmin from '../../../components/UsuariosAdmin.vue'

definePage({ meta: { title: 'Docentes', permiso: 'docentes.ver' } })
</script>
