(async function () {
    const usuario = await AuthUI.exigir(['entrenador']);
    if (!usuario) return;
    const t = (key, vars) => I18n.t(key, vars);

    const hash = AuthUI.hash();
    const activo = ['rutinas', 'notificaciones', 'entrenadores', 'config', 'socios', 'ejercicios'].includes(hash)
        ? (hash === 'socios' ? 'entrenadores' : hash)
        : 'inicio';
    document.getElementById('app').innerHTML = AuthUI.shell(usuario, activo);
    AuthUI.bindLogout();

    const contenido = document.getElementById('contenido');
    const [resumenRes, rutinasRes, ejerciciosRes, sociosRes, gruposRes] = await Promise.all([
        API.get('/entrenador/resumen'),
        API.get('/rutinas'),
        API.get('/ejercicios'),
        API.get('/socios'),
        API.get('/grupos')
    ]);

    const resumen = resumenRes.resumen || {};
    const socios = sociosRes.socios || resumenRes.socios || [];
    const rutinas = rutinasRes.rutinas || [];
    const ejercicios = ejerciciosRes.ejercicios || [];
    const grupos = gruposRes.grupos || [];
    const asignadas = rutinas.filter((r) => r.id_socio);

    function filasSocios(lista) {
        return lista.map((s) => `
            <tr>
                <td>${AuthUI.persona(s.nombre, s.apellido)}</td>
                <td>${s.email}</td>
                <td>${Number(s.activo) ? `<span class="badge badge-on">${t('badge_on')}</span>` : `<span class="badge badge-off">${t('badge_off')}</span>`}</td>
                <td>
                    <div class="row-actions">
                        <a class="btn btn-navy btn-chip" href="#rutinas">${t('create_routine')}</a>
                    </div>
                </td>
            </tr>
        `).join('') || `<tr><td colspan="4" class="muted">${t('no_members_trainer')}</td></tr>`;
    }

    function vistaInicio() {
        document.getElementById('titulo-pagina').textContent = t('nav_home');
        contenido.innerHTML = `
            <h2 class="welcome">${t('welcome_name', { name: usuario.nombre })}</h2>
            <div class="grid-3 home-kpis">
                <article class="card stat">
                    <div class="notify-icon notify-icon--navy" aria-hidden="true">
                        <img src="../imagenes/user.png" alt="">
                    </div>
                    <div class="num">${resumen.socios_asignados ?? 0}</div>
                    <p>${t('stat_assigned')}</p>
                </article>
                <article class="card stat good">
                    <div class="notify-icon notify-icon--green" aria-hidden="true">
                        <img src="../imagenes/muscle.png" alt="">
                    </div>
                    <div class="num">${resumen.rutinas_creadas ?? rutinas.length}</div>
                    <p>${t('stat_routines')}</p>
                </article>
                <article class="card stat warn">
                    <div class="notify-icon notify-icon--red" aria-hidden="true">
                        <img src="../imagenes/report.png" alt="">
                    </div>
                    <div class="num">${resumen.mensualidades_vencidas ?? 0}</div>
                    <p>${t('stat_overdue')}</p>
                </article>
            </div>
            <div class="grid-2">
                <article class="card">
                    <div class="card-head">
                        <div>
                            <h3>${t('my_members')}</h3>
                            <p class="muted">${t('my_members_trainer_help')}</p>
                        </div>
                        <a class="link-all" href="#socios">${t('see_all')}</a>
                    </div>
                    <div class="table-scroll" id="socios-tabla">
                    <table class="table">
                        <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_status')}</th><th>${t('th_actions')}</th></tr></thead>
                        <tbody>${filasSocios(socios.slice(0, 6))}</tbody>
                    </table>
                    </div>
                    <input class="table-hscroll table-hscroll-mobile-only" id="socios-barra" type="range" min="0" value="0" step="1" aria-label="${t('scroll_members')}">
                </article>
                <article class="card quick">
                    <h3>${t('quick_actions')}</h3>
                    <p class="muted">${t('quick_trainer_help')}</p>
                    <a class="btn btn-navy" href="#rutinas"><span class="plus">+</span> ${t('create_routine')}</a>
                    <a class="btn btn-navy" href="#socios"><span class="plus">+</span> ${t('see_my_members')}</a>
                    <a class="btn btn-navy" href="#ejercicios"><span class="plus">+</span> ${t('manage_exercises')}</a>
                </article>
            </div>
        `;
        bindTablaSocios();
    }

    function cardsRutinas(items, vacio) {
        if (!items.length) return `<p class="muted">${vacio}</p>`;
        return `
            <div class="routine-cards">
                ${items.map((r) => `
                    <article class="routine-card">
                        <span class="muted">${r.socio_nombre || ''} ${r.socio_apellido || ''}</span>
                        <h3>${r.nombre}</h3>
                        <p class="muted">${r.objetivo || ''}</p>
                        <div class="row-actions">
                            <a class="btn btn-primary btn-small" href="${FRONT.rutina}?id=${r.id_rutina}">${t('edit')}</a>
                            ${r.id_socio ? `<button type="button" class="btn btn-ghost btn-small" data-quitar-rutina="${r.id_rutina}">${t('remove')}</button>` : ''}
                        </div>
                    </article>
                `).join('')}
            </div>
        `;
    }

    function opcionesSociosHtml(placeholder) {
        const opts = socios
            .slice()
            .sort((a, b) => `${a.nombre} ${a.apellido}`.localeCompare(`${b.nombre} ${b.apellido}`, I18n.locale()))
            .map((s) => `<option value="${s.id_socio}">${s.nombre} ${s.apellido}</option>`)
            .join('');
        return `<option value="">${placeholder}</option>${opts}`;
    }

    function vistaRutinas() {
        document.getElementById('titulo-pagina').textContent = t('nav_routines');
        const sinSocios = !socios.length;
        contenido.innerHTML = `
            <div class="card">
                <h3>${t('create_routine')}</h3>
                <p class="muted">${t('create_routine_help')}</p>
                ${sinSocios
                    ? `<p class="muted">${t('no_members_trainer')}</p>`
                    : `<div class="form-inline" style="margin-bottom:16px">
                        <div class="field"><label>${t('th_name')}</label><input id="nueva-nombre" placeholder="Full body"></div>
                        <div class="field"><label>${t('label_goal')}</label><input id="nueva-objetivo" placeholder="Fuerza"></div>
                        <div class="field">
                            <label for="nueva-socio">${t('label_member')}</label>
                            <select id="nueva-socio" class="plain">${opcionesSociosHtml(t('pick_member_routine'))}</select>
                        </div>
                        <button class="btn btn-navy" id="btn-crear">${t('create_routine')}</button>
                    </div>`}
                <div class="routine-filter">
                    <h3>${t('assigned_to_members')}</h3>
                    ${sinSocios ? '' : `<div class="field">
                        <label for="filtro-socio">${t('filter_by_member')}</label>
                        <select id="filtro-socio" class="plain">${opcionesSociosHtml(t('all_members'))}</select>
                    </div>`}
                </div>
                <div id="rutinas-asignadas"></div>
            </div>
        `;
        const pintarAsignadas = () => {
            const filtro = document.getElementById('filtro-socio');
            const idSocio = filtro ? filtro.value : '';
            const items = idSocio
                ? asignadas.filter((r) => String(r.id_socio) === String(idSocio))
                : asignadas;
            document.getElementById('rutinas-asignadas').innerHTML = cardsRutinas(
                items,
                idSocio ? t('no_routines_member') : t('nothing_yet')
            );
            document.querySelectorAll('[data-quitar-rutina]').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const id = Number(btn.dataset.quitarRutina);
                    btn.disabled = true;
                    const res = await API.del('/rutinas/' + id);
                    if (res.status !== 'ok') {
                        btn.disabled = false;
                        alert(I18n.api(res.message, 'unassign_fail'));
                        return;
                    }
                    const i = asignadas.findIndex((x) => Number(x.id_rutina) === id);
                    if (i >= 0) asignadas.splice(i, 1);
                    pintarAsignadas();
                });
            });
        };
        document.getElementById('filtro-socio')?.addEventListener('change', pintarAsignadas);
        pintarAsignadas();
        document.getElementById('btn-crear')?.addEventListener('click', crearRutina);
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

    function vistaSocios() {
        document.getElementById('titulo-pagina').textContent = t('my_members');
        contenido.innerHTML = `
            <article class="card">
                <div class="table-scroll" id="socios-tabla">
                <table class="table">
                    <thead><tr><th>${t('th_name')}</th><th>${t('th_email')}</th><th>${t('th_status')}</th><th>${t('th_actions')}</th></tr></thead>
                    <tbody>${filasSocios(socios)}</tbody>
                </table>
                </div>
                <input class="table-hscroll table-hscroll-mobile-only" id="socios-barra" type="range" min="0" value="0" step="1" aria-label="${t('scroll_members')}">
            </article>
        `;
        bindTablaSocios();
    }

    function vistaEjercicios() {
        document.getElementById('titulo-pagina').textContent = t('exercises_title');
        const opcionesGrupo = `<option value="">${t('pick_group')}</option>` + grupos.map((g) =>
            `<option value="${g.nombre}">${g.nombre}</option>`
        ).join('');
        contenido.innerHTML = `
            <article class="card">
                <div class="alert" id="ej-error"></div>
                <div class="form-inline">
                    <div class="field">
                        <label>${t('th_name')} <span class="req">*</span></label>
                        <input id="ej-nombre" placeholder="Sentadilla">
                    </div>
                    <div class="field">
                        <label>${t('th_group')} <span class="req">*</span></label>
                        <select id="ej-grupo" class="plain">${opcionesGrupo}</select>
                    </div>
                    <div class="field" style="min-width:220px">
                        <label>${t('label_desc')} <span class="muted">${t('optional')}</span></label>
                        <input id="ej-desc">
                    </div>
                    <button class="btn btn-navy" id="ej-crear" ${grupos.length ? '' : 'disabled'}>${t('add')}</button>
                </div>
                ${grupos.length ? '' : `<p class="muted" style="margin-top:12px">${t('no_groups_trainer')}</p>`}
                <table class="table" style="margin-top:16px">
                    <thead><tr><th></th><th>${t('th_name')}</th><th>${t('th_group')}</th><th></th></tr></thead>
                    <tbody>
                        ${ejercicios.map((e) => `<tr>
                            <td><img class="exercise-gif-thumb" src="${e.imagen || '../imagenes/ejercicios/sentadilla.gif'}${String(e.imagen || '').includes('?') ? '' : '?v=fp-rack'}" alt=""></td>
                            <td>${e.nombre}</td>
                            <td>${e.grupo_muscular || ''}</td>
                            <td><button class="btn btn-ghost btn-small" data-del="${e.id_ejercicio}">${t('delete_btn')}</button></td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </article>
        `;
        document.getElementById('ej-crear').addEventListener('click', async () => {
            const error = document.getElementById('ej-error');
            error.style.display = 'none';
            const nombre = document.getElementById('ej-nombre').value.trim();
            const grupo = document.getElementById('ej-grupo').value.trim();
            const descripcion = document.getElementById('ej-desc').value.trim();
            if (!nombre || !grupo) {
                error.textContent = t('fill_name_group');
                error.style.display = 'block';
                return;
            }
            const res = await API.post('/ejercicios', {
                nombre,
                grupo_muscular: grupo,
                descripcion
            });
            if (res.status === 'ok') location.reload();
            else {
                error.textContent = I18n.api(res.message);
                error.style.display = 'block';
            }
        });
        contenido.querySelectorAll('[data-del]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const res = await API.del('/ejercicios/' + btn.dataset.del);
                if (res.status === 'ok') location.reload();
                else alert(I18n.api(res.message));
            });
        });
    }

    async function crearRutina() {
        const nombre = (document.getElementById('nueva-nombre')?.value || '').trim();
        if (!nombre) {
            alert(t('prompt_routine'));
            return;
        }
        const idSocio = Number(document.getElementById('nueva-socio')?.value || 0);
        if (!idSocio) {
            alert(t('pick_member_routine'));
            return;
        }
        const objetivo = (document.getElementById('nueva-objetivo')?.value || '').trim();
        const res = await API.post('/rutinas', { nombre, objetivo, id_socio: idSocio });
        if (res.status !== 'ok') {
            alert(I18n.api(res.message));
            return;
        }
        location.href = FRONT.rutina + '?id=' + res.rutina.id_rutina;
    }

    const vistas = {
        inicio: vistaInicio,
        rutinas: vistaRutinas,
        socios: vistaSocios,
        entrenadores: vistaSocios,
        ejercicios: vistaEjercicios,
        notificaciones: () => AuthUI.vistaNotificaciones(),
        config: () => AuthUI.vistaConfig(usuario)
    };
    await Promise.resolve((vistas[hash] || vistaInicio)());
    window.addEventListener('hashchange', () => location.reload());
    FitLoader.hide();
})();
