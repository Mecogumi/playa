/**
 * carrito-page.js
 * Funcionalidad para carrito.html
 */

let numeroNoches = 0;

document.addEventListener('DOMContentLoaded', () => {
    if (!requerirAutenticacion()) return;
    if (!esHuesped()) {
        alert('Solo los huéspedes pueden acceder al carrito');
        window.location.href = 'index.html';
        return;
    }

    cargarCarrito();
    configurarEventos();
    establecerFechasMinimas();
});

function configurarEventos() {
    document.getElementById('fecha_entrada').addEventListener('change', calcularNoches);
    document.getElementById('fecha_salida').addEventListener('change', calcularNoches);
    document.getElementById('btnProcesarReservacion').addEventListener('click', mostrarConfirmacionPago);
    document.getElementById('btnConfirmarPago').addEventListener('click', procesarReservacion);
    document.getElementById('btnCancelarPago').addEventListener('click', () => {
        document.getElementById('modalConfirmarPago').classList.remove('active');
    });
}

function establecerFechasMinimas() {
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_entrada').min = hoy;
    document.getElementById('fecha_salida').min = hoy;
}

function cargarCarrito() {
    const carrito = obtenerCarrito();
    const carritoVacio = document.getElementById('carritoVacio');
    const carritoItems = document.getElementById('carritoItems');
    const resumenCostos = document.getElementById('resumenCostos');

    if (carrito.length === 0) {
        carritoVacio.style.display = 'block';
        carritoItems.classList.add('hidden');
        resumenCostos.style.display = 'none';
        return;
    }

    carritoVacio.style.display = 'none';
    carritoItems.classList.remove('hidden');
    resumenCostos.style.display = 'block';

    mostrarItemsCarrito(carrito);
    actualizarCostos();
}

function mostrarItemsCarrito(carrito) {
    const container = document.getElementById('carritoItems');
    container.innerHTML = '';

    carrito.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';

        const imagenUrl = item.imagen_principal || 'images/placeholder.jpg';

        div.innerHTML = `
            ${item.imagen_principal ?
                `<img src="${imagenUrl}" alt="${sanitizarHTML(item.nombre)}" class="cart-item-image">` :
                '<div class="cart-item-image" style="background:#667eea;display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;">🏨</div>'
            }
            <div class="cart-item-details">
                <div class="cart-item-title">${sanitizarHTML(item.nombre)}</div>
                <div>${sanitizarHTML(item.nombre_categoria)}</div>
                <div class="cart-item-price">${formatearPrecio(item.precio_noche)}/noche</div>
            </div>
            <div class="cart-item-actions">
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="cambiarCantidad(${item.id_habitacion}, ${item.cantidad - 1})">-</button>
                    <span class="quantity-value">${item.cantidad}</span>
                    <button class="quantity-btn" onclick="cambiarCantidad(${item.id_habitacion}, ${item.cantidad + 1})">+</button>
                </div>
                <button class="btn btn-danger" onclick="eliminarItem(${item.id_habitacion})">🗑️</button>
            </div>
        `;

        container.appendChild(div);
    });
}

function cambiarCantidad(idHabitacion, nuevaCantidad) {
    if (nuevaCantidad < 1) {
        eliminarItem(idHabitacion);
        return;
    }

    if (actualizarCantidadCarrito(idHabitacion, nuevaCantidad)) {
        cargarCarrito();
    }
}

function eliminarItem(idHabitacion) {
    if (eliminarDelCarrito(idHabitacion)) {
        cargarCarrito();
    }
}

function calcularNoches() {
    const fechaEntrada = document.getElementById('fecha_entrada').value;
    const fechaSalida = document.getElementById('fecha_salida').value;

    if (!fechaEntrada || !fechaSalida) {
        numeroNoches = 0;
        document.getElementById('numeroNoches').textContent = '0';
        return;
    }

    const entrada = new Date(fechaEntrada);
    const salida = new Date(fechaSalida);

    if (salida <= entrada) {
        alert('La fecha de salida debe ser posterior a la fecha de entrada');
        document.getElementById('fecha_salida').value = '';
        numeroNoches = 0;
        document.getElementById('numeroNoches').textContent = '0';
        return;
    }

    const diff = salida - entrada;
    numeroNoches = Math.ceil(diff / (1000 * 60 * 60 * 24));
    document.getElementById('numeroNoches').textContent = numeroNoches;
    actualizarCostos();
}

function actualizarCostos() {
    const noches = numeroNoches > 0 ? numeroNoches : 1;
    const subtotal = calcularSubtotal(noches);
    const impuestos = calcularImpuestos(subtotal);
    const total = subtotal + impuestos;

    document.getElementById('subtotal').textContent = formatearPrecio(subtotal);
    document.getElementById('impuestos').textContent = formatearPrecio(impuestos);
    document.getElementById('total').textContent = formatearPrecio(total);
}

function mostrarConfirmacionPago() {
    // Validar fechas
    const fechaEntrada = document.getElementById('fecha_entrada').value;
    const fechaSalida = document.getElementById('fecha_salida').value;

    if (!fechaEntrada || !fechaSalida) {
        alert('Debes seleccionar las fechas de entrada y salida');
        return;
    }

    if (numeroNoches <= 0) {
        alert('Las fechas seleccionadas no son válidas');
        return;
    }

    const carrito = obtenerCarrito();
    if (carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }

    // Mostrar modal de confirmación
    const noches = numeroNoches;
    const total = calcularTotal(noches);

    document.getElementById('totalModal').textContent = formatearPrecio(total);
    document.getElementById('nochesModal').textContent = noches;
    document.getElementById('modalConfirmarPago').classList.add('active');
}

async function procesarReservacion() {
    const btnConfirmar = document.getElementById('btnConfirmarPago');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Procesando...';

    const fechaEntrada = document.getElementById('fecha_entrada').value;
    const fechaSalida = document.getElementById('fecha_salida').value;
    const notas = document.getElementById('notas').value;
    const carrito = obtenerCarrito();

    const datos = {
        fecha_entrada: fechaEntrada,
        fecha_salida: fechaSalida,
        notas: notas,
        habitaciones: carrito.map(item => ({
            id_habitacion: item.id_habitacion,
            cantidad: item.cantidad
        }))
    };

    try {
        const response = await fetchAutenticado(`${API_BASE}reservaciones.php?accion=crear`, {
            method: 'POST',
            body: JSON.stringify(datos)
        });

        if (response && response.success) {
            limpiarCarrito();
            alert('¡Reservación procesada exitosamente!\\n\\nID de Reservación: ' + response.data.id_reservacion + '\\nTotal: ' + formatearPrecio(response.data.total));
            window.location.href = 'mis-reservaciones.html';
        } else {
            alert('Error al procesar reservación: ' + (response ? response.error : 'Error desconocido'));
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Sí, Procesar Reservación';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar reservación');
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = 'Sí, Procesar Reservación';
    }

    document.getElementById('modalConfirmarPago').classList.remove('active');
}
