(async function () {
    const usuario = await AuthUI.exigir(['administrador']);
    if (!usuario) return;
    const t = (key, vars) => I18n.t(key, vars);

    const hash = AuthUI.hash();
    const activo = ['rutinas', 'notificaciones', 'entrenadores', 'fitpoints', 'config', 'usuarios', 'grupos'].includes(hash) ? hash : 'inicio';
    document.getElementById('app').innerHTML = AuthUI.shell(usuario, activo);
    AuthUI.bindLogout();

    const contenido = document.getElementById('contenido');
    const [resumenRes, usuariosRes, rutinasRes, entrenadoresRes, gruposRes] = await Promise.all([
        API.get('/admin/resumen'),
        API.get('/usuarios'),
        API.get('/rutinas'),
        API.get('/entrenadores'),
        API.get('/grupos')
    ]);
    const resumen = resumenRes.resumen || {};
    const socios = resumenRes.socios || [];
    const usuarios = usuariosRes.usuarios || [];
    const rutinas = rutinasRes.rutinas || [];
    const entrenadores = entrenadoresRes.entrenadores || usuarios.filter((u) => u.rol === 'entrenador');
    const grupos = gruposRes.grupos || [];

    function opcionesEntrenador(idSel) {
        const actual = idSel ? String(idSel) : '';
        return `<option value="">${t('unassigned')}</option>` + entrenadores.map((e) => `
            <option value="${e.id_entrenador}" ${String(e.id_entrenador) === actual ? 'selected' : ''}>${e.nombre} ${e.apellido}</option>
        `).join('');
    }

    function filasSocios(lista) {
        return lista.map((s) => `
            <tr>
                <td>${AuthUI.persona(s.nombre, s.apellido)}</td>
                <td>${s.email}</td>
                <td>
                    <select class="assign" data-asigna-socio="${s.id_socio}">
                        ${opcionesEntrenador(s.id_entrenador)}
                    </select>
                </td>
                <td>${Number(s.activo) ? `<span class="badge badge-on">${t('badge_on')}</span>` : `<span class="badge badge-off">${t('badge_off')}</span>`}</td>
                <td>
                    <a class="btn btn-ghost btn-chip" href="#usuarios">${t('see_more')}</a>
                    <a class="btn btn-navy btn-chip" href="mailto:${s.email}">${t('send')}</a>
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
                    <table class="table">
                        <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_coach')}</th><th>${t('th_status')}</th><th>${t('th_actions')}</th></tr></thead>
                        <tbody>${filasSocios(socios.slice(0, 6))}</tbody>
                    </table>
                </article>
                <article class="card quick">
                    <h3>${t('quick_actions')}</h3>
                    <p class="muted">${t('quick_admin_help')}</p>
                    <a class="btn btn-navy" href="#usuarios"><span class="plus">+</span> ${t('edit_users')}</a>
                    <a class="btn btn-navy" href="#fitpoints"><span class="plus">+</span> ${t('manage_fitpoints')}</a>
                    <a class="btn btn-navy" href="#entrenadores"><span class="plus">+</span> ${t('edit_coaches')}</a>
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
    }

    function vistaUsuarios() {
        document.getElementById('titulo-pagina').textContent = t('users_title');
        contenido.innerHTML = `
            <article class="card">
                <h3>${t('user_create')}</h3>
                <p class="muted">${t('user_create_help')}</p>
                <div class="alert" id="error"></div>
                <div class="form-inline">
                    <div class="field"><label>${t('label_first')}</label><input id="u-nombre"></div>
                    <div class="field"><label>${t('label_last')}</label><input id="u-apellido"></div>
                    <div class="field"><label>${t('login_email')}</label><input id="u-email" type="email"></div>
                    <div class="field"><label>${t('label_password')}</label><input id="u-pass" type="password"></div>
                    <div class="field"><label>${t('th_role')}</label>
                        <select id="u-rol" class="plain">
                            <option value="socio">${t('role_socio_opt')}</option>
                            <option value="entrenador">${t('role_entrenador_opt')}</option>
                            <option value="administrador">${t('role_admin_opt')}</option>
                        </select>
                    </div>
                    <div class="field"><label>${t('label_phone')}</label><input id="u-tel"></div>
                    <div class="field" id="campo-entrenador">
                        <label>${t('label_coach_charge')}</label>
                        <select id="u-entrenador" class="plain">${opcionesEntrenador('')}</select>
                    </div>
                    <button class="btn btn-navy" id="u-crear">${t('register')}</button>
                </div>
            </article>
            <article class="card" style="margin-top:16px">
                <h3>${t('users_title')}</h3>
                <table class="table">
                    <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_role')}</th><th>${t('th_coach')}</th><th>${t('th_status')}</th></tr></thead>
                    <tbody>
                        ${usuarios.map((u) => `
                            <tr>
                                <td>${AuthUI.persona(u.nombre, u.apellido)}</td>
                                <td>${u.email}</td>
                                <td>${I18n.role(u.rol)}</td>
                                <td>${u.rol === 'socio' ? `<select class="assign" data-asigna-socio="${u.id_socio}">${opcionesEntrenador(u.id_entrenador_asignado)}</select>` : '—'}</td>
                                <td>${u.activo ? `<span class="badge badge-on">${t('badge_on')}</span>` : `<span class="badge badge-off">${t('badge_off')}</span>`}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </article>
        `;
        const campoEntrenador = document.getElementById('campo-entrenador');
        const rolSelect = document.getElementById('u-rol');
        const toggleEntrenador = () => {
            campoEntrenador.style.display = rolSelect.value === 'socio' ? '' : 'none';
        };
        rolSelect.addEventListener('change', toggleEntrenador);
        toggleEntrenador();
        document.getElementById('u-crear').addEventListener('click', async () => {
            const error = document.getElementById('error');
            error.style.display = 'none';
            const rol = document.getElementById('u-rol').value;
            const payload = {
                nombre: document.getElementById('u-nombre').value,
                apellido: document.getElementById('u-apellido').value,
                email: document.getElementById('u-email').value,
                password: document.getElementById('u-pass').value,
                rol,
                telefono: document.getElementById('u-tel').value
            };
            if (rol === 'socio') {
                payload.id_entrenador = document.getElementById('u-entrenador').value
                    ? Number(document.getElementById('u-entrenador').value)
                    : null;
            }
            const res = await API.post('/usuarios', payload);
            if (res.status !== 'ok') {
                error.textContent = I18n.api(res.message);
                error.style.display = 'block';
                return;
            }
            location.reload();
        });
        bindAsignarEntrenador();
    }

    function vistaRutinas() {
        document.getElementById('titulo-pagina').textContent = t('nav_routines');
        contenido.innerHTML = `
            <div class="routine-cards">
                ${rutinas.length ? rutinas.map((r) => `
                    <article class="routine-card">
                        <span class="muted">${r.id_socio ? t('assigned') : t('template')}</span>
                        <h3>${r.nombre}</h3>
                        <p class="muted">${r.objetivo || ''}</p>
                    </article>
                `).join('') : `<p class="muted">${t('no_routines')}</p>`}
            </div>
        `;
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

    function vistaEntrenadores() {
        document.getElementById('titulo-pagina').textContent = t('nav_coaches');
        contenido.innerHTML = `
            <div class="routine-cards">
                ${entrenadores.map((e) => `
                    <article class="card">
                        <div class="membership">
                            <div class="avatar">${AuthUI.iniciales(e)}</div>
                            <div>
                                <strong>${e.nombre} ${e.apellido}</strong>
                                <p class="muted">${e.especialidad || t('coach_fallback')}</p>
                                <p class="muted">${e.email}</p>
                            </div>
                        </div>
                    </article>
                `).join('') || `<article class="card"><p class="muted">${t('no_coaches')}</p></article>`}
            </div>
            <p style="margin-top:16px"><a class="btn btn-navy" href="#usuarios">${t('register_user')}</a></p>
        `;
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
        rutinas: vistaRutinas,
        entrenadores: vistaEntrenadores,
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
