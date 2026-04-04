// La ruta base se inyecta en build-time según el entorno (dev.env.js / prod.env.js)
const RUTA_GLOBAL = process.env.API_BASE || '/api/'

async function fetchConReintentos(url, opciones, reintentos = 2, esperaMs = 800) {
    for (let intento = 0; intento <= reintentos; intento++) {
        try {
            return await fetch(url, opciones);
        } catch (err) {
            const esRedTransitoria = err instanceof TypeError && (
                err.message === 'Failed to fetch' || err.message.includes('network')
            );
            if (esRedTransitoria && intento < reintentos) {
                await new Promise(r => setTimeout(r, esperaMs));
                continue;
            }
            throw err;
        }
    }
}

function getAuthHeaders() {
    const token = localStorage.getItem('jwt_token');
    let headers = { 'Content-Type': 'application/json' };
    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }
    return headers;
}

function manejar401() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('idUsuario');
    localStorage.removeItem('nombreUsuario');
    localStorage.removeItem('rol');
    localStorage.setItem('sesion_expirada', '1');
    window.location.href = '/';
}

const HttpService = {
    async registrar(datos, ruta){
        const respuesta = await fetchConReintentos(RUTA_GLOBAL + ruta, {
            method: "post",
            headers: getAuthHeaders(),
            body: JSON.stringify(datos),
        });
        if (respuesta.status === 401) { manejar401(); return null; }
        let resultado = await respuesta.json()
        return resultado
    },

    async obtenerConDatos(datos, ruta){
        const respuesta = await fetchConReintentos(RUTA_GLOBAL + ruta, {
            method: "post",
            headers: getAuthHeaders(),
            body: JSON.stringify(datos),
        });
        if (respuesta.status === 401) { manejar401(); return null; }
        let resultado = await respuesta.json()
        return resultado
    },

    async obtener(ruta){
        const respuesta = await fetchConReintentos(RUTA_GLOBAL + ruta, {
            method: "get",
            headers: getAuthHeaders(),
        });
        if (respuesta.status === 401) { manejar401(); return null; }
        let datos = await respuesta.json()
        return datos
    },

    async eliminar(ruta, id) {
        const respuesta = await fetchConReintentos(RUTA_GLOBAL + ruta, {
            method: "post",
            headers: getAuthHeaders(),
            body: JSON.stringify(id),
        });
        if (respuesta.status === 401) { manejar401(); return null; }
        let resultado = await respuesta.json()
        return resultado
    }
}

export default  HttpService