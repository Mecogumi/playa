let reservaciones = [];
let estadoActual = 'todas';
let reservacionACancelar = null;

document.addEventListener('DOMContentLoaded', async () => {
    if (!requerirAutenticacion()) return;
    actualizarTituloPagina();
    await cargarReservaciones();
    configurarEventos();
});

function actualizarTituloPagina() {
    if (esAdmin()) {
        const pageHeader = document.querySelector('.page-header h1');
        if (pageHeader) {
            pageHeader.textContent = 'Administrar Reservaciones';
        }

        document.title = 'Administrar Reservaciones - Hotel Playa';

        const pageDescription = document.querySelector('.page-header p');
        if (pageDescription) {
            pageDescription.textContent = 'Gestiona todas las reservaciones del hotel';
        }
    }
}

function configurarEventos() {
    const filterBtns = document.querySelectorAll('.status-filters .filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            estadoActual = btn.dataset.estado;
            filtrarReservaciones();
        });
    });

    const closeBtn = document.getElementById('closeModal');
    if (closeBtn) closeBtn.onclick = cerrarModal;

    document.getElementById('btnConfirmarAccion').addEventListener('click', ejecutarCancelacion);
    document.getElementById('btnCancelarAccion').addEventListener('click', cerrarModalConfirmar);
}

async function cargarReservaciones() {
    const loading = document.getElementById('loading');
    const emptyMessage = document.getElementById('emptyMessage');

    try {
        const url = esAdmin() ?
            `${API_BASE}reservaciones.php?accion=listar` :
            `${API_BASE}reservaciones.php?accion=mis_reservaciones`;

        const data = await fetchAutenticado(url);

        if (data && data.success) {
            reservaciones = data.data.reservaciones;
            loading.classList.add('hidden');

            if (reservaciones.length === 0) {
                emptyMessage.classList.remove('hidden');
            } else {
                emptyMessage.classList.add('hidden');
                filtrarReservaciones();
            }
        }
    } catch (error) {
        console.error('Error:', error);
        loading.classList.add('hidden');
        mostrarAlerta('Error al cargar reservaciones', 'error');
    }
}

function filtrarReservaciones() {
    let reservacionesFiltradas = reservaciones;

    if (estadoActual !== 'todas') {
        reservacionesFiltradas = reservaciones.filter(r => r.estado_reservacion === estadoActual);
    }

    mostrarReservaciones(reservacionesFiltradas);
}

function mostrarReservaciones(reservaciones) {
    const container = document.getElementById('reservacionesList');
    container.innerHTML = '';

    if (reservaciones.length === 0) {
        container.innerHTML = '<div class="empty-message"><p>No hay reservaciones con este filtro</p></div>';
        return;
    }

    reservaciones.forEach(reservacion => {
        const card = crearTarjetaReservacion(reservacion);
        container.appendChild(card);
    });
}

function crearTarjetaReservacion(reservacion) {
    const div = document.createElement('div');
    div.className = 'reservacion-card';

    const estadoClass = `status-${reservacion.estado_reservacion}`;

    div.innerHTML = `
        <div class="reservacion-header">
            <div class="reservacion-id">Reservación #${reservacion.id_reservacion}</div>
            <span class="status-badge ${estadoClass}">${reservacion.estado_reservacion.toUpperCase()}</span>
        </div>

        ${esAdmin() ? `
            <div style="margin-bottom: 1rem; padding: 0.5rem; background-color: #f8f9fa; border-radius: 4px;">
                <strong>Cliente:</strong> ${sanitizarHTML(reservacion.nombre_completo)}<br>
                <strong>Email:</strong> ${sanitizarHTML(reservacion.email)}<br>
                <strong>Teléfono:</strong> ${sanitizarHTML(reservacion.telefono || 'N/A')}
            </div>
        ` : ''}

        <div class="reservacion-details">
            <div class="detail-item">
                <div class="detail-label">Fecha de Entrada</div>
                <div class="detail-value">${formatearFecha(reservacion.fecha_entrada)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Fecha de Salida</div>
                <div class="detail-value">${formatearFecha(reservacion.fecha_salida)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Noches</div>
                <div class="detail-value">${reservacion.numero_noches}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Total</div>
                <div class="detail-value" style="color: var(--primary-color); font-weight: bold;">${formatearPrecio(reservacion.total)}</div>
            </div>
        </div>

        <div class="reservacion-habitaciones">
            <strong>Habitaciones:</strong>
            ${reservacion.detalles.map(d => `
                <div class="habitacion-item">
                    ${sanitizarHTML(d.nombre)} - ${d.cantidad_habitaciones} habitación(es) - ${formatearPrecio(d.subtotal)}
                </div>
            `).join('')}
        </div>

        ${reservacion.notas ? `
            <div style="margin-top: 1rem; padding: 0.5rem; background-color: #fff3cd; border-radius: 4px;">
                <strong>Notas:</strong> ${sanitizarHTML(reservacion.notas)}
            </div>
        ` : ''}

        <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #6c757d;">
            Reservada el: ${formatearFechaHora(reservacion.fecha_reservacion)}
        </div>

        <div class="reservacion-actions">
            <button class="btn btn-secondary" onclick="verDetallesReservacion(${reservacion.id_reservacion})">
                Ver Detalles
            </button>
            ${reservacion.estado_reservacion === 'confirmada' || reservacion.estado_reservacion === 'pendiente' ?
                `<button class="btn btn-danger" onclick="confirmarCancelarReservacion(${reservacion.id_reservacion})">
                    Cancelar Reservación
                </button>` : ''
            }
        </div>
    `;

    return div;
}

