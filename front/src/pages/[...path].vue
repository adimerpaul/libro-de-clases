<template>
  <div class="fullscreen notFound text-center q-pa-md flex flex-center">
    <div>
      <div class="code">404</div>
      <div class="text-h5 q-mt-sm">Esta página no existe</div>
      <q-btn class="q-mt-xl" color="accent" unelevated no-caps to="/" label="Volver al inicio" />
    </div>
  </div>
</template>

<style scoped>
.notFound { background: linear-gradient(135deg, #082039, #113958 50%, #481722); color: #fff }
.code { font-size: 30vh; font-weight: 800; line-height: 1; letter-spacing: -.05em }
</style>
