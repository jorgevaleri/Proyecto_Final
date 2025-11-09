// REGISTROS

// VARIABLES GLOBALES
const __REG = window._REGISTROS || {};
const PRE_FORM = Number(__REG.preForm || 0);
const PRE_ESC = Number(__REG.preEsc || 0);
const PRE_ANIO = Number(__REG.preAnio || 0);
const DOC_FULL = String(__REG.docenteFullname || '');

document.addEventListener('DOMContentLoaded', function () {

    // SELECTS DEPENDIENTES
    const esc = document.getElementById('escuela');
    const frm = document.getElementById('formacion');
    const doc = document.getElementById('docente');
    const anioSelect = document.getElementById('anio');

    // FETCH PARA DETECTAR SI LA RESPUESTA ES HTML EN LUGAR DE JSON
    function safeFetchJson(url) {
        return fetch(url, { credentials: 'same-origin' })
            .then(resp => resp.text())
            .then(text => {
                if (!text) return [];
                const t = text.trim();
                if (t[0] === '<') {
                    console.error('Respuesta no JSON (HTML):', t.slice(0, 300));
                    throw new SyntaxError('Respuesta no JSON (HTML). Ver consola para ver el contenido.');
                }
                try {
                    return JSON.parse(t);
                } catch (err) {
                    console.error('Error parseando JSON:', err, 'texto:', t.slice(0, 300));
                    throw err;
                }
            });
    }

    function setOptions(selectEl, html) {
        if (!selectEl) return;
        selectEl.innerHTML = html;
    }

    // CARGAR FORMACIONES PROFESIONALES
    function loadFormaciones(escId) {
        if (!frm) return Promise.resolve();
        if (!escId) {
            setOptions(frm, '<option value="">--</option>');
            return Promise.resolve();
        }
        setOptions(frm, '<option>Cargando…</option>');
        const url = `registros.php?endpoint=formaciones&escuela=${encodeURIComponent(escId)}`;
        return safeFetchJson(url)
            .then(js => {
                let h = '<option value="">--</option>';
                if (Array.isArray(js) && js.length) {
                    js.forEach(o => {
                        const selected = (PRE_FORM && PRE_ESC && Number(PRE_ESC) === Number(escId) && Number(o.id) === Number(PRE_FORM)) ? ' selected' : '';
                        h += `<option value="${o.id}"${selected}>${o.nombre}</option>`;
                    });
                }
                setOptions(frm, h);

                // CARGAR DOCENTE Y AÑOS DESPUES DE CARGAR FORMACION PROFESIONAL
                const selVal = frm.value;
                if (selVal) {
                    loadDocente(escId, selVal);
                    loadAnios(escId, selVal, PRE_ANIO);
                } else {
                    if (doc) doc.value = DOC_FULL || '';
                    if (anioSelect) anioSelect.innerHTML = '<option value="">--</option>';
                }
            })
            .catch(err => {
                console.error('Error cargando formaciones:', err);
                setOptions(frm, '<option value="">--</option>');
                if (anioSelect) anioSelect.innerHTML = '<option value="">--</option>';
                if (doc) doc.value = DOC_FULL || '';
            });
    }

    // CARGAR DOCENTE DE ACUERDO A LA ESCUELA Y A LA FORMACION PROFESIONAL
    function loadDocente(escId, formId) {
        if (!doc) return;
        if (!escId || !formId) {
            doc.value = DOC_FULL || '';
            return;
        }
        doc.value = 'Cargando…';
        const url = `registros.php?endpoint=docente&escuela=${encodeURIComponent(escId)}&formacion=${encodeURIComponent(formId)}`;
        safeFetchJson(url)
            .then(j => {
                doc.value = j.personas_nombre ? `${j.personas_nombre} ${j.personas_apellido || ''}` : (DOC_FULL || '');
            })
            .catch(err => {
                console.error('Error cargando docente:', err);
                doc.value = DOC_FULL || '';
            });
    }

    // CARGAR AÑOS
    function loadAnios(escId, formId, selectedAnio) {
        if (!anioSelect) return;
        anioSelect.innerHTML = '<option>Cargando…</option>';
        if (!escId || !formId) {
            let h = '<option value="">--</option>';
            for (let yy = 2020; yy <= 2030; yy++) {
                const sel = (selectedAnio && Number(selectedAnio) === yy) ? ' selected' : '';
                h += `<option value="${yy}"${sel}>${yy}</option>`;
            }
            anioSelect.innerHTML = h;
            return;
        }

        const url = `registros.php?endpoint=anios&escuela=${encodeURIComponent(escId)}&formacion=${encodeURIComponent(formId)}`;
        return safeFetchJson(url)
            .then(js => {
                // js puede ser [] o [2023,2024,...]
                // Vamos a combinar lo recibido con el rango 2020..2030,
                // eliminar duplicados y ordenar ascendentemente.
                const rango = [];
                for (let yy = 2020; yy <= 2030; yy++) rango.push(yy);
                let recibidos = Array.isArray(js) ? js.map(n => Number(n)).filter(n => Number.isFinite(n)) : [];
                // union
                const union = Array.from(new Set([...rango, ...recibidos])).sort((a, b) => a - b);
                let h = '<option value="">--</option>';
                union.forEach(y => {
                    const sel = (selectedAnio && Number(selectedAnio) === Number(y)) ? ' selected' : '';
                    h += `<option value="${y}"${sel}>${y}</option>`;
                });
                anioSelect.innerHTML = h;
            })
            .catch(err => {
                console.error('Error cargando años:', err);
                // fallback simple: rango 2020-2030
                let h = '<option value="">--</option>';
                for (let yy = 2020; yy <= 2030; yy++) h += `<option value="${yy}">${yy}</option>`;
                anioSelect.innerHTML = h;
            });
    }

    // EVENTO PARA SELECTS
    if (esc) {
        esc.addEventListener('change', () => {
            const v = esc.value;
            if (v) loadFormaciones(v);
            else {
                setOptions(frm, '<option value="">--</option>');
                if (doc) doc.value = DOC_FULL || '';
                if (anioSelect) anioSelect.innerHTML = '<option value="">--</option>';
            }
        });
    }

    if (frm) {
        frm.addEventListener('change', () => {
            if (esc && esc.value && frm.value) {
                loadDocente(esc.value, frm.value);
                loadAnios(esc.value, frm.value);
            } else {
                if (doc) doc.value = DOC_FULL || '';
                if (anioSelect) anioSelect.innerHTML = '<option value="">--</option>';
            }
        });
    }

    // INICIALIZAR VALORES
    if (PRE_ESC) {
        if (esc) {
            const opt = Array.from(esc.options).find(o => Number(o.value || 0) === Number(PRE_ESC));
            if (opt) opt.selected = true;
        }
        loadFormaciones(PRE_ESC).catch(() => { });
    } else if (esc && esc.value) {
        loadFormaciones(esc.value).catch(() => { });
    }

    // CONTADOR DE ALUMNOS SELECCIONADOS
    const tblAdd = document.getElementById('tbl-add');
    const countSpan = document.getElementById('count');
    if (tblAdd && countSpan) {
        const updateCount = () => {
            const n = tblAdd.querySelectorAll('input[type=checkbox]:checked').length;
            countSpan.textContent = n;
        };
        tblAdd.querySelectorAll('input[type=checkbox]').forEach(cb => {
            cb.addEventListener('change', e => {
                e.target.closest('tr').classList.toggle('selected', e.target.checked);
                updateCount();
            });
        });
        updateCount();
    }

    // CALCULADORA
    const calculadoraNode = document.querySelector('.calculadora');

    function recalcular() {
        if (!calculadoraNode) return;

        // DIAS HABILES DESDE LA PRIMERA FILA
        try {
            if (window.VALIDACIONES && typeof window.VALIDACIONES.sincronizarDiasDesdePrimeraFila === 'function') {
                window.VALIDACIONES.sincronizarDiasDesdePrimeraFila('id_tabla1', 'id_dias_habiles');
            }
        } catch (e) { /* noop */ }

        const rows = document.querySelectorAll('.calculadora .tabla1 tbody tr');
        if (!rows || rows.length === 0) {
            const elems = ['sum-asi-var', 'sum-asi-muj', 'sum-asi-tot', 'sum-ina-var', 'sum-ina-muj', 'sum-ina-tot', 'sum-asi-med-var', 'sum-asi-med-muj', 'sum-asi-med-tot', 'sum-por-var', 'sum-por-muj', 'sum-por-tot', 'id_dias_habiles'];
            elems.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    if (el.tagName === 'INPUT') el.value = '';
                    else el.textContent = '';
                }
            });
            return;
        }

        // SUMAR POR SEXO SEGUN TABLA
        let asiVar = 0, asiMuj = 0, inaVar = 0, inaMuj = 0;

        rows.forEach(r => {
            const sexoCell = r.cells[2];
            const asiCell = r.cells[3];
            const inaCell = r.cells[4];
            if (!sexoCell || !asiCell || !inaCell) return;
            const sexo = sexoCell.textContent.trim();
            const asiInput = asiCell.querySelector('input');
            const inaInput = inaCell.querySelector('input');
            const asi = asiInput ? (+asiInput.value || 0) : 0;
            const ina = inaInput ? (+inaInput.value || 0) : 0;
            if (sexo === 'Masculino') {
                asiVar += asi; inaVar += ina;
            } else {
                asiMuj += asi; inaMuj += ina;
            }
        });

        const asiTot = asiVar + asiMuj;
        const inaTot = inaVar + inaMuj;

        // LEER DIAS HABILAES
        const diasEl = document.getElementById('id_dias_habiles') || document.getElementById('id_dias_habiles') || document.getElementById('id_dias_habiles');
        const diasRaw = diasEl ? (diasEl.value || '') : '';
        const diasForCalc = (diasRaw !== '' && Number(diasRaw) > 0) ? Number(diasRaw) : 1;

        const medVar = Math.round(asiVar / diasForCalc);
        const medMuj = Math.round(asiMuj / diasForCalc);
        const medTot = Math.round(asiTot / diasForCalc);

        const pct = (a, b) => b ? Math.round(a * 100 / b) + '%' : '0%';
        const pctVar = pct(asiVar, asiVar + inaVar);
        const pctMuj = pct(asiMuj, asiMuj + inaMuj);
        const pctTot = pct(asiTot, asiTot + inaTot);

        const safeSetText = (id, value) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'INPUT') el.value = value;
            else el.textContent = value;
        };

        // ESCRIBIR RESULTADOS
        safeSetText('sum-asi-var', asiVar);
        safeSetText('sum-asi-muj', asiMuj);
        safeSetText('sum-asi-tot', asiTot);
        safeSetText('sum-ina-var', inaVar);
        safeSetText('sum-ina-muj', inaMuj);
        safeSetText('sum-ina-tot', inaTot);
        safeSetText('sum-asi-med-var', medVar);
        safeSetText('sum-asi-med-muj', medMuj);
        safeSetText('sum-asi-med-tot', medTot);
        safeSetText('sum-por-var', pctVar);
        safeSetText('sum-por-muj', pctMuj);
        safeSetText('sum-por-tot', pctTot);

        if (!diasEl) {
            const fallback = document.getElementById('id_dias_habiles');
            if (fallback) fallback.value = diasRaw || '';
        }

        // SINCRONIZAR INPUTS
        const setHidden = (id, val) => {
            const h = document.getElementById(id);
            if (h) h.value = val;
        };
        setHidden('h_sum-asi-var', asiVar);
        setHidden('h_sum-asi-muj', asiMuj);
        setHidden('h_sum-asi-tot', asiTot);
        setHidden('h_sum-ina-var', inaVar);
        setHidden('h_sum-ina-muj', inaMuj);
        setHidden('h_sum-ina-tot', inaTot);
        setHidden('h_sum-asi-med-var', medVar);
        setHidden('h_sum-asi-med-muj', medMuj);
        setHidden('h_sum-asi-med-tot', medTot);
        setHidden('h_sum-por-var', pctVar);
        setHidden('h_sum-por-muj', pctMuj);
        setHidden('h_sum-por-tot', pctTot);
    }

    // VACIAR TABLAS
    function limpiarCalculadora() {
        if (!calculadoraNode) return;
        document.querySelectorAll('.calculadora .tabla1 tbody tr').forEach(r => {
            const asi = r.cells[3] ? r.cells[3].querySelector('input') : null;
            const ina = r.cells[4] ? r.cells[4].querySelector('input') : null;
            if (asi) asi.value = '';
            if (ina) ina.value = '';
        });
        const dh = document.getElementById('id_dias_habiles');
        if (dh) dh.value = '';
        recalcular();
    }

    // RECALCULAR
    document.addEventListener('input', e => {
        if (!calculadoraNode) return;
        if (e.target.closest('.calculadora .tabla1') || e.target.name === 'mes') recalcular();
    });

    // SE EJECUTA AL CARGAR LA PESTAÑA CALCULADORA
    if (calculadoraNode) recalcular();

    // EXPORTAR FUNCIONES
    window.limpiarCalculadora = limpiarCalculadora;
    window.recalcular = recalcular;

});