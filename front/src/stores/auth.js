import { defineStore, acceptHMRUpdate } from 'pinia'

export const TOKEN_KEY = 'tokenLibro'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isLogged: false,
    user: {},
  }),

  getters: {
    hasPermission: (state) => (perm) => {
      const permisos = state.user.permisos || []
      if (Array.isArray(perm)) return perm.some(p => permisos.includes(p))
      return permisos.includes(perm)
    },
  },

  actions: {
    setSesion ({ user }) {
      this.isLogged = true
      this.user = user
    },
    logout () {
      localStorage.removeItem(TOKEN_KEY)
      this.isLogged = false
      this.user = {}
    },
  },
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useAuthStore, import.meta.hot))
}
