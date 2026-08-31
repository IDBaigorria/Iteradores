/***
 * Funciones de empresas, vehículos y editor de asientos.
 * @version 1.5piloto.15
 */

// Variable global para el editor de asientos
let editor_pisos_actuales = [];

async function cargar_datos_micros() {
    // Limpiar selects
    $("#selector_dueno_micros").innerHTML = '';
    $("#selector_empresa_micros").innerHTML = '';
    $("#selector_vehiculo_micros").innerHTML = '';
    $("#croquis_pisos_micros").innerHTML = '';

    // Ocultar paneles por defecto
    $("#panel_selector_dueno_micros").style.display = 'none';
    $("#panel_empresas_micros").style.display = 'none';
    $("#panel_vehiculos_micros").style.display = 'none';
    $("#panel_croquis_micros").style.display = 'none';

    if (!usuario_actual) return;

    if (usuario_actual.nivel === 'admin') {
        $("#panel_selector_dueno_micros").style.display = 'block';
        await cargar_duenos_en_select_micros();
    } else if (usuario_actual.nivel === 'dueno') {
        await cargar_empresas_de_dueno(usuario_actual.nombre_usuario);
        $("#panel_empresas_micros").style.display = 'block';
    }
}

async function cargar_duenos_en_select_micros() {
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "administrador/listar_duenos" })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select = $("#selector_dueno_micros");
            select.innerHTML = '<option value="">Seleccione dueño...</option>';
            datos.duenos.forEach(dueno => {
                const opcion = document.createElement('option');
                opcion.value = dueno.nombre_usuario;
                opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;
                select.appendChild(opcion);
            });
            select.onchange = async () => {
                const nombre_dueno = select.value;
                if (nombre_dueno) {
                    await cargar_empresas_de_dueno(nombre_dueno);
                    $("#panel_empresas_micros").style.display = 'block';
                } else {
                    $("#panel_empresas_micros").style.display = 'none';
                }
            };
        }
    } catch (e) { console.error(e); }
}

async function cargar_empresas_de_dueno(nombre_dueno) {
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "empresas/listar", nombre_dueno })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select = $("#selector_empresa_micros");
            select.innerHTML = '<option value="">Seleccione empresa...</option>';
            datos.empresas.forEach(empresa => {
                const opcion = document.createElement('option');
                opcion.value = empresa.nombre_empresa;
                opcion.textContent = empresa.nombre;
                select.appendChild(opcion);
            });
            select.onchange = async () => {
                const nombre_empresa = select.value;
                if (nombre_empresa) {
                    await cargar_vehiculos_de_empresa(nombre_empresa);
                    $("#panel_vehiculos_micros").style.display = 'block';
                } else {
                    $("#panel_vehiculos_micros").style.display = 'none';
                    $("#panel_croquis_micros").style.display = 'none';
                }
            };
        }
    } catch (e) { console.error(e); }
}

async function cargar_vehiculos_de_empresa(nombre_empresa) {
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select = $("#selector_vehiculo_micros");
            select.innerHTML = '<option value="">Seleccione vehículo...</option>';
            datos.vehiculos.forEach(vehiculo => {
                const opcion = document.createElement('option');
                opcion.value = vehiculo.nombre_vehiculo;
                opcion.textContent = vehiculo.nombre;
                select.appendChild(opcion);
            });

            if (datos.vehiculos.length > 0) {
                select.selectedIndex = 1;
                vehiculo_seleccionado_micros = datos.vehiculos[0];
                mostrar_croquis_vehiculo(vehiculo_seleccionado_micros);
                actualizar_foto_vehiculo(vehiculo_seleccionado_micros.foto, false);
                $("#panel_croquis_micros").style.display = 'block';
                $("#area_croquis_estatico_micros").classList.remove("hidden");
                $("#panel_edicion_vehiculo_micros").classList.add("hidden");
            } else {
                vehiculo_seleccionado_micros = null;
                $("#panel_croquis_micros").style.display = 'none';
            }

            select.onchange = async () => {
                const vehiculo = datos.vehiculos.find(v => v.nombre_vehiculo === select.value);
                if (vehiculo) {
                    vehiculo_seleccionado_micros = vehiculo;
                    mostrar_croquis_vehiculo(vehiculo);
                    actualizar_foto_vehiculo(vehiculo.foto, false);
                    $("#panel_croquis_micros").style.display = 'block';
                    $("#area_croquis_estatico_micros").classList.remove("hidden");
                    $("#panel_edicion_vehiculo_micros").classList.add("hidden");
                } else {
                    vehiculo_seleccionado_micros = null;
                    $("#panel_croquis_micros").style.display = 'none';
                }
            };
        }
    } catch (e) {
        console.error(e);
    }
}