async function verDetallesReservacion(idReservacion) {
    try {
        const data = await fetchAutenticado(`${API_BASE}reservaciones.php?accion=obtener&id=${idReservacion}`);

        if (data && data.success) {
            mostrarModalDetalles(data.data.reservacion);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar detalles');
    }
}

function mostrarModalDetalles(reservacion) {
    const modal = document.getElementById('modalDetalles');
    const modalBody = document.getElementById('modalBody');

    if (!modal || !modalBody) return;

    const estadoClass = `status-${reservacion.estado_reservacion}`;

    modalBody.innerHTML = `
        <h2>Reservación #${reservacion.id_reservacion}</h2>
        <span class="status-badge ${estadoClass}">${reservacion.estado_reservacion.toUpperCase()}</span>

        ${esAdmin() ? `
            <div style="margin: 1.5rem 0; padding: 1rem; background-color: #f8f9fa; border-radius: 8px;">
                <h3>Información del Cliente</h3>
                <p><strong>Nombre:</strong> ${sanitizarHTML(reservacion.nombre_completo)}</p>
                <p><strong>Email:</strong> ${sanitizarHTML(reservacion.email)}</p>
                <p><strong>Teléfono:</strong> ${sanitizarHTML(reservacion.telefono || 'N/A')}</p>
            </div>
        ` : ''}

        <div style="margin: 1.5rem 0;">
            <h3>Detalles de la Estadía</h3>
            <p><strong>Entrada:</strong> ${formatearFecha(reservacion.fecha_entrada)}</p>
            <p><strong>Salida:</strong> ${formatearFecha(reservacion.fecha_salida)}</p>
            <p><strong>Noches:</strong> ${reservacion.numero_noches}</p>
            <p><strong>Fecha de Reservación:</strong> ${formatearFechaHora(reservacion.fecha_reservacion)}</p>
        </div>

        <div style="margin: 1.5rem 0;">
            <h3>Habitaciones Reservadas</h3>
            ${reservacion.detalles.map(d => `
                <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 0.5rem;">
                    <p><strong>${sanitizarHTML(d.nombre)}</strong> (ID: ${d.id_habitacion})</p>
                    <p>Cantidad: ${d.cantidad_habitaciones} habitación(es)</p>
                    <p>Precio por habitación: ${formatearPrecio(d.precio_unitario)}</p>
                    <p>Subtotal: ${formatearPrecio(d.subtotal)}</p>
                </div>
            `).join('')}
        </div>

        <div style="margin: 1.5rem 0; padding: 1rem; background-color: var(--light); border-radius: 8px;">
            <h3>Resumen de Costos</h3>
            <p><strong>Subtotal:</strong> ${formatearPrecio(reservacion.subtotal)}</p>
            <p><strong>Impuestos (16%):</strong> ${formatearPrecio(reservacion.impuestos)}</p>
            <p style="font-size: 1.25rem; color: var(--primary-color);"><strong>Total:</strong> ${formatearPrecio(reservacion.total)}</p>
        </div>

        ${reservacion.notas ? `
            <div style="margin: 1.5rem 0;">
                <h3>Notas</h3>
                <p>${sanitizarHTML(reservacion.notas)}</p>
            </div>
        ` : ''}
    `;

    modal.classList.add('active');
}

function confirmarCancelarReservacion(idReservacion) {
    reservacionACancelar = idReservacion;
    document.getElementById('modalConfirmar').classList.add('active');
}

async function ejecutarCancelacion() {
    if (!reservacionACancelar) return;

    try {
        const data = await fetchAutenticado(`${API_BASE}reservaciones.php?accion=cancelar&id=${reservacionACancelar}`, {
            method: 'POST'
        });

        if (data && data.success) {
            mostrarAlerta('Reservación cancelada exitosamente', 'success');
            await cargarReservaciones();
        } else {
            mostrarAlerta(data ? data.error : 'Error al cancelar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cancelar reservación', 'error');
    }

    reservacionACancelar = null;
    cerrarModalConfirmar();
}

function cerrarModal() {
    document.getElementById('modalDetalles').classList.remove('active');
}

function cerrarModalConfirmar() {
    document.getElementById('modalConfirmar').classList.remove('active');
}

function formatearFecha(fecha) {
    const d = new Date(fecha);
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return d.toLocaleDateString('es-MX', opciones);
}

function formatearFechaHora(fecha) {
    const d = new Date(fecha);
    const opciones = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return d.toLocaleDateString('es-MX', opciones);
}

function mostrarAlerta(mensaje, tipo) {
    const alert = document.getElementById('alertMessage');
    if (!alert) return;
    alert.className = `alert alert-${tipo}`;
    alert.textContent = mensaje;
    alert.classList.remove('hidden');
    setTimeout(() => alert.classList.add('hidden'), 5000);
}
