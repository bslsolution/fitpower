const AuthUI = {
    async me() {
        return API.get('/me');
    },

    async exigir(roles) {
        const res = await this.me();
        if (res.status !== 'ok' || !res.usuario) {
            location.href = FRONT.login;
            return null;
        }
        if (roles && !roles.includes(res.usuario.rol)) {
            location.href = FRONT.dashboards[res.usuario.rol] || FRONT.login;
            return null;
        }
        return res.usuario;
    },

    irSegunRol(usuario) {
        const url = FRONT.dashboards[usuario.rol] || FRONT.landing;
        location.href = url;
    },

    async logout() {
        await API.post('/logout', {});
        location.href = FRONT.login;
    },

    iniciales(usuario) {
        const n = (usuario.nombre || '?')[0];
        const a = (usuario.apellido || '?')[0];
        return (n + a).toUpperCase();
    },

    icono(nombre, extra = '') {
        const svg = {
            inicio: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>',
            rutinas: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>',
            campana: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"/><path d="M10 21h4"/></svg>',
            gente: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 19c.8-3.2 3.3-5 6-5s5.2 1.8 6 5M14 19c.4-2 1.8-3.5 3.5-3.5 1.4 0 2.6.8 3.2 2"/></svg>',
            config: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M4.9 6.3l1.4 1.4M17.7 16.3l1.4 1.4M3 12h2M19 12h2M4.9 17.7l1.4-1.4M17.7 7.7l1.4-1.4"/></svg>',
            progreso: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5M4 19h16"/><path d="M7 14l4-5 3 3 5-7"/></svg>',
            salir: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"/><path d="M4 12h11M12 8l4 4-4 4"/></svg>'
        };
        if (nombre === 'fitpoints') {
            return `<span class="nav-icon nav-fp">
                <img class="fp-logo-white" src="/front/dist/imagenes/FitPoints-white.png" alt="">
                <img class="fp-logo-navy" src="/front/dist/imagenes/FitPoints-navy.png" alt="">
            </span>`;
        }
        return `<span class="nav-icon">${svg[nombre] || ''}${extra}</span>`;
    },

    notifDot() {
        return `<span class="nav-notif-dot" title="${I18n.t('notif_new')}" aria-label="${I18n.t('notif_new')}"></span>`;
    },

    hash() {
        return (location.hash || '#inicio').replace('#', '') || 'inicio';
    },

    shell(usuario, activo) {
        const base = FRONT.dashboards[usuario.rol];
        const hayNueva = Number(usuario.notificaciones_nuevas) > 0
            && activo !== 'notificaciones';
        const item = (id, href, icono, texto) => {
            const extra = id === 'notificaciones' && hayNueva ? this.notifDot() : '';
            return `<a class="${activo === id ? 'active' : ''}" href="${href}">${this.icono(icono, extra)} ${texto}</a>`;
        };

        return `
            <aside class="sidebar">
                <a class="logo" href="/front/index.html">
                    <img src="/front/dist/imagenes/FitPoints-white.png" alt=""> FitPower
                </a>
                <nav class="side-nav">
                    ${item('inicio', base, 'inicio', I18n.t('nav_home'))}
                    ${item('rutinas', base + '#rutinas', 'rutinas', I18n.t('nav_routines'))}
                    ${item('notificaciones', base + '#notificaciones', 'campana', I18n.t('nav_notif'))}
                    ${item('entrenadores', base + '#entrenadores', 'gente', I18n.t('nav_coaches'))}
                    ${usuario.rol === 'administrador' ? item('fitpoints', base + '#fitpoints', 'fitpoints', I18n.t('nav_fitpoints')) : ''}
                    ${usuario.rol === 'socio' ? item('progreso', base + '#progreso', 'progreso', I18n.t('nav_progress')) : ''}
                    ${usuario.rol === 'socio' ? item('fitpoints', base + '#fitpoints', 'fitpoints', I18n.t('nav_fitpoints')) : ''}
                </nav>
                <div class="side-spacer"></div>
                <nav class="side-nav">
                    ${item('config', base + '#config', 'config', I18n.t('nav_settings'))}
                    <button type="button" id="btn-salir">${this.icono('salir')} ${I18n.t('nav_logout')}</button>
                </nav>
            </aside>
            <main class="app-main">
                <header class="topbar">
                    <h1 id="titulo-pagina">${I18n.t('nav_home')}</h1>
                    <div class="topbar-actions">
                    ${usuario.rol === 'socio' ? `<a class="fp-points-chip" href="${base}#fitpoints" title="${I18n.t('nav_fitpoints')}">
                        <img src="/front/dist/imagenes/FitPoints-navy.png" alt="">
                        <strong>${Number(usuario.fitpoints) || 0}</strong>
                    </a>` : ''}
                    <div class="user-menu">
                        <button type="button" class="user-chip" id="user-menu-btn" aria-expanded="false" aria-haspopup="true">
                            <div class="avatar">${this.iniciales(usuario)}</div>
                            <div>
                                <strong>${usuario.nombre} ${usuario.apellido}</strong>
                                <small>${I18n.role(usuario.rol)}</small>
                            </div>
                        </button>
                        <div class="user-menu-pop" id="user-menu-pop" hidden>
                            <a href="${base}#config">${I18n.t('nav_settings')}</a>
                            <button type="button" id="btn-salir-menu">${I18n.t('nav_logout')}</button>
                        </div>
                    </div>
                    </div>
                </header>
                <div id="contenido"></div>
            </main>
        `;
    },

    bindLogout() {
        document.querySelectorAll('#btn-salir, #btn-salir-menu').forEach((btn) => {
            btn.addEventListener('click', () => this.logout());
        });
        this.bindUserMenu();
    },

    bindUserMenu() {
        const btn = document.getElementById('user-menu-btn');
        const pop = document.getElementById('user-menu-pop');
        if (!btn || !pop) return;
        const setOpen = (open) => {
            pop.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        btn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            setOpen(pop.hidden);
        });
        document.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') setOpen(false);
        });
    },

    persona(nombre, apellido) {
        return `<div class="person"><div class="avatar sm">${this.iniciales({ nombre, apellido })}</div>${nombre} ${apellido}</div>`;
    },

    vistaVacia(titulo, texto) {
        document.getElementById('titulo-pagina').textContent = titulo;
        document.getElementById('contenido').innerHTML = `
            <article class="card">
                <h3>${titulo}</h3>
                <p class="muted">${texto}</p>
            </article>
        `;
    },

    vistaConfig(usuario) {
        document.getElementById('titulo-pagina').textContent = I18n.t('settings_title');
        document.getElementById('contenido').innerHTML = `
            <article class="card" style="max-width:520px">
                <h3>${I18n.t('settings_account')}</h3>
                <p class="muted">${I18n.t('settings_account_help')}</p>
                <p><strong>${usuario.nombre} ${usuario.apellido}</strong></p>
                <p class="muted">${usuario.email}</p>
                <p class="muted">${I18n.t('settings_role', { role: I18n.role(usuario.rol) })}</p>
            </article>
            ${I18n.settingsCard()}
        `;
        I18n.bindLangButtons();
    },

    quitarBadgeNotif() {
        document.querySelectorAll('.nav-notif-dot').forEach((el) => el.remove());
    },

    fechaCorta(fecha) {
        if (!fecha) return '';
        const d = new Date(String(fecha).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return fecha;
        return d.toLocaleString(I18n.locale(), {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    tituloNotificacion(n) {
        if (n.tipo === 'canje_fitpoints') return I18n.t('notif_redeem_title');
        if (n.tipo === 'inactividad') return I18n.t('notif_inactive_title');
        if (n.tipo === 'mensualidad_vencida') return I18n.t('notif_overdue_title');
        return I18n.t('nav_notif');
    },

    textoNotificacion(n) {
        const d = n.datos || {};
        const name = [d.nombre, d.apellido].filter(Boolean).join(' ') || I18n.t('role_socio');
        if (n.tipo === 'canje_fitpoints') {
            return I18n.t('notif_redeem', { name, reward: d.recompensa || '', cost: d.costo ?? '' });
        }
        if (n.tipo === 'inactividad') {
            return d.para === 'entrenador'
                ? I18n.t('notif_inactive_coach', { name })
                : I18n.t('notif_inactive_user');
        }
        if (n.tipo === 'mensualidad_vencida') {
            return d.para === 'entrenador'
                ? I18n.t('notif_overdue_coach', { name })
                : I18n.t('notif_overdue_user');
        }
        return d.mensaje || '';
    },

    async vistaNotificaciones(emptyKey) {
        const t = (key, vars) => I18n.t(key, vars);
        document.getElementById('titulo-pagina').textContent = t('nav_notif');
        const contenido = document.getElementById('contenido');
        contenido.innerHTML = `<article class="card"><p class="muted">${t('notif_loading')}</p></article>`;
        const res = await API.get('/notificaciones');
        if (res.status !== 'ok') {
            contenido.innerHTML = `<article class="card"><p class="muted">${I18n.api(res.message)}</p></article>`;
            return;
        }
        const lista = res.notificaciones || [];
        if (!lista.length) {
            this.vistaVacia(t('nav_notif'), t(emptyKey || 'empty_notif_user'));
            return;
        }
        contenido.innerHTML = lista.map((n) => `
            <article class="card notif-item${n.leida ? '' : ' is-new'}">
                <div class="notify-icon">✉</div>
                <div>
                    <h3>${this.tituloNotificacion(n)}${n.leida ? '' : `<span class="notif-pill">${t('notif_new')}</span>`}</h3>
                    <p>${this.textoNotificacion(n)}</p>
                    <p class="muted">${this.fechaCorta(n.fecha)}</p>
                </div>
            </article>
        `).join('');
        if (lista.some((n) => !n.leida)) {
            await API.post('/notificaciones/leer', {});
            this.quitarBadgeNotif();
        }
    }
};