function actualizar_foto_vehiculo(ruta, es_editor = false) {
    if (es_editor) {
        const img = $("#foto_vehiculo_editor_img");
        const placeholder = $("#foto_vehiculo_editor_placeholder");
        const boton_subir = $("#boton_subir_foto");
        const boton_cambiar = $("#boton_cambiar_foto");
        if (ruta) {
            img.src = ruta;
            img.style.display = 'block';
            placeholder.style.display = 'none';
            boton_subir.style.display = 'none';
            boton_cambiar.style.display = 'inline-block';
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'block';
            boton_subir.style.display = 'inline-block';
            boton_cambiar.style.display = 'none';
        }
    } else {
        const img = $("#foto_vehiculo_estatica");
        const placeholder = $("#foto_vehiculo_estatica_placeholder");
        if (ruta) {
            img.src = ruta;
            img.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'block';
        }
    }
}

function mostrar_croquis_vehiculo(vehiculo) {
    const contenedor = $("#croquis_pisos_micros");
    contenedor.innerHTML = '';

    const configuracion = vehiculo.configuracion;
    if (!configuracion || !configuracion.pisos || configuracion.pisos.length === 0) {
        const num_asientos = parseInt(vehiculo.asientos) || 44;
        const filas = Math.ceil(num_asientos / 4);
        // Croquis simple omitido por brevedad
    } else {
        configuracion.pisos.forEach((piso, index) => {
            const titulo = document.createElement('div');
            titulo.className = 'section-title';
            titulo.textContent = `Piso ${index + 1}`;
            contenedor.appendChild(titulo);

            const busDiv = document.createElement('div');
            busDiv.className = 'bus';
            busDiv.innerHTML = `<div class="bus-front">FRENTE · CONDUCTOR</div>`;

            for (let fila = 1; fila <= piso.filas; fila++) {
                const filaDiv = document.createElement('div');
                filaDiv.className = 'seat-row';
                for (let col = 1; col <= piso.columnas; col++) {
                    const asiento = piso.asientos.find(a => parseInt(a.fila) === fila && parseInt(a.columna) === col);
                    if (asiento) {
                        const seat = document.createElement('div');
                        seat.className = 'seat';
                        seat.textContent = asiento.numero.padStart(2, '0');
                        filaDiv.appendChild(seat);
                    } else {
                        const empty = document.createElement('div');
                        empty.className = 'aisle';
                        filaDiv.appendChild(empty);
                    }
                }
                busDiv.appendChild(filaDiv);
            }

            busDiv.innerHTML += `<div class="bus-back">PARTE TRASERA</div>`;
            contenedor.appendChild(busDiv);
        });
    }
}

// Eventos de empresa y vehículo
$("#boton_agregar_empresa_micros").addEventListener("click", () => {
    $("#formulario_nueva_empresa").classList.remove("hidden");
});

$("#boton_cancelar_empresa").addEventListener("click", () => {
    $("#formulario_nueva_empresa").classList.add("hidden");
});

