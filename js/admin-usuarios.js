let usuarioEditando = null;
let accionConfirmacion = null;
let filtroActual = 'todos';
let usuariosData = [];

document.addEventListener('DOMContentLoaded', async () => {
    if (!requerirAutenticacion('admin')) return;

    await cargarUsuarios();
    configurarEventos();
});

function configurarEventos() {
    document.getElementById('btnNuevoUsuario').onclick = () => abrirModalNuevoUsuario();
    document.getElementById('btnCancelar').onclick = cerrarModalUsuario;
    document.getElementById('closeModalUsuario').onclick = cerrarModalUsuario;
    document.getElementById('formUsuario').onsubmit = handleGuardarUsuario;
    document.getElementById('btnConfirmarAccion').onclick = () => ejecutarAccionConfirmada();
    document.getElementById('btnCancelarAccion').onclick = cerrarModalConfirmar;

    const filterBtns = document.querySelectorAll('.status-filters .filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filtroActual = btn.dataset.tipo;
            aplicarFiltros();
        });
    });
}

async function cargarUsuarios() {
    try {
        const data = await fetchAutenticado(`${API_BASE}usuarios.php?accion=listar`);
        if (data && data.success) {
            usuariosData = data.data.usuarios;
            aplicarFiltros();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cargar usuarios', 'error');
    }
}

function aplicarFiltros() {
    let usuariosFiltrados = usuariosData;

    switch (filtroActual) {
        case 'admin':
            usuariosFiltrados = usuariosData.filter(u => u.tipo_usuario === 'admin');
            break;
        case 'huesped':
            usuariosFiltrados = usuariosData.filter(u => u.tipo_usuario === 'huesped');
            break;
        case 'activo':
            usuariosFiltrados = usuariosData.filter(u => u.activo == 1);
            break;
        case 'inactivo':
            usuariosFiltrados = usuariosData.filter(u => u.activo == 0);
            break;
    }

    mostrarUsuariosTabla(usuariosFiltrados);
}

function mostrarUsuariosTabla(usuarios) {
    const tbody = document.getElementById('tablaUsuarios');
    tbody.innerHTML = '';

    if (usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No hay usuarios que coincidan con el filtro</td></tr>';
        return;
    }

    usuarios.forEach(usuario => {
        const tr = document.createElement('tr');

        const tipoClass = usuario.tipo_usuario === 'admin' ? 'status-confirmada' : 'status-pendiente';
        const esUsuarioActual = usuario.id_usuario == obtenerUsuarioActual().id;

        tr.innerHTML = `
            <td>${usuario.id_usuario}</td>
            <td>${sanitizarHTML(usuario.nombre_usuario)}</td>
            <td>${sanitizarHTML(usuario.nombre_completo)}</td>
            <td>${sanitizarHTML(usuario.email)}</td>
            <td>${sanitizarHTML(usuario.telefono || 'N/A')}</td>
            <td><span class="status-badge ${tipoClass}">${getTipoUsuarioLabel(usuario.tipo_usuario)}</span></td>
            <td>
                <span class="status-badge status-${usuario.activo ? 'activo' : 'inactivo'}">${usuario.activo ? 'Activo' : 'Inactivo'}</span>
                ${!esUsuarioActual ? `
                    <button class="btn btn-small ${usuario.activo ? 'btn-secondary' : 'btn-success'}"
                            onclick="toggleEstadoUsuario(${usuario.id_usuario}, ${usuario.activo})"
                            style="margin-left:0.5rem;"
                            title="${usuario.activo ? 'Desactivar usuario' : 'Activar usuario'}">
                        ${usuario.activo ? '🔒' : '✅'}
                    </button>
                ` : ''}
            </td>
            <td>${formatearFecha(usuario.fecha_registro)}</td>
            <td>
                <button class="btn btn-secondary btn-small" onclick="editarUsuario(${usuario.id_usuario})" style="margin-right:0.5rem;">Editar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function getTipoUsuarioLabel(tipo) {
    const labels = {
        'admin': 'Administrador',
        'huesped': 'Huésped',
        'no_registrado': 'No Registrado'
    };
    return labels[tipo] || tipo;
}

function abrirModalNuevoUsuario() {
    usuarioEditando = null;
    document.getElementById('modalTitle').textContent = 'Agregar Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('accion').value = 'crear';
    document.getElementById('seccionEstado').style.display = 'none';
    document.getElementById('nombre_usuario').disabled = false;

    document.getElementById('contrasena').required = true;
    document.getElementById('confirmar_contrasena').required = true;
    document.getElementById('labelContrasena').innerHTML = 'Contraseña *';
    document.getElementById('labelConfirmarContrasena').innerHTML = 'Confirmar Contraseña *';
    document.getElementById('helpContrasena').textContent = 'Mínimo 6 caracteres';

    document.getElementById('modalUsuario').classList.add('active');
}

async function editarUsuario(id) {
    try {
        const data = await fetchAutenticado(`${API_BASE}usuarios.php?accion=obtener&id=${id}`);
        if (data && data.success) {
            usuarioEditando = data.data.usuario;
            llenarFormularioEdicion(usuarioEditando);
            document.getElementById('modalUsuario').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar usuario');
    }
}

function llenarFormularioEdicion(usuario) {
    document.getElementById('modalTitle').textContent = 'Editar Usuario';
    document.getElementById('id_usuario').value = usuario.id_usuario;
    document.getElementById('accion').value = 'actualizar';
    document.getElementById('nombre_usuario').value = usuario.nombre_usuario;
    document.getElementById('nombre_usuario').disabled = true;
    document.getElementById('nombre_completo').value = usuario.nombre_completo;
    document.getElementById('email').value = usuario.email;
    document.getElementById('telefono').value = usuario.telefono || '';
    document.getElementById('tipo_usuario').value = usuario.tipo_usuario;
    document.getElementById('activo').value = usuario.activo ? '1' : '0';

    document.getElementById('contrasena').value = '';
    document.getElementById('confirmar_contrasena').value = '';
    document.getElementById('contrasena').required = false;
    document.getElementById('confirmar_contrasena').required = false;
    document.getElementById('labelContrasena').innerHTML = 'Nueva Contraseña (opcional)';
    document.getElementById('labelConfirmarContrasena').innerHTML = 'Confirmar Nueva Contraseña';
    document.getElementById('helpContrasena').textContent = 'Dejar en blanco para no cambiar';

    document.getElementById('seccionEstado').style.display = 'block';
}

async function handleGuardarUsuario(e) {
    e.preventDefault();
    limpiarErroresFormulario('formUsuario');

    const accion = document.getElementById('accion').value;
    const formData = new FormData(e.target);

    const datos = {
        nombre_usuario: document.getElementById('nombre_usuario').value,
        nombre_completo: formData.get('nombre_completo'),
        email: formData.get('email'),
        telefono: formData.get('telefono'),
        tipo_usuario: formData.get('tipo_usuario')
    };

    const contrasena = formData.get('contrasena');
    const confirmarContrasena = formData.get('confirmar_contrasena');

    // Validar contraseñas
    if (accion === 'crear') {
        if (!contrasena || contrasena.length < 6) {
            mostrarError('Contrasena', 'La contraseña debe tener al menos 6 caracteres');
            return;
        }
        if (contrasena !== confirmarContrasena) {
            mostrarError('ConfirmarContrasena', 'Las contraseñas no coinciden');
            return;
        }
        datos.contrasena = contrasena;
    } else {
        if (contrasena) {
            if (contrasena.length < 6) {
                mostrarError('Contrasena', 'La contraseña debe tener al menos 6 caracteres');
                return;
            }
            if (contrasena !== confirmarContrasena) {
                mostrarError('ConfirmarContrasena', 'Las contraseñas no coinciden');
                return;
            }
            datos.contrasena = contrasena;
        }
        datos.id_usuario = parseInt(formData.get('id_usuario'));
        datos.activo = parseInt(formData.get('activo'));
    }

    if (!validarFormularioUsuario(datos)) return;

    try {
        const url = `${API_BASE}usuarios.php?accion=${accion}`;
        const response = await fetchAutenticado(url, {
            method: 'POST',
            body: JSON.stringify(datos)
        });

        if (response && response.success) {
            mostrarAlerta(response.data.mensaje, 'success');
            cerrarModalUsuario();
            await cargarUsuarios();
        } else {
            mostrarAlerta(response ? response.error : 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al guardar usuario', 'error');
    }
}

function validarFormularioUsuario(datos) {
    limpiarErroresFormulario('formUsuario');
    let valido = true;

    if (!datos.nombre_usuario || datos.nombre_usuario.length < 4) {
        mostrarError('nombre_usuario', 'El nombre de usuario debe tener al menos 4 caracteres');
        valido = false;
    }

    if (!datos.nombre_completo) {
        mostrarError('nombre_completo', 'El nombre completo es requerido');
        valido = false;
    }

    if (!datos.email || validarEmail(datos.email)!==null) {
        mostrarError('email', 'El email no es válido');
        valido = false;
    }

    if (!datos.tipo_usuario) {
        mostrarError('tipo_usuario', 'Debes seleccionar un tipo de usuario');
        valido = false;
    }

    return valido;
}

async function toggleEstadoUsuario(idUsuario, estadoActual) {
    const nuevoEstado = estadoActual ? 0 : 1;
    const mensaje = nuevoEstado ? 'activar' : 'desactivar';

    if (!confirm(`¿Estás seguro de que deseas ${mensaje} este usuario?`)) {
        return;
    }

    try {
        const response = await fetchAutenticado(`${API_BASE}usuarios.php?accion=cambiar_estado`, {
            method: 'POST',
            body: JSON.stringify({
                id_usuario: idUsuario,
                activo: nuevoEstado
            })
        });

        if (response && response.success) {
            mostrarAlerta(`Usuario ${nuevoEstado ? 'activado' : 'desactivado'} exitosamente`, 'success');
            await cargarUsuarios();
        } else {
            mostrarAlerta(response ? response.error : 'Error al cambiar estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cambiar el estado del usuario', 'error');
    }
}

function confirmarEliminarUsuario(id) {
    accionConfirmacion = () => eliminarUsuario(id);
    document.getElementById('mensajeConfirmacion').textContent = '¿Estás seguro de que deseas desactivar este usuario? No podrá iniciar sesión.';
    document.getElementById('modalConfirmar').classList.add('active');
}

async function eliminarUsuario(id) {
    try {
        const data = await fetchAutenticado(`${API_BASE}usuarios.php?accion=eliminar&id=${id}`, {
            method: 'POST'
        });

        if (data && data.success) {
            mostrarAlerta('Usuario desactivado', 'success');
            await cargarUsuarios();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al desactivar', 'error');
    }

    cerrarModalConfirmar();
}

function ejecutarAccionConfirmada() {
    if (accionConfirmacion) {
        accionConfirmacion();
        accionConfirmacion = null;
    }
}

function cerrarModalUsuario() {
    document.getElementById('modalUsuario').classList.remove('active');
    document.getElementById('nombre_usuario').disabled = false;
    limpiarErroresFormulario('formUsuario');
}

function cerrarModalConfirmar() {
    document.getElementById('modalConfirmar').classList.remove('active');
}

function mostrarAlerta(mensaje, tipo) {
    const alert = document.getElementById('alertMessage');
    if (!alert) return;
    alert.className = `alert alert-${tipo}`;
    alert.textContent = mensaje;
    alert.classList.remove('hidden');
    setTimeout(() => alert.classList.add('hidden'), 5000);
}

function formatearFecha(fecha) {
    const d = new Date(fecha);
    const opciones = { year: 'numeric', month: 'short', day: 'numeric' };
    return d.toLocaleDateString('es-MX', opciones);
}
