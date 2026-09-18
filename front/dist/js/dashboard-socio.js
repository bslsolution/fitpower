(async function () {
    const usuario = await AuthUI.exigir(['socio']);
    if (!usuario) return;
    const t = (key, vars) => I18n.t(key, vars);

    const hash = AuthUI.hash();
    const matchEnt = hash.match(/^entrenamiento-(\d+)$/);
    const activo = (matchEnt || ['rutinas', 'notificaciones', 'entrenadores', 'progreso', 'fitpoints', 'config', 'mensaje'].includes(hash))
        ? (matchEnt ? 'progreso' : hash)
        : 'inicio';
    document.getElementById('app').innerHTML = AuthUI.shell(usuario, activo);
    AuthUI.bindLogout();

    const data = await API.get('/mis-rutinas');
    const rutinas = data.rutinas || [];
    const entrenador = data.entrenador;
    const contenido = document.getElementById('contenido');
    const activa = data.membresia === 'activa';

    function vistaInicio() {
        document.getElementById('titulo-pagina').textContent = t('nav_home');
        const preview = rutinas.slice(0, 3);
        const cards = preview.length
            ? preview.map((r) => `
                <article class="routine-card">
                    <img src="../imagenes/mancuerna-negro.png" class="mini-icon" alt="">
                    <h3>${r.nombre}</h3>
                    <p class="muted">${r.objetivo || t('assigned_routine')}</p>
                    <a class="btn btn-primary btn-small" href="${FRONT.rutina}?id=${r.id_rutina}">${t('see_routine')}</a>
                </article>
            `).join('')
            : `<p class="muted">${t('no_routines_yet')}</p>`;

        contenido.innerHTML = `
            <article class="card fp-balance">
                <img src="../imagenes/FitPoints-navy.png" alt="">
                <div class="fp-balance-text">
                    <p class="muted">${t('fp_your_points')}</p>
                    <strong>${Number(usuario.fitpoints) || 0}</strong>
                    <p class="muted">${t('fp_earn_help')}</p>
                </div>
                <a class="btn btn-navy btn-small" href="#fitpoints">${t('fp_see_rewards')}</a>
            </article>
            <div class="grid-3">
                <article class="card status-card">
                    <h3>${t('membership_status')}</h3>
                    <div class="membership ${activa ? '' : 'status-off'}">
                        <div class="check">${activa ? '✓' : '!'}</div>
                        <div>
                            <strong>${activa ? t('membership_on') : t('membership_off')}</strong>
                            <p class="muted">${activa ? t('membership_ok') : t('membership_bad')}</p>
                        </div>
                    </div>
                    <div class="card-actions">
                        ${data.vence_el ? `<span class="muted">${t('membership_expires', { date: data.vence_el })}</span>` : ''}
                        <a class="btn btn-ghost btn-small" href="#config">${t('see_details')}</a>
                    </div>
                </article>
                <article class="card">
                    <h3>${t('assigned_coach')}</h3>
                    <div class="membership">
                        <div class="avatar">${entrenador ? AuthUI.iniciales(entrenador) : '?'}</div>
                        <div>
                            <strong>${entrenador ? entrenador.nombre + ' ' + entrenador.apellido : t('unassigned')}</strong>
                            <p class="muted">${entrenador ? (entrenador.especialidad || t('personal_trainer')) : t('coach_pending')}</p>
                        </div>
                    </div>
                    <div class="card-actions">
                        ${entrenador ? `<a class="btn btn-navy btn-small" href="#mensaje">${t('send_message')}</a>` : ''}
                    </div>
                </article>
                <article class="card photo-card">
                    <h3>${rutinas[0] ? rutinas[0].nombre : t('your_routines')}</h3>
                    <p>${rutinas[0] ? (rutinas[0].objetivo || '') : t('start_when_assigned')}</p>
                    ${rutinas[0] ? `<a class="btn btn-primary btn-small" href="${FRONT.rutina}?id=${rutinas[0].id_rutina}">${t('see_my_routines')}</a>` : ''}
                </article>
            </div>
            <section class="section-block" id="rutinas">
                <div class="card-head">
                    <h3>${t('nav_routines')}</h3>
                    <a class="link-all" href="#rutinas">${t('see_all_short')}</a>
                </div>
                <div class="routine-cards">${cards}</div>
            </section>
        `;
    }

    function vistaRutinas() {
        document.getElementById('titulo-pagina').textContent = t('nav_routines');
        contenido.innerHTML = `
            <div class="routine-cards">
                ${rutinas.length ? rutinas.map((r) => `
                    <article class="routine-card">
                        <img src="../imagenes/mancuerna-negro.png" class="mini-icon" alt="">
                        <h3>${r.nombre}</h3>
                        <p class="muted">${r.objetivo || ''}</p>
                        <p class="muted">${r.entrenador_nombre || ''} ${r.entrenador_apellido || ''}</p>
                        <a class="btn btn-primary btn-small" href="${FRONT.rutina}?id=${r.id_rutina}">${t('see_routine')}</a>
                    </article>
                `).join('') : `<p class="muted">${t('no_routines_yet')}</p>`}
            </div>
        `;
    }

    function vistaEntrenadores() {
        document.getElementById('titulo-pagina').textContent = t('nav_coaches');
        contenido.innerHTML = entrenador ? `
            <article class="card" style="max-width:420px">
                <div class="membership">
                    <div class="avatar">${AuthUI.iniciales(entrenador)}</div>
                    <div>
                        <strong>${entrenador.nombre} ${entrenador.apellido}</strong>
                        <p class="muted">${entrenador.especialidad || t('personal_trainer')}</p>
                    </div>
                </div>
            </article>
        ` : `<article class="card"><p class="muted">${t('no_coach')}</p></article>`;
    }

    function vistaMensaje() {
        document.getElementById('titulo-pagina').textContent = t('message_soon_title');
        contenido.innerHTML = `
            <article class="card coming-soon">
                <div class="notify-icon notify-icon--purple" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m3 7 9 6 9-6"></path>
                    </svg>
                </div>
                <h2>${t('message_soon_title')}</h2>
                <p>${t('message_soon')}</p>
                <p class="muted">${t('message_soon_help')}</p>
                <a class="btn btn-navy btn-small" href="#">${t('back')}</a>
            </article>
        `;
    }

    function fechaCorta(valor) {
        const d = new Date(String(valor || '').replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return valor || '';
        return d.toLocaleString(I18n.locale(), {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    async function vistaProgreso() {
        document.getElementById('titulo-pagina').textContent = t('progress_title');
        contenido.innerHTML = `<article class="card"><p class="muted">${t('loading_workouts')}</p></article>`;
        const res = await API.get('/progreso');
        if (res.status !== 'ok') {
            contenido.innerHTML = `<article class="card"><p class="muted">${I18n.api(res.message, 'progress_fail')}</p></article>`;
            return;
        }
        const entrenamientos = res.entrenamientos || [];
        if (!entrenamientos.length) {
            contenido.innerHTML = `
                <article class="card">
                    <h3>${t('no_workouts_title')}</h3>
                    <p class="muted">${t('no_workouts_help')}</p>
                    <a class="btn btn-navy btn-small" href="#rutinas">${t('go_routines')}</a>
                </article>
            `;
            return;
        }
        contenido.innerHTML = entrenamientos.map((e) => `
            <article class="card progress-card">
                <div class="card-head">
                    <div>
                        <h3>${e.nombre}</h3>
                        <p class="muted">${fechaCorta(e.fecha)} · ${I18n.seriesLabel(e.series_count)}</p>
                    </div>
                    <a class="btn btn-navy btn-small" href="#entrenamiento-${e.id_entrenamiento}">${t('see_workout')}</a>
                </div>
            </article>
        `).join('');
    }

    async function vistaEntrenamiento(idEntrenamiento) {
        document.getElementById('titulo-pagina').textContent = t('workout_title');
        contenido.innerHTML = `<article class="card"><p class="muted">${t('loading_workout')}</p></article>`;
        const res = await API.get('/progreso/' + idEntrenamiento);
        if (res.status !== 'ok') {
            contenido.innerHTML = `
                <article class="card">
                    <p class="muted">${I18n.api(res.message, 'workout_not_found')}</p>
                    <a class="btn btn-ghost btn-small" href="#progreso">${t('back_progress')}</a>
                </article>
            `;
            return;
        }
        const ent = res.entrenamiento;
        const ejercicios = res.ejercicios || [];
        contenido.innerHTML = `
            <p style="margin:0 0 16px"><a href="#progreso">${t('back_progress')}</a></p>
            <article class="card progress-card">
                <h3>${ent.nombre}</h3>
                <p class="muted">${fechaCorta(ent.fecha)}</p>
            </article>
            ${ejercicios.map((ej) => `
                <article class="card progress-card">
                    <h3>${ej.nombre}</h3>
                    <p class="muted">${ej.grupo_muscular || ''}</p>
                    <table class="table">
                        <thead><tr><th>${t('th_set')}</th><th>${t('th_reps')}</th><th>${t('th_weight')}</th><th>${t('th_rest')}</th></tr></thead>
                        <tbody>
                            ${(ej.series || []).map((s) => `
                                <tr>
                                    <td>${s.serie_orden}</td>
                                    <td>${s.repeticiones}</td>
                                    <td><strong>${s.peso_kg ?? '—'}</strong></td>
                                    <td>${s.descanso_segundos ?? '—'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </article>
            `).join('')}
        `;
    }

    async function vistaFitPoints() {
        document.getElementById('titulo-pagina').textContent = t('nav_fitpoints');
        contenido.innerHTML = `<article class="card"><p class="muted">${t('fp_loading')}</p></article>`;
        const res = await API.get('/fitpoints');
        if (res.status !== 'ok') {
            contenido.innerHTML = `<article class="card"><p class="muted">${I18n.api(res.message)}</p></article>`;
            return;
        }
        const puntos = Number(res.puntos) || 0;
        const recompensas = res.recompensas || [];
        const chip = document.querySelector('.fp-points-chip strong');
        if (chip) chip.textContent = String(puntos);
        contenido.innerHTML = `
            <section class="fitpoints-main">
                <article class="card fp-balance">
                    <img src="../imagenes/FitPoints-navy.png" alt="">
                    <div class="fp-balance-text">
                        <p class="muted">${t('fp_your_points')}</p>
                        <strong>${puntos}</strong>
                        <p class="muted">${t('fp_earn_help')}</p>
                    </div>
                </article>
                <h3 class="fp-catalog-title">${t('fp_rewards')}</h3>
                <p class="muted fp-catalog-help">${t('fp_rewards_help')}</p>
                <div class="reward-grid">
                    ${recompensas.length
                        ? recompensas.map((r) => tarjetaSocio(r, puntos)).join('')
                        : `<p class="muted">${t('fp_empty')}</p>`}
                </div>
            </section>
        `;
        contenido.querySelectorAll('[data-canjear]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const nombre = btn.dataset.nombre || '';
                const costo = btn.dataset.costo || '';
                if (!confirm(t('fp_redeem_confirm', { name: nombre, n: costo }))) return;
                btn.disabled = true;
                const res = await API.post('/fitpoints/canjear', { id_recompensa: Number(btn.dataset.canjear) });
                if (res.status === 'ok') {
                    alert(t('fp_redeemed', { name: res.nombre || nombre, total: res.puntos }));
                    location.reload();
                    return;
                }
                btn.disabled = false;
                alert(I18n.api(res.message));
            });
        });
    }

    function tarjetaSocio(r, puntos) {
        const nombre = String(r.nombre || '');
        const desc = String(r.descripcion || '');
        const costo = Number(r.costo_puntos) || 0;
        const locked = costo > puntos;
        const nombreAttr = nombre.replace(/"/g, '&quot;');
        return `
            <article class="reward-card is-view${locked ? ' is-locked' : ''}">
                <div class="reward-photo">
                    <img src="${r.imagen}" alt="${nombreAttr}">
                </div>
                <strong>${nombre}</strong>
                ${desc ? `<span class="reward-desc">${desc}</span>` : ''}
                <span class="reward-cost">${costo} FitPoints</span>
                ${locked
                    ? `<span class="reward-status">${t('fp_missing', { n: costo - puntos })}</span>`
                    : `<div class="reward-actions">
                        <button type="button" class="btn btn-navy btn-small" data-canjear="${r.id_recompensa}" data-nombre="${nombreAttr}" data-costo="${costo}">${t('fp_redeem')}</button>
                    </div>`}
            </article>
        `;
    }

    const vistas = {
        inicio: vistaInicio,
        rutinas: vistaRutinas,
        notificaciones: () => AuthUI.vistaNotificaciones(),
        entrenadores: vistaEntrenadores,
        mensaje: vistaMensaje,
        progreso: vistaProgreso,
        fitpoints: vistaFitPoints,
        config: () => AuthUI.vistaConfig(usuario)
    };
    if (matchEnt) await vistaEntrenamiento(matchEnt[1]);
    else await (vistas[hash] || vistaInicio)();
    window.addEventListener('hashchange', () => location.reload());
    FitLoader.hide();
})();