$("#boton_guardar_empresa").addEventListener("click", async () => {
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    if (!nombre_dueno) {
        mostrar_aviso("Seleccione un dueño", 'error');
        return;
    }
    const datos = {
        accion: "empresas/agregar",
        nombre_dueno,
        nombre_empresa: $("#nueva_empresa_nombre").value.trim(),
        nombre_real: $("#nueva_empresa_nombre_real").value.trim()
    };
    if (!datos.nombre_empresa) {
        mostrar_aviso("Nombre de empresa obligatorio", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Empresa agregada", 'exito');
        $("#formulario_nueva_empresa").classList.add("hidden");
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al agregar empresa", 'error');
    }
});

// Eventos de vehículo
$("#boton_agregar_vehiculo_micros").addEventListener("click", () => {
    $("#formulario_nuevo_vehiculo").classList.remove("hidden");
});

$("#boton_cancelar_vehiculo").addEventListener("click", () => {
    $("#formulario_nuevo_vehiculo").classList.add("hidden");
});

$("#boton_guardar_vehiculo").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa", 'error');
        return;
    }
    const datos = {
        accion: "vehiculos/agregar",
        nombre_empresa,
        nombre_vehiculo: $("#nuevo_vehiculo_nombre").value.trim(),
        nombre_real: $("#nuevo_vehiculo_nombre_real").value.trim()
    };
    if (!datos.nombre_vehiculo) {
        mostrar_aviso("Patente obligatoria", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Vehículo agregado. Configure los asientos.", 'exito');
        $("#formulario_nuevo_vehiculo").classList.add("hidden");
        await cargar_vehiculos_de_empresa(nombre_empresa);
        const select = $("#selector_vehiculo_micros");
        select.value = datos.nombre_vehiculo;
        select.dispatchEvent(new Event('change'));
        setTimeout(() => {
            if (vehiculo_seleccionado_micros && vehiculo_seleccionado_micros.nombre_vehiculo === datos.nombre_vehiculo) {
                iniciar_editor_vehiculo();
            }
        }, 300);
    } else {
        mostrar_aviso(resultado.error || "Error al agregar vehículo", 'error');
    }
});

// Editor de asientos
function iniciar_editor_vehiculo() {
    if (!vehiculo_seleccionado_micros) {
        mostrar_aviso("Seleccione un vehículo primero", 'error');
        return;
    }

    $("#area_croquis_estatico_micros").classList.add("hidden");
    $("#panel_edicion_vehiculo_micros").classList.remove("hidden");

    const configuracion = vehiculo_seleccionado_micros.configuracion;
    let numPisos = 1;
    if (configuracion && Array.isArray(configuracion.pisos) && configuracion.pisos.length > 0) {
        numPisos = configuracion.pisos.length;
    }
    $("#editor_num_pisos").value = String(numPisos);

    editor_pisos_actuales = [];
    if (configuracion && configuracion.pisos) {
        for (let i = 0; i < configuracion.pisos.length; i++) {
            editor_pisos_actuales.push({
                filas: configuracion.pisos[i].filas,
                columnas: configuracion.pisos[i].columnas,
                asientos: configuracion.pisos[i].asientos.map(a => ({
                    fila: parseInt(a.fila),
                    columna: parseInt(a.columna),
                    numero: a.numero
                }))
            });
        }
    } else {
        editor_pisos_actuales.push({ filas: 12, columnas: 5, asientos: [] });
    }

    generar_editor_pisos();
    actualizar_foto_vehiculo(vehiculo_seleccionado_micros.foto, true);
}

