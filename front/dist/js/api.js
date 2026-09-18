const API = {
    urlBase: '/api',

    async request(endpoint, method = 'GET', data = null) {
        const opciones = {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        };
        if (data !== null && method !== 'GET') {
            opciones.body = JSON.stringify(data);
        }

        try {
            const respuesta = await fetch(this.urlBase + endpoint, opciones);
            let json = {};
            try {
                json = await respuesta.json();
            } catch (e) {
                json = { status: 'error', message: 'Respuesta inválida de la API' };
            }
            json.http = respuesta.status;
            if (respuesta.status === 401 && endpoint !== '/login' && endpoint !== '/me') {
                location.href = FRONT.login;
            }
            return json;
        } catch (error) {
            console.error('Error de conexión con la API:', error);
            return { status: 'error', message: 'No se pudo conectar con la API', http: 0 };
        }
    },

    get(endpoint) { return this.request(endpoint, 'GET'); },
        post(endpoint, data) { return this.request(endpoint, 'POST', data); },
    put(endpoint, data) { return this.request(endpoint, 'PUT', data); },
    del(endpoint) { return this.request(endpoint, 'DELETE'); },

    async postForm(endpoint, formData) {
        try {
            const respuesta = await fetch(this.urlBase + endpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            let json = {};
            try {
                json = await respuesta.json();
            } catch (e) {
                json = { status: 'error', message: 'Respuesta inválida de la API' };
            }
            json.http = respuesta.status;
            if (respuesta.status === 401 && endpoint !== '/login' && endpoint !== '/me') {
                location.href = FRONT.login;
            }
            return json;
        } catch (error) {
            console.error('Error de conexión con la API:', error);
            return { status: 'error', message: 'No se pudo conectar con la API', http: 0 };
        }
    }
};

const FRONT = {
    landing: '/front/index.html',
    login: '/front/dist/pages/login.html',
    rutina: '/front/dist/pages/rutina.html',
    dashboards: {
        administrador: '/front/dist/pages/dashboard-admin.html',
        entrenador: '/front/dist/pages/dashboard-entrenador.html',
        socio: '/front/dist/pages/dashboard-socio.html'
    }
};
