import { defineStore, acceptHMRUpdate } from 'pinia'

const storedUser = () => {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}')
  } catch (error) {
    return {}
  }
}

export const useCounterStore = defineStore('counter', {
  state: () => ({
    counter: 0,
    user: storedUser(),
    isLogged: localStorage.getItem('tokenAsistencia') ? true : false,
  }),

  getters: {
    doubleCount: (state) => state.counter * 2
  },

  actions: {
    increment() {
      this.counter++
    }
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCounterStore, import.meta.hot))
}
