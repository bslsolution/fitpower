const FitLoader = {
    started: Date.now(),
    hidden: false,

    hide() {
        if (this.hidden) return;
        const el = document.getElementById('fp-loader');
        if (!el || el.classList.contains('is-done')) {
            this.hidden = true;
            return;
        }
        this.hidden = true;
        const wait = Math.max(0, 300 - (Date.now() - this.started));
        setTimeout(() => el.classList.add('is-done'), wait);
    }
};

document.addEventListener('DOMContentLoaded', () => FitLoader.hide());
window.addEventListener('load', () => FitLoader.hide());
setTimeout(() => FitLoader.hide(), 1200);
