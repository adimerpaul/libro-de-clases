<template>
  <div />
</template>

<script setup>
import { getCurrentInstance } from 'vue'
import { MODULOS_ADMIN } from '../../../composables/adminModulos'

// /admin abre la primera pestaña que el usuario puede ver
definePage({ meta: { title: 'Administrador' } })

const { proxy } = getCurrentInstance()
const primera = MODULOS_ADMIN.find(m => proxy.$auth.hasPermission(`${m.modulo}.ver`))
proxy.$router.replace(primera ? primera.to : '/')
</script>
