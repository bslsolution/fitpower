(async function () {
    const params = new URLSearchParams(location.search);
    const id = params.get('id');
    if (!id) {
        location.href = FRONT.landing;
        return;
    }

    const me = await AuthUI.me();
    if (me.status !== 'ok') {
        location.href = FRONT.login;
        return;
    }
    const usuario = me.usuario;
    const t = (key, vars) => I18n.t(key, vars);
    const app = document.getElementById('app');
    app.innerHTML = AuthUI.shell(usuario, 'rutinas');
    AuthUI.bindLogout();

    const data = await API.get('/rutinas/' + id);
    if (data.status !== 'ok') {
        document.getElementById('contenido').innerHTML = `<article class="card">${I18n.api(data.message)}</article>`;
        FitLoader.hide();
        return;
    }

    const soloLectura = data.solo_lectura;
    document.getElementById('titulo-pagina').textContent = data.rutina.nombre;
    const contenido = document.getElementById('contenido');
    let ejercicios = data.ejercicios || [];
    const catalogo = soloLectura ? { ejercicios: [] } : await API.get('/ejercicios');
    const socios = soloLectura ? { socios: [] } : await API.get('/socios');
    let idEjercicioElegido = null;
    const GIF_BASE = '../imagenes/ejercicios/';
    const crono = { running: false, startedAt: 0, accumulated: 0, tick: null };
    const storageKey = 'fp-entrenamiento-' + id;
    let idEntrenamiento = Number(sessionStorage.getItem(storageKey)) || null;

    function normalizar(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function gifDeEjercicio(ej) {
        if (ej.url_gif) return ej.url_gif;
        const n = normalizar(ej.nombre);
        const reglas = [
            { k: ['sentadilla', 'squat'], f: 'sentadilla.gif' },
            { k: ['press banca', 'press de banca', 'bench'], f: 'press-banca.gif' },
            { k: ['remo'], f: 'remo.gif' },
            { k: ['plancha', 'plank'], f: 'plancha.gif' },
            { k: ['peso muerto', 'deadlift'], f: 'peso-muerto.gif' },
            { k: ['curl', 'biceps'], f: 'curl.gif' },
            { k: ['militar', 'hombro', 'overhead', 'laterales'], f: 'press-militar.gif' },
            { k: ['dominada', 'pull'], f: 'dominadas.gif' },
            { k: ['flexion', 'push', 'fondos'], f: 'flexiones.gif' },
            { k: ['abdominal', 'crunch', 'sit'], f: 'crunch.gif' },
            { k: ['elevacion', 'leg raise'], f: 'elevaciones.gif' },
            { k: ['burpee'], f: 'burpees.gif' },
            { k: ['extension'], f: 'extensiones.gif' },
            { k: ['rodilla', 'high knee'], f: 'rodillas.gif' }
        ];
        const hit = reglas.find((r) => r.k.some((k) => n.includes(k)));
        if (hit) return GIF_BASE + hit.f;
        const g = normalizar(ej.grupo_muscular);
        const porGrupo = {
            pierna: 'sentadilla.gif',
            pecho: 'press-banca.gif',
            espalda: 'remo.gif',
            core: 'plancha.gif',
            hombros: 'press-militar.gif',
            hombro: 'press-militar.gif',
            brazos: 'curl.gif',
            biceps: 'curl.gif',
            triceps: 'flexiones.gif'
        };
        return GIF_BASE + (porGrupo[g] || 'sentadilla.gif');
    }

    function formatoTiempo(ms) {
        const t = Math.max(0, Math.floor(ms / 1000));
        const h = Math.floor(t / 3600);
        const m = Math.floor((t % 3600) / 60);
        const s = t % 60;
        const mm = String(m).padStart(2, '0');
        const ss = String(s).padStart(2, '0');
        return h > 0 ? h + ':' + mm + ':' + ss : mm + ':' + ss;
    }

    function tiempoCrono() {
        return crono.accumulated + (crono.running ? Date.now() - crono.startedAt : 0);
    }

    function pintarCrono() {
        const el = document.getElementById('crono-tiempo');
        if (el) el.textContent = formatoTiempo(tiempoCrono());
    }

    function syncCronoBotones() {
        const start = document.getElementById('crono-start');
        const stop = document.getElementById('crono-stop');
        if (!start || !stop) return;
        start.disabled = crono.running;
        stop.disabled = !crono.running;
        start.textContent = (!crono.running && crono.accumulated > 0) ? t('resume') : t('start');
    }

    function detenerCrono() {
        if (!crono.running) return;
        crono.accumulated = tiempoCrono();
        crono.running = false;
        crono.startedAt = 0;
        clearInterval(crono.tick);
        crono.tick = null;
    }

    function bindCronometro() {
        const start = document.getElementById('crono-start');
        const stop = document.getElementById('crono-stop');
        const reset = document.getElementById('crono-reset');
        if (!start || !stop || !reset) return;
        if (crono.tick) {
            clearInterval(crono.tick);
            crono.tick = null;
        }
        pintarCrono();
        syncCronoBotones();
        if (crono.running) crono.tick = setInterval(pintarCrono, 200);
        start.onclick = () => {
            if (crono.running) return;
            crono.running = true;
            crono.startedAt = Date.now();
            crono.tick = setInterval(pintarCrono, 200);
            syncCronoBotones();
        };
        stop.onclick = () => {
            detenerCrono();
            pintarCrono();
            syncCronoBotones();
        };
        reset.onclick = () => {
            crono.running = false;
            crono.accumulated = 0;
            crono.startedAt = 0;
            clearInterval(crono.tick);
            crono.tick = null;
            pintarCrono();
            syncCronoBotones();
        };
    }

    function marcarBotonTerminado(btn) {
        btn.textContent = t('finished');
        btn.classList.remove('btn-navy');
        btn.classList.add('btn-ghost', 'is-done-set');
        syncTerminarRutina();
    }

    function resetBotonesSeries() {
        contenido.querySelectorAll('[data-terminar]').forEach((btn) => {
            btn.textContent = t('finish');
            btn.classList.add('btn-navy');
            btn.classList.remove('btn-ghost', 'is-done-set');
            btn.disabled = false;
        });
        syncTerminarRutina();
    }

    function haySeriesTerminadas() {
        return [...contenido.querySelectorAll('[data-terminar]')].some((btn) =>
            btn.classList.contains('is-done-set')
        );
    }

    function syncTerminarRutina() {
        const btn = document.getElementById('btn-terminar-rutina');
        if (!btn) return;
        btn.disabled = !haySeriesTerminadas();
    }

    function mostrarAviso(texto) {
        let el = document.getElementById('fp-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'fp-toast';
            el.className = 'fp-toast';
            document.body.appendChild(el);
        }
        el.textContent = texto;
        el.classList.add('is-on');
        clearTimeout(el._hide);
        el._hide = setTimeout(() => el.classList.remove('is-on'), 4200);
    }

    async function cerrarEntrenamiento() {
        const idCerrado = idEntrenamiento;
        idEntrenamiento = null;
        sessionStorage.removeItem(storageKey);
        resetBotonesSeries();
        crono.running = false;
        crono.accumulated = 0;
        crono.startedAt = 0;
        clearInterval(crono.tick);
        crono.tick = null;
        pintarCrono();
        syncCronoBotones();
        if (usuario.rol === 'socio' && idCerrado) {
            const res = await API.post('/fitpoints/completar-rutina', { id_entrenamiento: idCerrado });
            if (res.status === 'ok' && res.otorgados) {
                mostrarAviso(t('fp_earned', { n: res.sumados, total: res.puntos }));
                const chip = document.querySelector('.fp-points-chip strong');
                if (chip) chip.textContent = String(res.puntos);
            } else if (res.status !== 'ok') {
                mostrarAviso(I18n.api(res.message));
            }
        }
    }

    async function marcarTerminadas() {
        if (!idEntrenamiento) return;
        const res = await API.get('/progreso/' + idEntrenamiento);
        if (res.status !== 'ok') return;
        const ids = new Set();
        (res.ejercicios || []).forEach((ej) => {
            (ej.series || []).forEach((s) => {
                if (s.id_serie_origen) ids.add(String(s.id_serie_origen));
            });
        });
        contenido.querySelectorAll('[data-terminar]').forEach((btn) => {
            if (ids.has(btn.dataset.terminar)) marcarBotonTerminado(btn);
        });
    }

    function coincidencias(q) {
        const n = normalizar(q);
        if (!n) return [];
        return (catalogo.ejercicios || []).filter((e) =>
            normalizar(e.nombre).includes(n) || normalizar(e.grupo_muscular).includes(n)
        ).slice(0, 12);
    }

    function pintarResultados(q) {
        const lista = document.getElementById('r-ejercicio-list');
        if (!lista) return;
        const texto = (q || '').trim();
        if (!texto) {
            lista.hidden = true;
            lista.innerHTML = '';
            return;
        }
        const items = coincidencias(texto);
        lista.hidden = false;
        if (!items.length) {
            lista.innerHTML = `<li class="empty">${t('no_match')}</li>`;
            return;
        }
        lista.innerHTML = items.map((e, i) => `
            <li data-id="${e.id_ejercicio}" class="${i === 0 ? 'active' : ''}">
                <strong>${e.nombre}</strong>
                <span class="muted">${e.grupo_muscular || ''}</span>
            </li>
        `).join('');
    }

    function elegirEjercicio(id) {
        const ej = (catalogo.ejercicios || []).find((e) => String(e.id_ejercicio) === String(id));
        if (!ej) return;
        idEjercicioElegido = Number(ej.id_ejercicio);
        const input = document.getElementById('r-ejercicio-q');
        const lista = document.getElementById('r-ejercicio-list');
        if (input) input.value = ej.nombre;
        if (lista) {
            lista.hidden = true;
            lista.innerHTML = '';
        }
    }

    function bindBuscarEjercicio() {
        const input = document.getElementById('r-ejercicio-q');
        const lista = document.getElementById('r-ejercicio-list');
        if (!input || !lista) return;

        input.addEventListener('input', () => {
            const actual = (catalogo.ejercicios || []).find((e) =>
                Number(e.id_ejercicio) === idEjercicioElegido && e.nombre === input.value
            );
            if (!actual) idEjercicioElegido = null;
            pintarResultados(input.value);
        });
        input.addEventListener('focus', () => {
            if (input.value.trim() && !idEjercicioElegido) pintarResultados(input.value);
        });
        input.addEventListener('keydown', (ev) => {
            const opciones = [...lista.querySelectorAll('li[data-id]')];
            if (ev.key === 'Escape') {
                lista.hidden = true;
                return;
            }
            if (!opciones.length) return;
            const actual = lista.querySelector('li.active');
            let idx = opciones.indexOf(actual);
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                idx = Math.min((idx < 0 ? -1 : idx) + 1, opciones.length - 1);
                opciones.forEach((li) => li.classList.toggle('active', li === opciones[idx]));
                opciones[idx].scrollIntoView({ block: 'nearest' });
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                idx = Math.max((idx < 0 ? 0 : idx) - 1, 0);
                opciones.forEach((li) => li.classList.toggle('active', li === opciones[idx]));
                opciones[idx].scrollIntoView({ block: 'nearest' });
            } else if (ev.key === 'Enter') {
                ev.preventDefault();
                const elegido = opciones[idx] || opciones[0];
                elegirEjercicio(elegido.dataset.id);
            }
        });
        lista.addEventListener('mousedown', (ev) => {
            const li = ev.target.closest('li[data-id]');
            if (!li) return;
            ev.preventDefault();
            elegirEjercicio(li.dataset.id);
        });
        input.addEventListener('blur', () => {
            setTimeout(() => { lista.hidden = true; }, 120);
        });
    }

    function pintar() {
        idEjercicioElegido = null;
        const bloques = ejercicios.map((ej, idx) => `
            <article class="exercise-block" draggable="${soloLectura ? 'false' : 'true'}" data-id="${ej.id_rutina_ejercicio}">
                <div class="exercise-media">
                    <img src="${gifDeEjercicio(ej)}" alt="${t('how_to_alt', { name: ej.nombre })}" class="exercise-gif" onerror="this.onerror=null;this.src='../imagenes/mancuerna-negro.png'">
                    <p class="exercise-gif-caption">${t('how_to')}</p>
                </div>
                <div class="exercise-body">
                    <div class="exercise-head">
                        <div>
                            <span class="drag-handle">${soloLectura ? idx + 1 : '☰ ' + (idx + 1)}</span>
                            <strong>${ej.nombre}</strong>
                            <span class="muted"> · ${ej.grupo_muscular || ''}</span>
                            ${ej.notas ? `<p class="muted">${ej.notas}</p>` : ''}
                        </div>
                        ${soloLectura ? '' : `
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-small" data-up="${ej.id_rutina_ejercicio}">↑</button>
                            <button class="btn btn-ghost btn-small" data-down="${ej.id_rutina_ejercicio}">↓</button>
                            <button class="btn btn-danger btn-small" data-quitar="${ej.id_rutina_ejercicio}">${t('remove')}</button>
                        </div>`}
                    </div>
                    <table class="table">
                        <thead><tr><th>${t('th_set')}</th><th>${t('th_reps')}</th><th>${t('th_weight')}</th><th>${t('th_rest')}</th><th></th></tr></thead>
                        <tbody>
                            ${(ej.series || []).map((s) => `
                                <tr>
                                    <td>${s.orden}</td>
                                    <td>${soloLectura ? s.repeticiones : `<input data-serie="${s.id_serie}" data-campo="repeticiones" value="${s.repeticiones}" style="width:70px">`}</td>
                                    <td>${soloLectura
                                        ? `<input class="peso-input" data-peso-local="${s.id_serie}" type="number" min="0" step="0.5" inputmode="decimal" placeholder="${t('weight_ph')}" value="${s.peso_kg ?? ''}">`
                                        : `<input class="peso-input" data-serie="${s.id_serie}" data-campo="peso_kg" type="number" min="0" step="0.5" inputmode="decimal" placeholder="${t('weight_ph')}" value="${s.peso_kg ?? ''}">`}</td>
                                    <td>${soloLectura ? (s.descanso_segundos ?? '—') : `<input data-serie="${s.id_serie}" data-campo="descanso_segundos" value="${s.descanso_segundos ?? ''}" style="width:80px">`}</td>
                                    <td>${soloLectura
                                        ? `<button class="btn btn-navy btn-small" type="button" data-terminar="${s.id_serie}">${t('finish')}</button>`
                                        : `<button class="btn btn-ghost btn-small" data-borrar-serie="${s.id_serie}">x</button>`}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    ${soloLectura ? '' : `
                    <div class="form-inline" style="margin-top:8px">
                        <div class="field"><label>${t('th_reps')}</label><input id="reps-${ej.id_rutina_ejercicio}" value="10"></div>
                        <div class="field"><label>${t('th_weight').replace(' (kg)', '')}</label><input id="peso-${ej.id_rutina_ejercicio}" placeholder="${t('weight_optional')}"></div>
                        <div class="field"><label>${t('th_rest')}</label><input id="desc-${ej.id_rutina_ejercicio}" value="90"></div>
                        <button class="btn btn-primary btn-small" data-add-serie="${ej.id_rutina_ejercicio}">${t('add_set')}</button>
                    </div>`}
                </div>
            </article>
        `).join('');

        contenido.innerHTML = `
            ${soloLectura ? `
            <section class="workout-timer" aria-label="${t('timer')}">
                <div>
                    <p class="timer-label">${t('timer')}</p>
                    <div class="timer-time" id="crono-tiempo">${formatoTiempo(tiempoCrono())}</div>
                </div>
                <div class="timer-actions">
                    <button class="btn btn-primary" id="crono-start" type="button">${t('start')}</button>
                    <button class="btn btn-ghost" id="crono-stop" type="button" disabled>${t('stop')}</button>
                    <button class="btn btn-ghost" id="crono-reset" type="button">${t('reset')}</button>
                </div>
            </section>
            <p class="muted" style="margin:-4px 0 16px">${t('finish_help')}</p>` : ''}
            <article class="card">
                <p class="muted">${data.rutina.objetivo || ''} ${data.rutina.socio_nombre ? t('assigned_to', { name: data.rutina.socio_nombre + ' ' + data.rutina.socio_apellido }) : t('template_label')}</p>
                ${soloLectura ? '' : `
                <div class="form-inline" style="margin-bottom:16px">
                    <div class="field"><label>${t('th_name')}</label><input id="r-nombre" value="${data.rutina.nombre}"></div>
                    <div class="field"><label>${t('label_goal')}</label><input id="r-objetivo" value="${data.rutina.objetivo || ''}"></div>
                    <button class="btn btn-navy btn-small" id="r-guardar">${t('save')}</button>
                </div>
                <div class="form-inline" style="margin-bottom:16px">
                    <div class="field search-field">
                        <label>${t('add_exercise')}</label>
                        <div class="exercise-search">
                            <input id="r-ejercicio-q" type="search" placeholder="${t('search_ex')}" autocomplete="off">
                            <ul id="r-ejercicio-list" class="search-results" hidden></ul>
                        </div>
                    </div>
                    <div class="field"><label>${t('notes')}</label><input id="r-notas" placeholder="${t('notes_ph')}"></div>
                    <button class="btn btn-primary btn-small" id="r-add-ej">${t('add_to_routine')}</button>
                </div>
                <div class="form-inline" style="margin-bottom:16px">
                    <div class="field"><label>${t('assign_copy')}</label>
                        <select id="r-socio" class="plain">
                            ${(socios.socios || []).map((s) => `<option value="${s.id_socio}">${s.nombre} ${s.apellido}</option>`).join('')}
                        </select>
                    </div>
                    <button class="btn btn-navy btn-small" id="r-asignar">${t('assign')}</button>
                </div>`}
                ${bloques || `<p class="muted">${t('no_exercises')}</p>`}
                ${soloLectura && bloques ? `
                <div class="routine-end-actions">
                    <button class="btn btn-navy" type="button" id="btn-terminar-rutina" disabled>${t('finish_routine')}</button>
                    <p class="muted" style="margin:0">${t('finish_routine_help')}</p>
                </div>` : ''}
                <p style="margin-top:16px"><a href="${FRONT.dashboards[usuario.rol]}">${t('back')}</a></p>
            </article>
        `;
        bind();
        if (soloLectura) {
            bindCronometro();
            marcarTerminadas().then(() => syncTerminarRutina());
            const fin = document.getElementById('btn-terminar-rutina');
            if (fin) fin.addEventListener('click', cerrarEntrenamiento);
        }
    }

    async function recargar() {
        const nuevo = await API.get('/rutinas/' + id);
        if (nuevo.status === 'ok') {
            ejercicios = nuevo.ejercicios || [];
            data.rutina = nuevo.rutina;
            pintar();
        }
    }

    async function guardarOrden() {
        const ids = ejercicios.map((e) => e.id_rutina_ejercicio);
        await API.put('/rutinas/' + id + '/ejercicios/orden', { orden: ids });
        await recargar();
    }

    function bind() {
        const guardar = document.getElementById('r-guardar');
        if (guardar) {
            guardar.addEventListener('click', async () => {
                const res = await API.put('/rutinas/' + id, {
                    nombre: document.getElementById('r-nombre').value,
                    objetivo: document.getElementById('r-objetivo').value,
                    estado: data.rutina.estado
                });
                if (res.status !== 'ok') alert(I18n.api(res.message));
                else recargar();
            });
        }
        bindBuscarEjercicio();
        const addEj = document.getElementById('r-add-ej');
        if (addEj) {
            addEj.addEventListener('click', async () => {
                if (!idEjercicioElegido) {
                    alert(t('pick_exercise'));
                    document.getElementById('r-ejercicio-q')?.focus();
                    return;
                }
                const res = await API.post('/rutinas/' + id + '/ejercicios', {
                    id_ejercicio: idEjercicioElegido,
                    notas: document.getElementById('r-notas').value
                });
                if (res.status !== 'ok') alert(I18n.api(res.message));
                else recargar();
            });
        }
        const asignar = document.getElementById('r-asignar');
        if (asignar) {
            asignar.addEventListener('click', async () => {
                const res = await API.post('/rutinas/' + id + '/asignar', {
                    id_socio: Number(document.getElementById('r-socio').value)
                });
                if (res.status !== 'ok') alert(I18n.api(res.message));
                else location.href = FRONT.rutina + '?id=' + res.rutina.id_rutina;
            });
        }
        contenido.querySelectorAll('[data-up]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const i = ejercicios.findIndex((e) => String(e.id_rutina_ejercicio) === btn.dataset.up);
                if (i > 0) {
                    [ejercicios[i - 1], ejercicios[i]] = [ejercicios[i], ejercicios[i - 1]];
                    await guardarOrden();
                }
            });
        });
        contenido.querySelectorAll('[data-down]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const i = ejercicios.findIndex((e) => String(e.id_rutina_ejercicio) === btn.dataset.down);
                if (i >= 0 && i < ejercicios.length - 1) {
                    [ejercicios[i + 1], ejercicios[i]] = [ejercicios[i], ejercicios[i + 1]];
                    await guardarOrden();
                }
            });
        });
        contenido.querySelectorAll('[data-quitar]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                await API.del('/rutina-ejercicios/' + btn.dataset.quitar);
                recargar();
            });
        });
        contenido.querySelectorAll('[data-add-serie]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const re = btn.dataset.addSerie;
                const res = await API.post('/rutina-ejercicios/' + re + '/series', {
                    repeticiones: Number(document.getElementById('reps-' + re).value || 10),
                    peso_kg: document.getElementById('peso-' + re).value,
                    descanso_segundos: Number(document.getElementById('desc-' + re).value || 0)
                });
                if (res.status !== 'ok') alert(I18n.api(res.message));
                else recargar();
            });
        });
        contenido.querySelectorAll('[data-borrar-serie]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                await API.del('/series/' + btn.dataset.borrarSerie);
                recargar();
            });
        });
        contenido.querySelectorAll('input[data-serie]').forEach((input) => {
            input.addEventListener('change', async () => {
                const idSerie = input.dataset.serie;
                const fila = input.closest('tr').querySelectorAll('input[data-serie="' + idSerie + '"]');
                const payload = {};
                fila.forEach((el) => { payload[el.dataset.campo] = el.value; });
                const res = await API.put('/series/' + idSerie, payload);
                if (res.status !== 'ok') {
                    alert(I18n.api(res.message, 'save_fail'));
                }
            });
        });
        contenido.querySelectorAll('[data-terminar]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const idSerie = btn.dataset.terminar;
                const peso = btn.closest('tr').querySelector('[data-peso-local="' + idSerie + '"]')?.value ?? '';
                btn.disabled = true;
                const res = await API.post('/progreso/terminar', {
                    id_serie: Number(idSerie),
                    peso_kg: peso,
                    id_entrenamiento: idEntrenamiento || null
                });
                btn.disabled = false;
                if (res.status !== 'ok') {
                    alert(I18n.api(res.message, 'set_save_fail'));
                    return;
                }
                idEntrenamiento = Number(res.entrenamiento && res.entrenamiento.id_entrenamiento);
                if (idEntrenamiento) sessionStorage.setItem(storageKey, String(idEntrenamiento));
                marcarBotonTerminado(btn);
            });
        });

        if (!soloLectura) {
            let dragId = null;
            contenido.querySelectorAll('.exercise-block').forEach((el) => {
                el.addEventListener('dragstart', () => { dragId = el.dataset.id; });
                el.addEventListener('dragover', (ev) => ev.preventDefault());
                el.addEventListener('drop', async (ev) => {
                    ev.preventDefault();
                    const from = ejercicios.findIndex((e) => String(e.id_rutina_ejercicio) === dragId);
                    const to = ejercicios.findIndex((e) => String(e.id_rutina_ejercicio) === el.dataset.id);
                    if (from < 0 || to < 0 || from === to) return;
                    const [item] = ejercicios.splice(from, 1);
                    ejercicios.splice(to, 0, item);
                    await guardarOrden();
                });
            });
        }
    }

    pintar();
    FitLoader.hide();
})();
