/**
 * admin-habitaciones.js
 * Panel de administración de habitaciones
 */

let categorias = [];
let habitacionEditando = null;
let accionConfirmacion = null;

document.addEventListener('DOMContentLoaded', async () => {
    if (!requerirAutenticacion('admin')) return;

    await cargarCategorias();
    await cargarHabitaciones();
    configurarEventos();
});

function configurarEventos() {
    document.getElementById('btnNuevaHabitacion').onclick = () => abrirModalNuevaHabitacion();
    document.getElementById('btnCancelar').onclick = cerrarModalHabitacion;
    document.getElementById('closeModalHabitacion').onclick = cerrarModalHabitacion;
    document.getElementById('formHabitacion').onsubmit = handleGuardarHabitacion;
    document.getElementById('btnConfirmarAccion').onclick = () => ejecutarAccionConfirmada();
    document.getElementById('btnCancelarAccion').onclick = cerrarModalConfirmar;
}

async function cargarCategorias() {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=categorias`);
        const data = await response.json();
        if (data.success) {
            categorias = data.data.categorias;
            llenarSelectCategorias();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function llenarSelectCategorias() {
    const select = document.getElementById('id_categoria');
    categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id_categoria;
        option.textContent = cat.nombre_categoria;
        select.appendChild(option);
    });
}

async function cargarHabitaciones() {
    try {
        const data = await fetchAutenticado(`${API_BASE}habitaciones.php?accion=listar`);
        if (data && data.success) {
            mostrarHabitacionesTabla(data.data.habitaciones);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cargar habitaciones', 'error');
    }
}

function mostrarHabitacionesTabla(habitaciones) {
    const tbody = document.getElementById('tablaHabitaciones');
    tbody.innerHTML = '';

    habitaciones.forEach(hab => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${sanitizarHTML(hab.numero_habitacion)}</td>
            <td>${sanitizarHTML(hab.nombre)}</td>
            <td>${sanitizarHTML(hab.nombre_categoria)}</td>
            <td>${formatearPrecio(hab.precio_noche)}</td>
            <td>${hab.capacidad_personas}</td>
            <td>${hab.cantidad_disponible}</td>
            <td>
                <span class="status-badge status-${hab.activo ? 'activo' : 'inactivo'}">${hab.activo ? 'Activo' : 'Inactivo'}</span>
                <button class="btn btn-small ${hab.activo ? 'btn-secondary' : 'btn-success'}"
                        onclick="toggleEstadoHabitacion(${hab.id_habitacion}, ${hab.activo})"
                        style="margin-left:0.5rem;"
                        title="${hab.activo ? 'Desactivar habitación' : 'Activar habitación'}">
                    ${hab.activo ? '🔒' : '✅'}
                </button>
            </td>
            <td>
                <button class="btn btn-secondary btn-small" onclick="editarHabitacion(${hab.id_habitacion})" style="margin-right:0.5rem;">Editar</button>
                <button class="btn btn-danger btn-small" onclick="confirmarEliminarHabitacion(${hab.id_habitacion})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function abrirModalNuevaHabitacion() {
    habitacionEditando = null;
    document.getElementById('modalTitle').textContent = 'Agregar Habitación';
    document.getElementById('formHabitacion').reset();
    document.getElementById('accion').value = 'crear';
    document.getElementById('seccionImagenes').style.display = 'none';
    document.getElementById('seccionEstado').style.display = 'none';
    document.getElementById('modalHabitacion').classList.add('active');
}

async function editarHabitacion(id) {
    try {
        const data = await fetchAutenticado(`${API_BASE}habitaciones.php?accion=obtener&id=${id}`);
        if (data && data.success) {
            habitacionEditando = data.data.habitacion;
            llenarFormularioEdicion(habitacionEditando);
            document.getElementById('modalHabitacion').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar habitación');
    }
}

function llenarFormularioEdicion(hab) {
    document.getElementById('modalTitle').textContent = 'Editar Habitación';
    document.getElementById('id_habitacion').value = hab.id_habitacion;
    document.getElementById('accion').value = 'actualizar';
    document.getElementById('numero_habitacion').value = hab.numero_habitacion;
    document.getElementById('numero_habitacion').disabled = true;
    document.getElementById('id_categoria').value = hab.id_categoria;
    document.getElementById('nombre').value = hab.nombre;
    document.getElementById('descripcion').value = hab.descripcion || '';
    document.getElementById('precio_noche').value = hab.precio_noche;
    document.getElementById('capacidad_personas').value = hab.capacidad_personas;
    document.getElementById('cantidad_disponible').value = hab.cantidad_disponible;
    document.getElementById('caracteristicas').value = hab.caracteristicas || '';
    document.getElementById('activo').value = hab.activo ? '1' : '0';

    mostrarImagenesActuales(hab.imagenes || []);
    document.getElementById('seccionImagenes').style.display = 'block';
    document.getElementById('seccionEstado').style.display = 'block';
}

function mostrarImagenesActuales(imagenes) {
    const container = document.getElementById('imagenesActuales');
    container.innerHTML = '';

    imagenes.forEach(img => {
        const div = document.createElement('div');
        div.className = 'image-item';
        div.innerHTML = `
            <img src="${img.ruta_archivo}" alt="Imagen">
            ${img.es_principal ? '<span class="image-badge">Principal</span>' : ''}
            <button type="button" class="image-delete" onclick="eliminarImagen(${img.id_imagen})">✕</button>
        `;
        container.appendChild(div);
    });
}

async function handleGuardarHabitacion(e) {
    e.preventDefault();

    const accion = document.getElementById('accion').value;
    const formData = new FormData(e.target);

    // Construir objeto de datos
    const datos = {
        numero_habitacion: document.getElementById("numero_habitacion").value,
        id_categoria: parseInt(formData.get('id_categoria')),
        nombre: formData.get('nombre'),
        descripcion: formData.get('descripcion'),
        precio_noche: parseFloat(formData.get('precio_noche')),
        capacidad_personas: parseInt(formData.get('capacidad_personas')),
        cantidad_disponible: parseInt(formData.get('cantidad_disponible')),
        caracteristicas: formData.get('caracteristicas')
    };

    // Si estamos editando, agregar el ID y el estado
    const idHabitacion = formData.get('id_habitacion');
    if (accion === 'actualizar' && idHabitacion) {
        datos.id_habitacion = parseInt(idHabitacion);
        datos.activo = parseInt(formData.get('activo'));
    } else {
        // Las habitaciones nuevas siempre se crean activas
        datos.activo = 1;
    }

    // Validar
    if (!validarFormularioHabitacion(datos)) return;

    try {
        const url = `${API_BASE}habitaciones.php?accion=${accion}`;
        const response = await fetchAutenticado(url, {
            method: 'POST',
            body: JSON.stringify(datos)
        });

        if (response && response.success) {
            const idHabitacion = datos.id_habitacion || response.data.id_habitacion;

            // Subir imágenes si hay
            const archivosInput = document.getElementById('imagenes');
            if (archivosInput.files.length > 0) {
                await subirImagenes(idHabitacion, archivosInput.files);
            }

            mostrarAlerta(response.data.mensaje, 'success');
            cerrarModalHabitacion();
            await cargarHabitaciones();
        } else {
            mostrarAlerta(response ? response.error : 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al guardar habitación', 'error');
    }
}

function validarFormularioHabitacion(datos) {
    limpiarErroresFormulario('formHabitacion');
    let valido = true;

    if (!datos.numero_habitacion) {
        mostrarError('NumeroHabitacion', 'El número de habitación es requerido');
        valido = false;
    }
    if (!datos.id_categoria) {
        mostrarError('Categoria', 'Debes seleccionar una categoría');
        valido = false;
    }
    if (!datos.nombre) {
        mostrarError('nombre', 'El nombre es requerido');
        valido = false;
    }
    if (datos.precio_noche <= 0) {
        mostrarError('precio_noche', 'El precio debe ser mayor a 0');
        valido = false;
    }
    if (datos.capacidad_personas < 1) {
        mostrarError('capacidad_personas', 'La capacidad debe ser al menos 1');
        valido = false;
    }
    if (datos.cantidad_disponible < 0) {
        mostrarError('cantidad_disponible', 'La cantidad no puede ser negativa');
        valido = false;
    }

    return valido;
}

async function subirImagenes(idHabitacion, archivos) {
    const formData = new FormData();
    formData.append('id_habitacion', idHabitacion);

    for (let i = 0; i < archivos.length; i++) {
        formData.append('imagenes[]', archivos[i]);
    }

    try {
        const response = await fetch(`${API_BASE}imagenes.php?accion=subir`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const data = await response.json();
        if (!data.success) {
            console.error('Error al subir imágenes:', data.error);
        }
    } catch (error) {
        console.error('Error al subir imágenes:', error);
    }
}

async function eliminarImagen(idImagen) {
    if (!confirm('¿Eliminar esta imagen?')) return;

    try {
        const data = await fetchAutenticado(`${API_BASE}imagenes.php?accion=eliminar&id=${idImagen}`, {
            method: 'POST'
        });

        if (data && data.success) {
            if (habitacionEditando) {
                await editarHabitacion(habitacionEditando.id_habitacion);
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function confirmarEliminarHabitacion(id) {
    accionConfirmacion = () => eliminarHabitacion(id);
    document.getElementById('mensajeConfirmacion').textContent = '¿Estás seguro de que deseas eliminar esta habitación?';
    document.getElementById('modalConfirmar').classList.add('active');
}

async function eliminarHabitacion(id) {
    try {
        const data = await fetchAutenticado(`${API_BASE}habitaciones.php?accion=eliminar&id=${id}`, {
            method: 'POST'
        });

        if (data && data.success) {
            mostrarAlerta('Habitación eliminada', 'success');
            await cargarHabitaciones();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al eliminar', 'error');
    }

    cerrarModalConfirmar();
}

function ejecutarAccionConfirmada() {
    if (accionConfirmacion) {
        accionConfirmacion();
        accionConfirmacion = null;
    }
}

function cerrarModalHabitacion() {
    document.getElementById('modalHabitacion').classList.remove('active');
    document.getElementById('numero_habitacion').disabled = false;
}

function cerrarModalConfirmar() {
    document.getElementById('modalConfirmar').classList.remove('active');
}

/**
 * Cambia el estado de una habitación (activo/inactivo) rápidamente
 */
async function toggleEstadoHabitacion(idHabitacion, estadoActual) {
    const nuevoEstado = estadoActual ? 0 : 1;
    const mensaje = nuevoEstado ? 'activar' : 'desactivar';

    if (!confirm(`¿Estás seguro de que deseas ${mensaje} esta habitación?`)) {
        return;
    }

    try {
        // Primero obtener los datos actuales de la habitación
        const dataActual = await fetchAutenticado(`${API_BASE}habitaciones.php?accion=obtener&id=${idHabitacion}`);

        if (!dataActual || !dataActual.success) {
            mostrarAlerta('Error al obtener datos de la habitación', 'error');
            return;
        }

        const habitacion = dataActual.data.habitacion;

        // Actualizar solo el estado
        const datosActualizar = {
            id_habitacion: idHabitacion,
            numero_habitacion: habitacion.numero_habitacion,
            id_categoria: habitacion.id_categoria,
            nombre: habitacion.nombre,
            descripcion: habitacion.descripcion,
            precio_noche: parseFloat(habitacion.precio_noche),
            capacidad_personas: habitacion.capacidad_personas,
            cantidad_disponible: habitacion.cantidad_disponible,
            caracteristicas: habitacion.caracteristicas,
            activo: nuevoEstado
        };

        const response = await fetchAutenticado(`${API_BASE}habitaciones.php?accion=actualizar`, {
            method: 'POST',
            body: JSON.stringify(datosActualizar)
        });

        if (response && response.success) {
            mostrarAlerta(`Habitación ${nuevoEstado ? 'activada' : 'desactivada'} exitosamente`, 'success');
            await cargarHabitaciones();
        } else {
            mostrarAlerta(response ? response.error : 'Error al cambiar estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cambiar el estado de la habitación', 'error');
    }
}

function mostrarAlerta(mensaje, tipo) {
    const alert = document.getElementById('alertMessage');
    if (!alert) return;
    alert.className = `alert alert-${tipo}`;
    alert.textContent = mensaje;
    alert.classList.remove('hidden');
    setTimeout(() => alert.classList.add('hidden'), 5000);
}
