<template>
  <b-dropdown position="is-bottom-left" aria-role="menu" :mobile-modal="false">
    <template #trigger>
      <a class="button is-light" title="Cambiar tema">
        <b-icon icon="palette" size="is-small"></b-icon>
      </a>
    </template>

    <b-dropdown-item custom aria-role="menuitem">
      <p class="has-text-weight-bold has-text-grey is-size-7" style="min-width:160px">TEMA DE COLOR</p>
    </b-dropdown-item>

    <b-dropdown-item
      v-for="(tema, clave) in temas"
      :key="clave"
      aria-role="menuitem"
      @click="seleccionar(clave)"
    >
      <div class="is-flex is-align-items-center" style="gap:8px">
        <span style="display:inline-flex; border-radius:4px; overflow:hidden; border:1px solid #ccc; width:24px; height:14px; flex-shrink:0">
          <span :style="{ background: tema.navbar, flex: 1 }"></span>
          <span :style="{ background: tema.fondo, flex: 1 }"></span>
        </span>
        <span>{{ tema.icono }} {{ tema.nombre }}</span>
        <b-icon v-if="temaActual === clave" icon="check" size="is-small" type="is-success"></b-icon>
      </div>
    </b-dropdown-item>
  </b-dropdown>
</template>

<script>
import TemaService from '../../Servicios/TemaService'

export default {
  name: 'SelectorTema',
  data() {
    return {
      temas: TemaService.TEMAS,
      temaActual: TemaService.temaGuardado(),
    }
  },
  methods: {
    seleccionar(clave) {
      TemaService.aplicarTema(clave)
      this.temaActual = clave
    },
  },
}
</script>