function generar_editor_pisos() {
    const select = $("#editor_num_pisos");
    if (!select) return;
    const numPisos = Math.max(1, parseInt(select.value, 10) || 1);

    while (editor_pisos_actuales.length < numPisos) {
        editor_pisos_actuales.push({ filas: 12, columnas: 5, asientos: [] });
    }
    editor_pisos_actuales.length = numPisos;

    const contenedor = $("#editor_pisos_container");
    contenedor.innerHTML = '';

    editor_pisos_actuales.forEach((piso, idxPiso) => {
        const pisoDiv = document.createElement('div');
        pisoDiv.className = 'editor-piso';
        pisoDiv.innerHTML = `<h4>Piso ${idxPiso + 1}</h4>
            <div class="form-grid" style="margin-bottom:8px;">
                <div class="field"><label>Filas</label><input type="number" class="input-filas" value="${piso.filas}" min="1"></div>
                <div class="field"><label>Columnas</label><input type="number" class="input-columnas" value="${piso.columnas}" min="1"></div>
            </div>
            <div class="cuadricula-editor" data-piso="${idxPiso}"></div>`;
        contenedor.appendChild(pisoDiv);

        const inputFilas = pisoDiv.querySelector('.input-filas');
        const inputColumnas = pisoDiv.querySelector('.input-columnas');
        inputFilas.addEventListener('change', () => {
            piso.filas = parseInt(inputFilas.value, 10) || 1;
            renderizar_cuadricula_editor(pisoDiv, idxPiso);
        });
        inputColumnas.addEventListener('change', () => {
            piso.columnas = parseInt(inputColumnas.value, 10) || 1;
            renderizar_cuadricula_editor(pisoDiv, idxPiso);
        });

        renderizar_cuadricula_editor(pisoDiv, idxPiso);
    });
}

function renderizar_cuadricula_editor(contenedorPiso, idxPiso) {
    const piso = editor_pisos_actuales[idxPiso];
    const cuadricula = contenedorPiso.querySelector('.cuadricula-editor');
    cuadricula.innerHTML = '';

    for (let f = 1; f <= piso.filas; f++) {
        const filaDiv = document.createElement('div');
        filaDiv.className = 'seat-row';
        for (let c = 1; c <= piso.columnas; c++) {
            const celda = document.createElement('div');
            celda.className = 'celda-editor';
            celda.dataset.fila = f;
            celda.dataset.columna = c;
            celda.dataset.piso = idxPiso;

            const asiento = piso.asientos.find(a => a.fila === f && a.columna === c);
            if (asiento) {
                celda.classList.add('asiento');
                celda.textContent = asiento.numero;
            } else {
                celda.classList.add('vacio');
            }

            celda.addEventListener('click', () => alternar_asiento(celda, idxPiso, f, c));
            celda.addEventListener('dblclick', () => editar_numero_asiento(celda, idxPiso, f, c));

            filaDiv.appendChild(celda);
        }
        cuadricula.appendChild(filaDiv);
    }
}

function alternar_asiento(celda, idxPiso, fila, columna) {
    const piso = editor_pisos_actuales[idxPiso];
    const idx = piso.asientos.findIndex(a => a.fila === fila && a.columna === columna);
    if (idx >= 0) {
        piso.asientos.splice(idx, 1);
        celda.classList.remove('asiento');
        celda.classList.add('vacio');
        celda.textContent = '';
    } else {
        const nuevoNumero = generar_numero_provisional(piso);
        piso.asientos.push({ fila, columna, numero: nuevoNumero });
        celda.classList.add('asiento');
        celda.classList.remove('vacio');
        celda.textContent = nuevoNumero;
    }
}

function editar_numero_asiento(celda, idxPiso, fila, columna) {
    const piso = editor_pisos_actuales[idxPiso];
    const asiento = piso.asientos.find(a => a.fila === fila && a.columna === columna);
    if (!asiento) return;

    const nuevoNumero = prompt("Número de asiento:", asiento.numero);
    if (nuevoNumero !== null && nuevoNumero.trim() !== '') {
        asiento.numero = nuevoNumero.trim();
        celda.textContent = nuevoNumero.trim();
    }
}

