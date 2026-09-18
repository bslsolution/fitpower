document.getElementById('form-login').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const error = document.getElementById('error');
    error.style.display = 'none';

    const res = await API.post('/login', {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value
    });

    if (res.status !== 'ok') {
        error.textContent = I18n.api(res.message, 'login_fail');
        error.style.display = 'block';
        return;
    }
    AuthUI.irSegunRol(res.usuario);
});
