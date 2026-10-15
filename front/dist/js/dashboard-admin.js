(async function () {
    const usuario = await AuthUI.exigir(['administrador']);
    if (!usuario) return;
    const t = (key, vars) => I18n.t(key, vars);

    const hashRaw = AuthUI.hash();
    const hashQ = hashRaw.indexOf('?');
    const hash = (hashQ === -1 ? hashRaw : hashRaw.slice(0, hashQ)) || 'inicio';
    const hashParams = new URLSearchParams(hashQ === -1 ? '' : hashRaw.slice(hashQ + 1));
    const idEditar = Number(hashParams.get('id')) || 0;
    const activo = ['socios', 'rutinas', 'notificaciones', 'entrenadores', 'fitpoints', 'config', 'usuarios', 'grupos', 'alta'].includes(hash)
        ? (hash === 'rutinas' ? 'socios' : hash === 'alta' ? 'usuarios' : hash)
        : 'inicio';
    document.getElementById('app').innerHTML = AuthUI.shell(usuario, activo);
    AuthUI.bindLogout();

    const contenido = document.getElementById('contenido');
    const [resumenRes, usuariosRes, entrenadoresRes, gruposRes] = await Promise.all([
        API.get('/admin/resumen'),
        API.get('/usuarios'),
        API.get('/entrenadores'),
        API.get('/grupos')
    ]);
    const resumen = resumenRes.resumen || {};
    const socios = resumenRes.socios || [];
    const usuarios = usuariosRes.usuarios || [];
    const entrenadores = entrenadoresRes.entrenadores || usuarios.filter((u) => u.rol === 'entrenador');
    const grupos = gruposRes.grupos || [];
    const USUARIOS_POR_PAGINA = 10;
    let paginaUsuarios = 1;
    let paginaSocios = 1;
    const listaSocios = usuarios.filter((u) => u.rol === 'socio');

    function opcionesEntrenador(idSel) {
        const actual = idSel ? String(idSel) : '';
        return `<option value="">${t('unassigned')}</option>` + entrenadores.map((e) => `
            <option value="${e.id_entrenador}" ${String(e.id_entrenador) === actual ? 'selected' : ''}>${e.nombre} ${e.apellido}</option>
        `).join('');
    }

    function filasSocios(lista) {
        return lista.map((s) => `
            <tr>
                <td data-label="${t('th_name')}">${AuthUI.persona(s.nombre, s.apellido)}</td>
                <td data-label="${t('th_email')}">${s.email}</td>
                <td data-label="${t('th_coach')}">
                    <select class="assign" data-asigna-socio="${s.id_socio}">
                        ${opcionesEntrenador(s.id_entrenador_asignado ?? s.id_entrenador)}
                    </select>
                </td>
                <td data-label="${t('th_membership')}">
                    <select class="assign membership-select ${s.membresia === 'inactiva' ? 'is-off' : 'is-on'}" data-membresia="${s.id_usuario}">
                        <option value="activa" ${s.membresia !== 'inactiva' ? 'selected' : ''}>${t('membership_on')}</option>
                        <option value="inactiva" ${s.membresia === 'inactiva' ? 'selected' : ''}>${t('membership_off')}</option>
                    </select>
                </td>
                <td data-label="${t('th_actions')}">
                    <a class="btn btn-navy btn-chip" href="#usuarios?id=${s.id_usuario}">${t('edit')}</a>
                </td>
            </tr>
        `).join('') || `<tr><td colspan="5" class="muted">${t('no_members')}</td></tr>`;
    }

    function bindAsignarEntrenador() {
        contenido.querySelectorAll('[data-asigna-socio]').forEach((sel) => {
            sel.addEventListener('change', async () => {
                const res = await API.put('/socios/' + sel.dataset.asignaSocio + '/entrenador', {
                    id_entrenador: sel.value ? Number(sel.value) : null
                });
                if (res.status !== 'ok') {
                    alert(I18n.api(res.message));
                    location.reload();
                }
            });
        });
    }

    function vistaInicio() {
        document.getElementById('titulo-pagina').textContent = t('nav_home');
        contenido.innerHTML = `
            <h2 class="welcome">${t('welcome_admin')}</h2>
            <p class="muted">${t('admin_summary')}</p>
            <div class="grid-4 home-kpis">
                <article class="card stat">
                    <div class="notify-icon notify-icon--navy" aria-hidden="true">
                        <img src="../imagenes/user.png" alt="">
                    </div>
                    <div class="num">${resumen.socios_asignados ?? socios.length}</div>
                    <p>${t('stat_members')}</p>
                </article>
                <article class="card stat good">
                    <div class="notify-icon notify-icon--green" aria-hidden="true">
                        <img src="../imagenes/muscle.png" alt="">
                    </div>
                    <div class="num">${resumen.rutinas_creadas ?? 0}</div>
                    <p>${t('stat_routines')}</p>
                </article>
                <article class="card stat warn">
                    <div class="notify-icon notify-icon--red" aria-hidden="true">
                        <img src="../imagenes/report.png" alt="">
                    </div>
                    <div class="num">${resumen.mensualidades_vencidas ?? 0}</div>
                    <p>${t('stat_overdue')}</p>
                </article>
                <article class="card notify-card">
                    <div class="notify-icon notify-icon--purple" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <path d="m3 7 9 6 9-6"></path>
                        </svg>
                    </div>
                    <h3>${t('notify_to')}</h3>
                    <div class="notify-checks">
                        <label><input type="checkbox" id="n-inactividad"> ${t('notify_inactivity')}</label>
                        <label><input type="checkbox" id="n-mensualidad"> ${t('notify_overdue')}</label>
                    </div>
                    <button class="btn btn-navy btn-small" type="button" id="n-enviar">${t('send')}</button>
                </article>
            </div>
            <div class="grid-2">
                <article class="card">
                    <div class="card-head">
                        <div>
                            <h3>${t('my_members')}</h3>
                            <p class="muted">${t('my_members_admin_help')}</p>
                        </div>
                        <a class="link-all" href="#usuarios">${t('see_all')}</a>
                    </div>
                    <div class="table-scroll" id="socios-tabla">
                    <table class="table">
                        <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_coach')}</th><th>${t('th_membership')}</th><th>${t('th_actions')}</th></tr></thead>
                        <tbody>${filasSocios(listaSocios.slice(0, 6))}</tbody>
                    </table>
                    </div>
                    <input class="table-hscroll table-hscroll-mobile-only" id="socios-barra" type="range" min="0" value="0" step="1" aria-label="${t('scroll_members')}">
                </article>
                <article class="card quick">
                    <h3>${t('quick_actions')}</h3>
                    <p class="muted">${t('quick_admin_help')}</p>
                    <a class="btn btn-navy" href="#alta"><span class="plus">+</span> ${t('user_create')}</a>
                    <a class="btn btn-navy" href="#usuarios"><span class="plus">+</span> ${t('edit_users')}</a>
                    <a class="btn btn-navy" href="#fitpoints"><span class="plus">+</span> ${t('manage_fitpoints')}</a>
                    <a class="btn btn-navy" href="#grupos"><span class="plus">+</span> ${t('edit_groups')}</a>
                </article>
            </div>
        `;
        document.getElementById('n-enviar').addEventListener('click', async () => {
            const inactividad = document.getElementById('n-inactividad').checked;
            const mensualidad = document.getElementById('n-mensualidad').checked;
            if (!inactividad && !mensualidad) {
                alert(t('notify_pick'));
                return;
            }
            const btn = document.getElementById('n-enviar');
            btn.disabled = true;
            const res = await API.post('/notificaciones/enviar', { inactividad, mensualidad });
            btn.disabled = false;
            if (res.status !== 'ok') {
                alert(I18n.api(res.message));
                return;
            }
            const n = Number(res.enviadas) || 0;
            alert(n ? t('notify_sent', { n }) : t('notify_none'));
        });
        bindAsignarEntrenador();
        bindMembresia();
        bindTablaSocios();
    }

    function bindTablaSocios() {
        const caja = document.getElementById('socios-tabla');
        const barra = document.getElementById('socios-barra');
        if (!caja || !barra) return;
        const maxScroll = () => Math.max(0, caja.scrollWidth - caja.clientWidth);
        const syncBarra = () => {
            const max = maxScroll();
            barra.max = String(Math.max(max, 1));
            barra.value = String(Math.min(caja.scrollLeft, max));
            barra.disabled = max <= 1;
        };
        caja.addEventListener('scroll', () => {
            barra.value = String(caja.scrollLeft);
        }, { passive: true });
        barra.addEventListener('input', () => {
            caja.scrollLeft = Number(barra.value);
        });
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(syncBarra).observe(caja);
        }
        window.addEventListener('resize', syncBarra);
        requestAnimationFrame(syncBarra);
    }

    function normalizarTexto(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filaUsuario(u) {
        const editing = idEditar && Number(u.id_usuario) === idEditar;
        const esEntrenador = u.rol === 'entrenador';
        return `
            <tr class="${editing ? 'is-editing' : ''}" data-usuario="${u.id_usuario}">
                <td data-label="${t('th_name')}">${AuthUI.persona(u.nombre, u.apellido)}</td>
                <td data-label="${t('th_email')}">${u.email}</td>
                <td data-label="${t('th_role')}">${I18n.role(u.rol)}</td>
                <td data-label="${t('th_actions')}">
                    <div class="row-actions">
                        ${esEntrenador ? `<a class="btn btn-navy btn-chip" href="#usuarios?id=${u.id_usuario}">${t('edit')}</a>` : ''}
                        ${esEntrenador ? `<button type="button" class="btn btn-danger btn-chip" data-del-usuario="${u.id_usuario}">${t('eliminar')}</button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }

    function leerFiltros() {
        return {
            q: document.getElementById('u-buscar')?.value || '',
            rol: document.getElementById('u-filtro-rol')?.value || ''
        };
    }

    function hayFiltros(f = leerFiltros()) {
        return Boolean(f.q || f.rol);
    }

    function usuariosFiltrados() {
        if (idEditar) {
            return usuarios.filter((u) => Number(u.id_usuario) === idEditar);
        }
        const f = leerFiltros();
        const n = normalizarTexto(f.q);
        return usuarios.filter((u) => {
            if (u.rol === 'socio') return false;
            if (n) {
                const blob = normalizarTexto(`${u.nombre} ${u.apellido} ${u.email}`);
                if (!blob.includes(n)) return false;
            }
            if (f.rol && u.rol !== f.rol) return false;
            return true;
        });
    }

    function numerosPagina(actual, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        const items = [1];
        if (actual > 3) items.push('…');
        const desde = Math.max(2, actual - 1);
        const hasta = Math.min(total - 1, actual + 1);
        for (let i = desde; i <= hasta; i += 1) items.push(i);
        if (actual < total - 2) items.push('…');
        items.push(total);
        return items;
    }

    function pintarPaginacion(totalFilas, totalPaginas) {
        const nav = document.getElementById('u-pager');
        if (!nav) return;
        if (!totalFilas || totalPaginas <= 1) {
            nav.hidden = true;
            nav.innerHTML = '';
            return;
        }
        nav.hidden = false;
        const btn = (page, label, extra = '') => `
            <button type="button" class="btn btn-chip ${page === paginaUsuarios ? 'btn-navy' : 'btn-ghost'}${extra}" data-page="${page}" ${page === paginaUsuarios ? 'aria-current="page"' : ''} aria-label="${t('users_page', { n: page })}">${label}</button>
        `;
        const prevDis = paginaUsuarios <= 1;
        const nextDis = paginaUsuarios >= totalPaginas;
        nav.innerHTML = `
            <button type="button" class="btn btn-ghost btn-chip" data-page="${paginaUsuarios - 1}" ${prevDis ? 'disabled' : ''}>${t('users_prev')}</button>
            ${numerosPagina(paginaUsuarios, totalPaginas).map((n) => (
                n === '…'
                    ? '<span class="pager-ellipsis" aria-hidden="true">…</span>'
                    : btn(n, n)
            )).join('')}
            <button type="button" class="btn btn-ghost btn-chip" data-page="${paginaUsuarios + 1}" ${nextDis ? 'disabled' : ''}>${t('users_next')}</button>
        `;
    }

    function pintarTablaUsuarios(resetPagina) {
        const tbody = document.getElementById('usuarios-tbody');
        if (!tbody) return;
        if (resetPagina) paginaUsuarios = 1;
        const lista = usuariosFiltrados();
        const totalPaginas = Math.max(1, Math.ceil(lista.length / USUARIOS_POR_PAGINA));
        if (paginaUsuarios > totalPaginas) paginaUsuarios = totalPaginas;
        if (paginaUsuarios < 1) paginaUsuarios = 1;
        const inicio = lista.length ? (paginaUsuarios - 1) * USUARIOS_POR_PAGINA : 0;
        const pagina = lista.slice(inicio, inicio + USUARIOS_POR_PAGINA);
        tbody.innerHTML = pagina.length
            ? pagina.map(filaUsuario).join('')
            : `<tr class="users-empty-row"><td colspan="4" class="muted users-empty">${t('users_no_match')}</td></tr>`;
        const count = document.getElementById('u-count');
        if (count) {
            count.textContent = lista.length
                ? t('users_showing', { from: inicio + 1, to: inicio + pagina.length, total: lista.length })
                : '';
        }
        const limpia = document.getElementById('u-limpiar');
        if (limpia) limpia.hidden = !hayFiltros();
        pintarPaginacion(lista.length, totalPaginas);
        bindEliminarUsuario();
    }

    function bindFiltrosUsuarios() {
        ['u-buscar', 'u-filtro-rol'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => {
                pintarTablaUsuarios(true);
            });
        });
        const limpia = document.getElementById('u-limpiar');
        if (limpia) {
            limpia.addEventListener('click', () => {
                const buscar = document.getElementById('u-buscar');
                const rol = document.getElementById('u-filtro-rol');
                if (buscar) buscar.value = '';
                if (rol) rol.value = '';
                pintarTablaUsuarios(true);
            });
        }
    }

    function bindPaginacion() {
        const nav = document.getElementById('u-pager');
        if (!nav || nav.dataset.bound) return;
        nav.dataset.bound = '1';
        nav.addEventListener('click', (ev) => {
            const btn = ev.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            const page = Number(btn.dataset.page);
            if (!page || page === paginaUsuarios) return;
            paginaUsuarios = page;
            pintarTablaUsuarios();
            document.getElementById('usuarios-tbody')?.scrollIntoView({ block: 'start', behavior: 'smooth' });
        });
    }

    function htmlFormularioUsuario(editando) {
        const volver = editando && editando.rol === 'socio' ? '#socios' : '#usuarios';
        return `
            <article class="card users-create">
                <h3>${editando ? t('user_edit') : t('user_create')}</h3>
                <p class="muted">${editando ? t('user_edit_help') : t('user_create_help')}</p>
                <div class="alert" id="error"></div>
                <div class="form-inline">
                    <div class="field"><label>${t('label_first')}</label><input id="u-nombre"></div>
                    <div class="field"><label>${t('label_last')}</label><input id="u-apellido"></div>
                    <div class="field"><label>${t('login_email')}</label><input id="u-email" type="email"></div>
                    <div class="field">
                        <label>${t('label_password')}</label>
                        <input id="u-pass" type="password" ${editando ? `placeholder="${t('user_password_keep')}"` : ''}>
                    </div>
                    <div class="field"><label>${t('th_role')}</label>
                        <select id="u-rol" class="plain" ${editando ? 'disabled' : ''}>
                            <option value="socio">${t('role_socio_opt')}</option>
                            <option value="entrenador">${t('role_entrenador_opt')}</option>
                            <option value="administrador">${t('role_admin_opt')}</option>
                        </select>
                    </div>
                    <div class="field"><label>${t('label_phone')}</label><input id="u-tel"></div>
                    <div class="field" id="campo-entrenador">
                        <label>${t('label_coach_charge')}</label>
                        <select id="u-entrenador" class="plain">${opcionesEntrenador(editando ? editando.id_entrenador_asignado : '')}</select>
                    </div>
                    <button class="btn btn-navy" type="button" id="u-crear">${editando ? t('save') : t('register')}</button>
                    <a class="btn btn-ghost" href="${volver}">${t('cancel')}</a>
                </div>
            </article>
        `;
    }

    function bindFormularioUsuario(editando) {
        const error = document.getElementById('error');
        const campoEntrenador = document.getElementById('campo-entrenador');
        const rolSelect = document.getElementById('u-rol');
        const toggleEntrenador = () => {
            campoEntrenador.style.display = rolSelect.value === 'socio' ? '' : 'none';
        };
        rolSelect.addEventListener('change', toggleEntrenador);
        if (editando) {
            document.getElementById('u-nombre').value = editando.nombre || '';
            document.getElementById('u-apellido').value = editando.apellido || '';
            document.getElementById('u-email').value = editando.email || '';
            document.getElementById('u-tel').value = editando.telefono || '';
            rolSelect.value = editando.rol || 'socio';
        }
        toggleEntrenador();
        document.getElementById('u-crear').addEventListener('click', async () => {
            error.style.display = 'none';
            const rol = rolSelect.value;
            const payload = {
                nombre: document.getElementById('u-nombre').value,
                apellido: document.getElementById('u-apellido').value,
                email: document.getElementById('u-email').value,
                telefono: document.getElementById('u-tel').value
            };
            const password = document.getElementById('u-pass').value;
            if (rol === 'socio') {
                payload.id_entrenador = document.getElementById('u-entrenador').value
                    ? Number(document.getElementById('u-entrenador').value)
                    : null;
            }
            let res;
            if (editando) {
                if (password) payload.password = password;
                res = await API.put('/usuarios/' + editando.id_usuario, payload);
            } else {
                payload.password = password;
                payload.rol = rol;
                res = await API.post('/usuarios', payload);
            }
            if (res.status !== 'ok') {
                error.textContent = I18n.api(res.message);
                error.style.display = 'block';
                return;
            }
            const destino = (editando ? editando.rol : rol) === 'socio' ? '#socios' : '#usuarios';
            if ((location.hash || '') === destino) location.reload();
            else location.hash = destino;
        });
    }

    function htmlTablaUsuarios(conFiltros) {
        return `
            <article class="card users-section"${conFiltros ? '' : ' style="margin-top:16px"'}>
                <div class="card-head">
                    <div>
                        <h3>${t('users_title')}</h3>
                    </div>
                    ${conFiltros ? `<a class="btn btn-navy btn-small" href="#alta"><span class="plus">+</span> ${t('user_create')}</a>` : ''}
                </div>
                ${conFiltros ? `
                <div class="users-filters form-inline">
                    <div class="field search-field">
                        <label for="u-buscar">${t('users_search')}</label>
                        <input id="u-buscar" type="search" placeholder="${t('users_search_ph')}" autocomplete="off">
                    </div>
                    <div class="field">
                        <label for="u-filtro-rol">${t('th_role')}</label>
                        <select id="u-filtro-rol" class="plain">
                            <option value="">${t('users_filter_all')}</option>
                            <option value="entrenador">${t('role_entrenador_opt')}</option>
                            <option value="administrador">${t('role_admin_opt')}</option>
                        </select>
                    </div>
                    <button class="btn btn-ghost" type="button" id="u-limpiar" hidden>${t('users_clear_filters')}</button>
                </div>
                <p class="muted users-count" id="u-count"></p>
                ` : ''}
                <table class="table users-table">
                    <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_role')}</th><th>${t('th_actions')}</th></tr></thead>
                    <tbody id="usuarios-tbody"></tbody>
                </table>
                ${conFiltros ? '<nav class="users-pager" id="u-pager" hidden></nav>' : ''}
            </article>
        `;
    }

    function vistaAlta() {
        document.getElementById('titulo-pagina').textContent = t('user_create');
        contenido.innerHTML = htmlFormularioUsuario(null);
        bindFormularioUsuario(null);
    }

    function vistaUsuarios() {
        const editando = idEditar
            ? usuarios.find((u) => Number(u.id_usuario) === idEditar) || null
            : null;
        if (editando) {
            document.getElementById('titulo-pagina').textContent = t('user_edit');
            contenido.innerHTML = htmlFormularioUsuario(editando) + htmlTablaUsuarios(false);
            bindFormularioUsuario(editando);
            pintarTablaUsuarios();
            return;
        }
        document.getElementById('titulo-pagina').textContent = t('users_title');
        contenido.innerHTML = htmlTablaUsuarios(true);
        bindFiltrosUsuarios();
        bindPaginacion();
        pintarTablaUsuarios(true);
    }

    function bindEliminarUsuario() {
        contenido.querySelectorAll('[data-del-usuario]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                btn.disabled = true;
                const res = await API.del('/usuarios/' + btn.dataset.delUsuario);
                if (res.status !== 'ok') {
                    btn.disabled = false;
                    alert(I18n.api(res.message));
                    return;
                }
                location.reload();
            });
        });
    }

    function bindMembresia() {
        contenido.querySelectorAll('[data-membresia]').forEach((sel) => {
            const pintar = () => {
                sel.classList.toggle('is-on', sel.value === 'activa');
                sel.classList.toggle('is-off', sel.value === 'inactiva');
            };
            pintar();
            sel.addEventListener('change', async () => {
                pintar();
                sel.disabled = true;
                const res = await API.put('/usuarios/' + sel.dataset.membresia + '/membresia', {
                    membresia: sel.value
                });
                sel.disabled = false;
                if (res.status !== 'ok') {
                    alert(I18n.api(res.message));
                    location.reload();
                    return;
                }
                const id = Number(sel.dataset.membresia);
                const u = usuarios.find((x) => Number(x.id_usuario) === id);
                if (u) u.membresia = sel.value;
                if (document.getElementById('socios-tbody')) pintarTablaSocios();
            });
        });
    }

    function leerFiltrosSocios() {
        return {
            q: document.getElementById('s-buscar')?.value || '',
            membresia: document.getElementById('s-filtro-membresia')?.value || '',
            entrenador: document.getElementById('s-filtro-entrenador')?.value || ''
        };
    }

    function hayFiltrosSocios(f = leerFiltrosSocios()) {
        return Boolean(f.q || f.membresia || f.entrenador);
    }

    function sociosFiltrados() {
        const f = leerFiltrosSocios();
        const n = normalizarTexto(f.q);
        return listaSocios.filter((s) => {
            if (n) {
                const blob = normalizarTexto(`${s.nombre} ${s.apellido} ${s.email}`);
                if (!blob.includes(n)) return false;
            }
            if (f.membresia) {
                const inactiva = s.membresia === 'inactiva';
                if (f.membresia === 'inactiva' && !inactiva) return false;
                if (f.membresia === 'activa' && inactiva) return false;
            }
            if (f.entrenador) {
                const idEnt = s.id_entrenador_asignado ? String(s.id_entrenador_asignado) : '';
                if (f.entrenador === 'none') {
                    if (idEnt) return false;
                } else if (idEnt !== f.entrenador) {
                    return false;
                }
            }
            return true;
        });
    }

    function pintarPaginacionSocios(totalFilas, totalPaginas) {
        const nav = document.getElementById('s-pager');
        if (!nav) return;
        if (!totalFilas || totalPaginas <= 1) {
            nav.hidden = true;
            nav.innerHTML = '';
            return;
        }
        nav.hidden = false;
        const btn = (page, label, extra = '') => `
            <button type="button" class="btn btn-chip ${page === paginaSocios ? 'btn-navy' : 'btn-ghost'}${extra}" data-page="${page}" ${page === paginaSocios ? 'aria-current="page"' : ''} aria-label="${t('users_page', { n: page })}">${label}</button>
        `;
        const prevDis = paginaSocios <= 1;
        const nextDis = paginaSocios >= totalPaginas;
        nav.innerHTML = `
            <button type="button" class="btn btn-ghost btn-chip" data-page="${paginaSocios - 1}" ${prevDis ? 'disabled' : ''}>${t('users_prev')}</button>
            ${numerosPagina(paginaSocios, totalPaginas).map((n) => (
                n === '…'
                    ? '<span class="pager-ellipsis" aria-hidden="true">…</span>'
                    : btn(n, n)
            )).join('')}
            <button type="button" class="btn btn-ghost btn-chip" data-page="${paginaSocios + 1}" ${nextDis ? 'disabled' : ''}>${t('users_next')}</button>
        `;
    }

    function pintarTablaSocios(resetPagina) {
        const tbody = document.getElementById('socios-tbody');
        if (!tbody) return;
        if (resetPagina) paginaSocios = 1;
        const lista = sociosFiltrados();
        const totalPaginas = Math.max(1, Math.ceil(lista.length / USUARIOS_POR_PAGINA));
        if (paginaSocios > totalPaginas) paginaSocios = totalPaginas;
        if (paginaSocios < 1) paginaSocios = 1;
        const inicio = lista.length ? (paginaSocios - 1) * USUARIOS_POR_PAGINA : 0;
        const pagina = lista.slice(inicio, inicio + USUARIOS_POR_PAGINA);
        tbody.innerHTML = pagina.length
            ? filasSocios(pagina)
            : `<tr class="users-empty-row"><td colspan="5" class="muted users-empty">${hayFiltrosSocios() ? t('members_no_match') : t('no_members')}</td></tr>`;
        const count = document.getElementById('s-count');
        if (count) {
            count.textContent = lista.length
                ? t('users_showing', { from: inicio + 1, to: inicio + pagina.length, total: lista.length })
                : '';
        }
        const limpia = document.getElementById('s-limpiar');
        if (limpia) limpia.hidden = !hayFiltrosSocios();
        pintarPaginacionSocios(lista.length, totalPaginas);
        bindAsignarEntrenador();
        bindMembresia();
    }

    function bindFiltrosSocios() {
        ['s-buscar', 's-filtro-membresia', 's-filtro-entrenador'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => {
                pintarTablaSocios(true);
            });
        });
        const limpia = document.getElementById('s-limpiar');
        if (limpia) {
            limpia.addEventListener('click', () => {
                const buscar = document.getElementById('s-buscar');
                const mem = document.getElementById('s-filtro-membresia');
                const ent = document.getElementById('s-filtro-entrenador');
                if (buscar) buscar.value = '';
                if (mem) mem.value = '';
                if (ent) ent.value = '';
                pintarTablaSocios(true);
            });
        }
    }

    function bindPaginacionSocios() {
        const nav = document.getElementById('s-pager');
        if (!nav || nav.dataset.bound) return;
        nav.dataset.bound = '1';
        nav.addEventListener('click', (ev) => {
            const btn = ev.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            const page = Number(btn.dataset.page);
            if (!page || page === paginaSocios) return;
            paginaSocios = page;
            pintarTablaSocios();
            document.getElementById('socios-tbody')?.scrollIntoView({ block: 'start', behavior: 'smooth' });
        });
    }

    function vistaSocios() {
        document.getElementById('titulo-pagina').textContent = t('my_members');
        const opcionesFiltroEntrenador = `<option value="">${t('users_filter_all')}</option>`
            + `<option value="none">${t('unassigned')}</option>`
            + entrenadores.map((e) => `<option value="${e.id_entrenador}">${e.nombre} ${e.apellido}</option>`).join('');
        contenido.innerHTML = `
            <article class="card users-section">
                <div class="card-head">
                    <div>
                        <h3>${t('my_members')}</h3>
                        <p class="muted">${t('my_members_admin_help')}</p>
                    </div>
                </div>
                <div class="users-filters form-inline">
                    <div class="field search-field">
                        <label for="s-buscar">${t('users_search')}</label>
                        <input id="s-buscar" type="search" placeholder="${t('users_search_ph')}" autocomplete="off">
                    </div>
                    <div class="field">
                        <label for="s-filtro-membresia">${t('th_membership')}</label>
                        <select id="s-filtro-membresia" class="plain">
                            <option value="">${t('users_filter_all_f')}</option>
                            <option value="activa">${t('membership_on')}</option>
                            <option value="inactiva">${t('membership_off')}</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="s-filtro-entrenador">${t('th_coach')}</label>
                        <select id="s-filtro-entrenador" class="plain">${opcionesFiltroEntrenador}</select>
                    </div>
                    <button class="btn btn-ghost" type="button" id="s-limpiar" hidden>${t('users_clear_filters')}</button>
                </div>
                <p class="muted users-count" id="s-count"></p>
                <table class="table users-table">
                    <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_coach')}</th><th>${t('th_membership')}</th><th>${t('th_actions')}</th></tr></thead>
                    <tbody id="socios-tbody"></tbody>
                </table>
                <nav class="users-pager" id="s-pager" hidden></nav>
            </article>
        `;
        bindFiltrosSocios();
        bindPaginacionSocios();
        pintarTablaSocios(true);
    }

    function vistaGrupos() {
        document.getElementById('titulo-pagina').textContent = t('groups_title');
        contenido.innerHTML = `
            <article class="card">
                <h3>${t('groups_title')}</h3>
                <p class="muted">${t('groups_help')}</p>
                <div class="alert" id="g-error"></div>
                <div class="form-inline">
                    <div class="field"><label>${t('group_name')}</label><input id="g-nombre" placeholder="Pierna"></div>
                    <button class="btn btn-navy" id="g-crear">${t('add')}</button>
                </div>
                <table class="table" style="margin-top:16px">
                    <thead><tr><th>${t('th_group')}</th><th></th></tr></thead>
                    <tbody>
                        ${grupos.map((g) => `
                            <tr>
                                <td><input class="assign" data-nombre-grupo="${g.id_grupo}" value="${g.nombre}"></td>
                                <td>
                                    <button class="btn btn-navy btn-chip" type="button" data-guardar-grupo="${g.id_grupo}">${t('save')}</button>
                                    <button class="btn btn-ghost btn-small" type="button" data-del-grupo="${g.id_grupo}">${t('delete_btn')}</button>
                                </td>
                            </tr>
                        `).join('') || `<tr><td colspan="2" class="muted">${t('no_groups')}</td></tr>`}
                    </tbody>
                </table>
            </article>
        `;
        const error = document.getElementById('g-error');
        const mostrarError = (msg) => {
            error.textContent = msg;
            error.style.display = 'block';
        };
        document.getElementById('g-crear').addEventListener('click', async () => {
            error.style.display = 'none';
            const nombre = document.getElementById('g-nombre').value.trim();
            if (!nombre) {
                mostrarError(t('group_required'));
                return;
            }
            const res = await API.post('/grupos', { nombre });
            if (res.status === 'ok') location.reload();
            else mostrarError(I18n.api(res.message));
        });
        contenido.querySelectorAll('[data-guardar-grupo]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                error.style.display = 'none';
                const input = contenido.querySelector(`[data-nombre-grupo="${btn.dataset.guardarGrupo}"]`);
                const nombre = (input.value || '').trim();
                if (!nombre) {
                    mostrarError(t('group_required'));
                    return;
                }
                const res = await API.put('/grupos/' + btn.dataset.guardarGrupo, { nombre });
                if (res.status === 'ok') location.reload();
                else mostrarError(I18n.api(res.message));
            });
        });
        contenido.querySelectorAll('[data-del-grupo]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                error.style.display = 'none';
                const res = await API.del('/grupos/' + btn.dataset.delGrupo);
                if (res.status === 'ok') location.reload();
                else mostrarError(I18n.api(res.message));
            });
        });
    }

    async function vistaFitPoints() {
        document.getElementById('titulo-pagina').textContent = t('nav_fitpoints');
        const listaRes = await API.get('/recompensas');
        const recompensas = listaRes.recompensas || [];
        contenido.innerHTML = `
            <section class="fitpoints-main">
                <article class="card">
                        <h3 id="fp-form-title">${t('reward_add')}</h3>
                        <p class="muted">${t('reward_add_help')}</p>
                        <div class="alert" id="fp-error"></div>
                        <input type="hidden" id="fp-id" value="">
                        <div class="form-inline">
                            <div class="field"><label>${t('reward_name')}</label><input id="fp-nombre"></div>
                            <div class="field"><label>${t('reward_cost')}</label><input id="fp-costo" type="number" min="1" step="1"></div>
                            <div class="field"><label>${t('label_desc')}</label><input id="fp-desc" placeholder="${t('optional')}"></div>
                            <div class="field"><label>${t('reward_image')}</label><input id="fp-imagen" type="file" accept="image/jpeg,image/png,image/gif,image/webp"></div>
                            <button class="btn btn-navy" type="button" id="fp-guardar">${t('add')}</button>
                            <button class="btn btn-ghost is-hidden" type="button" id="fp-cancelar">${t('reward_cancel')}</button>
                        </div>
                    </article>
                    <div class="reward-grid" id="fp-grid">
                        ${recompensas.length ? recompensas.map((r) => tarjetaRecompensa(r)).join('') : `<p class="muted">${t('reward_empty')}</p>`}
                    </div>
            </section>
        `;
        const error = document.getElementById('fp-error');
        const mostrarError = (msg) => {
            error.textContent = msg;
            error.style.display = 'block';
        };
        const limpiarForm = () => {
            document.getElementById('fp-id').value = '';
            document.getElementById('fp-nombre').value = '';
            document.getElementById('fp-costo').value = '';
            document.getElementById('fp-desc').value = '';
            document.getElementById('fp-imagen').value = '';
            document.getElementById('fp-form-title').textContent = t('reward_add');
            document.getElementById('fp-guardar').textContent = t('add');
            document.getElementById('fp-cancelar').classList.add('is-hidden');
            error.style.display = 'none';
        };
        const armarForm = () => {
            const datos = new FormData();
            datos.append('nombre', document.getElementById('fp-nombre').value.trim());
            datos.append('costo_puntos', document.getElementById('fp-costo').value.trim());
            datos.append('descripcion', document.getElementById('fp-desc').value.trim());
            const archivo = document.getElementById('fp-imagen').files[0];
            if (archivo) datos.append('imagen', archivo);
            return datos;
        };
        document.getElementById('fp-guardar').addEventListener('click', async () => {
            error.style.display = 'none';
            const id = document.getElementById('fp-id').value;
            const datos = armarForm();
            if (!datos.get('nombre')) {
                mostrarError(t('reward_name_required'));
                return;
            }
            if (!datos.get('costo_puntos')) {
                mostrarError(t('reward_cost_required'));
                return;
            }
            if (!id && !datos.get('imagen')) {
                mostrarError(t('reward_image_required'));
                return;
            }
            const res = id
                ? await API.postForm('/recompensas/' + id, datos)
                : await API.postForm('/recompensas', datos);
            if (res.status === 'ok') location.reload();
            else mostrarError(I18n.api(res.message));
        });
        document.getElementById('fp-cancelar').addEventListener('click', limpiarForm);
        contenido.querySelectorAll('[data-editar-recompensa]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('fp-id').value = btn.dataset.editarRecompensa;
                document.getElementById('fp-nombre').value = btn.dataset.nombre || '';
                document.getElementById('fp-costo').value = btn.dataset.costo || '';
                document.getElementById('fp-desc').value = btn.dataset.desc || '';
                document.getElementById('fp-imagen').value = '';
                document.getElementById('fp-form-title').textContent = t('reward_edit');
                document.getElementById('fp-guardar').textContent = t('save');
                document.getElementById('fp-cancelar').classList.remove('is-hidden');
                error.style.display = 'none';
                document.getElementById('fp-nombre').focus();
            });
        });
        contenido.querySelectorAll('[data-del-recompensa]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                error.style.display = 'none';
                const res = await API.del('/recompensas/' + btn.dataset.delRecompensa);
                if (res.status === 'ok') location.reload();
                else mostrarError(I18n.api(res.message));
            });
        });
    }

    function tarjetaRecompensa(r) {
        const nombre = String(r.nombre || '');
        const desc = String(r.descripcion || '');
        const costo = String(r.costo_puntos || '');
        return `
            <article class="reward-card">
                <div class="reward-photo">
                    <img src="${r.imagen}" alt="${nombre.replace(/"/g, '&quot;')}">
                </div>
                <strong>${nombre}</strong>
                <span class="reward-cost">${costo} FitPoints</span>
                <div class="reward-actions">
                    <button type="button" class="btn btn-navy btn-small" data-editar-recompensa="${r.id_recompensa}" data-nombre="${nombre.replace(/"/g, '&quot;')}" data-costo="${costo}" data-desc="${desc.replace(/"/g, '&quot;')}">${t('edit')}</button>
                    <button type="button" class="btn btn-ghost btn-small" data-del-recompensa="${r.id_recompensa}">${t('reward_delete')}</button>
                </div>
            </article>
        `;
    }

    const vistas = {
        inicio: vistaInicio,
        usuarios: vistaUsuarios,
        alta: vistaAlta,
        socios: vistaSocios,
        rutinas: vistaSocios,
        fitpoints: vistaFitPoints,
        grupos: vistaGrupos,
        notificaciones: () => AuthUI.vistaNotificaciones('empty_notif'),
        config: () => {
            AuthUI.vistaConfig(usuario);
            document.getElementById('contenido').insertAdjacentHTML('beforeend', `
                <article class="card" style="max-width:520px;margin-top:16px">
                    <h3>${t('gym_title')}</h3>
                    <p class="muted">${t('gym_help')}</p>
                    <a class="btn btn-navy" href="#grupos">${t('edit_muscle_groups')}</a>
                </article>
            `);
        }
    };
    await Promise.resolve((vistas[hash] || vistaInicio)());
    window.addEventListener('hashchange', () => location.reload());
    FitLoader.hide();
})();
