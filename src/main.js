// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'

import '@mdi/font/css/materialdesignicons.css'
import Buefy from 'buefy'
import 'buefy/dist/buefy.css'
import VueToastify from 'vue-toastify'
import router from './router'

Vue.use(Buefy)
Vue.use(VueToastify, {
  position: 'top-right',
  theme: 'light',
  successDuration: 3500,
  warningInfoDuration: 4500,
  errorDuration: 6000,
  defaultTitle: false
})

// Wrapper compatible con la API de $buefy.toast.open
Vue.prototype.$toast = function (options) {
  if (typeof options === 'string') {
    this.$vToastify.success(options)
    return
  }
  const { message, type = 'is-success' } = options
  if (type === 'is-success') this.$vToastify.success(message)
  else if (type === 'is-danger') this.$vToastify.error(message)
  else if (type === 'is-warning') this.$vToastify.warning(message)
  else this.$vToastify.info(message)
}

Vue.config.productionTip = false

// Filtro global de fechas: convierte 'YYYY-MM-DD HH:MM:SS' → 'DD-MM-YYYY HH:MM'
Vue.filter('formatFecha', function (f) {
    if (!f) return ''
    const s = f.replace('T', ' ').substring(0, 16)
    const [fecha, hora] = s.split(' ')
    if (!fecha) return f
    const [anio, mes, dia] = fecha.split('-')
    return `${dia}-${mes}-${anio}${hora ? ' ' + hora : ''}`
})

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  components: { App },
  template: '<App/>'
})
