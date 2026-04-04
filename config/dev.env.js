'use strict'
const merge = require('webpack-merge')
const prodEnv = require('./prod.env')

module.exports = merge(prodEnv, {
  NODE_ENV: '"development"',
  API_BASE: '"/api/"',  // webpack proxy redirige a /botanero-ventas/api/
  SSE_BASE: '"http://localhost/botanero-ventas/api/"'  // SSE va directo a WAMP (el proxy no soporta streams)
})
