document.getElementById('form-login').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const error = document.getElementById('error');
    error.style.display = 'none';

    const res = await API.post('/login', {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value
    });

    if (res.status !== 'ok' || !res.usuario || !res.usuario.rol) {
        const fallback = (typeof I18n !== 'undefined' && I18n.api)
            ? I18n.api(res.message, 'login_fail')
            : (res.message || 'No se pudo iniciar sesión');
        error.textContent = fallback;
        error.style.display = 'block';
        return;
    }
    AuthUI.irSegunRol(res.usuario);
});
