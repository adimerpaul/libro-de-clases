<template>
  <UsuariosAdmin />
</template>

<script setup>
import UsuariosAdmin from '../../../components/UsuariosAdmin.vue'

definePage({ meta: { title: 'Usuarios y permisos', permiso: 'usuarios.ver' } })
</script>
