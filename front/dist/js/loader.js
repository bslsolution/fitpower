const FitLoader = {
    started: Date.now(),

    hide() {
        const el = document.getElementById('fp-loader');
        if (!el || el.classList.contains('is-done')) return;
        const wait = Math.max(0, 450 - (Date.now() - this.started));
        setTimeout(() => el.classList.add('is-done'), wait);
    }
};

window.addEventListener('load', () => {
    if (document.body.classList.contains('landing-page') || document.body.classList.contains('login-page')) {
        FitLoader.hide();
    }
});
