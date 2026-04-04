const TEMAS = {
  morado: {
    nombre: 'Morado',
    icono: '🟣',
    primario: '#7957d5',
    primarioOscuro: '#6248b5',
    primarioClaro: '#e8e2fa',
    navbar: '#7957d5',
    pie: '#2e1a6e',
    fondo: '#f5f0ff',
  },
  rojo: {
    nombre: 'Rojo',
    icono: '🔴',
    primario: '#c0392b',
    primarioOscuro: '#a93226',
    primarioClaro: '#fde8e6',
    navbar: '#922b21',
    pie: '#3d1008',
    fondo: '#fff5f5',
  },
  azul: {
    nombre: 'Azul',
    icono: '🔵',
    primario: '#1a6eb5',
    primarioOscuro: '#155a94',
    primarioClaro: '#dbeeff',
    navbar: '#1a3a5c',
    pie: '#0a1e33',
    fondo: '#f0f7ff',
  },
  verde: {
    nombre: 'Verde',
    icono: '🟢',
    primario: '#27ae60',
    primarioOscuro: '#1e8449',
    primarioClaro: '#d5f5e3',
    navbar: '#1e8449',
    pie: '#0a2e1a',
    fondo: '#f0fff7',
  },
  naranja: {
    nombre: 'Naranja',
    icono: '🟠',
    primario: '#e67e22',
    primarioOscuro: '#ca6f1e',
    primarioClaro: '#fdebd0',
    navbar: '#a04000',
    pie: '#2c1500',
    fondo: '#fff9f0',
  },
  oscuro: {
    nombre: 'Oscuro',
    icono: '⚫',
    primario: '#5d6d7e',
    primarioOscuro: '#4a5568',
    primarioClaro: '#d5d8dc',
    navbar: '#1a252f',
    pie: '#0d1117',
    fondo: '#e8ecf0',
  },
  rosa: {
    nombre: 'Rosa',
    icono: '🩷',
    primario: '#e91e8c',
    primarioOscuro: '#c0166f',
    primarioClaro: '#fce4f3',
    navbar: '#ad1457',
    pie: '#560027',
    fondo: '#fff0f8',
  },
}

const CLAVE = 'botanero_tema'

function aplicarTema(clave) {
  const tema = TEMAS[clave] || TEMAS.morado
  const root = document.documentElement
  root.style.setProperty('--color-primario', tema.primario)
  root.style.setProperty('--color-primario-oscuro', tema.primarioOscuro)
  root.style.setProperty('--color-primario-claro', tema.primarioClaro)
  root.style.setProperty('--color-navbar', tema.navbar)
  root.style.setProperty('--color-pie', tema.pie)
  root.style.setProperty('--color-fondo', tema.fondo)
  localStorage.setItem(CLAVE, clave)
}

function temaGuardado() {
  return localStorage.getItem(CLAVE) || 'morado'
}

function init() {
  aplicarTema(temaGuardado())
}

export default { TEMAS, aplicarTema, temaGuardado, init }