function generar_numero_provisional(piso) {
    let max = 0;
    piso.asientos.forEach(a => {
        const num = parseInt(a.numero);
        if (!isNaN(num) && num > max) max = num;
    });
    return String(max + 1);
}

async function guardar_configuracion() {
    const numPisos = parseInt($("#editor_num_pisos").value);
    const configuracion = { pisos: [] };
    for (let i = 0; i < numPisos; i++) {
        const piso = editor_pisos_actuales[i];
        configuracion.pisos.push({
            filas: piso.filas,
            columnas: piso.columnas,
            asientos: piso.asientos.map(a => ({
                fila: String(a.fila),
                columna: String(a.columna),
                numero: String(a.numero)
            }))
        });
    }

    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_vehiculo = vehiculo_seleccionado_micros.nombre_vehiculo;

    const datos = {
        accion: "vehiculos/actualizar_configuracion",
        nombre_empresa,
        nombre_vehiculo,
        configuracion: JSON.stringify(configuracion)
    };

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        if (vehiculo_seleccionado_micros) {
            vehiculo_seleccionado_micros.configuracion = configuracion;
            const total_asientos = configuracion.pisos.reduce((total, piso) => total + piso.asientos.length, 0);
            vehiculo_seleccionado_micros.asientos = String(total_asientos);
        }
        cancelar_editor();
        mostrar_aviso("Configuración actualizada", 'exito');
    } else {
        mostrar_aviso(resultado.error || "Error al guardar configuración", 'error');
    }
}

function cancelar_editor() {
    $("#panel_edicion_vehiculo_micros").classList.add("hidden");
    $("#area_croquis_estatico_micros").classList.remove("hidden");
    if (vehiculo_seleccionado_micros) {
        mostrar_croquis_vehiculo(vehiculo_seleccionado_micros);
    }
}

// Eventos de edición
$("#boton_modificar_vehiculo_micros").addEventListener("click", iniciar_editor_vehiculo);
$("#editor_num_pisos").addEventListener("change", generar_editor_pisos);
$("#boton_guardar_configuracion").addEventListener("click", guardar_configuracion);
$("#boton_cancelar_configuracion").addEventListener("click", cancelar_editor);

$("#boton_reiniciar_asientos").addEventListener("click", () => {
    editor_pisos_actuales.forEach(piso => {
        piso.asientos = [];
    });
    document.querySelectorAll('.cuadricula-editor').forEach((cuadricula, idx) => {
        renderizar_cuadricula_editor(cuadricula.parentElement, idx);
    });
    mostrar_aviso("Asientos reiniciados", 'info');
});

// Subir foto
$("#boton_subir_foto").addEventListener("click", () => {
    $("#input_foto_vehiculo").click();
});

$("#boton_cambiar_foto").addEventListener("click", () => {
    $("#input_foto_vehiculo").click();
});

$("#input_foto_vehiculo").addEventListener("change", async (e) => {
    const archivo = e.target.files[0];
    if (!archivo) return;
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_vehiculo = vehiculo_seleccionado_micros?.nombre_vehiculo;
    if (!nombre_vehiculo) {
        mostrar_aviso("Seleccione un vehículo primero", 'error');
        return;
    }
    const formData = new FormData();
    formData.append('accion', 'vehiculos/subir_foto');
    formData.append('nombre_empresa', nombre_empresa);
    formData.append('nombre_vehiculo', nombre_vehiculo);
    formData.append('foto', archivo);

    const respuesta = await fetch("index.php", {
        method: "POST",
        body: formData
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        if (vehiculo_seleccionado_micros) {
            vehiculo_seleccionado_micros.foto = resultado.foto;
        }
        actualizar_foto_vehiculo(resultado.foto, false);
        actualizar_foto_vehiculo(resultado.foto, true);
        mostrar_aviso("Foto actualizada", 'exito');
    } else {
        mostrar_aviso(resultado.error || "Error al subir foto", 'error');
    }
});