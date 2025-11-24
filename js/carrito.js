const COOKIE_NOMBRE = 'carrito_habitaciones';
const COOKIE_DIAS = 7;

// Funciones para manejo de cookies
function getCookie(nombre) {
    const name = nombre + "=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');

    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function setCookie(nombre, valor, dias) {
    const d = new Date();
    d.setTime(d.getTime() + (dias * 24 * 60 * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = nombre + "=" + valor + ";" + expires + ";path=/";
}

function deleteCookie(nombre) {
    document.cookie = nombre + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}

function obtenerCarrito() {
    const carritoStr = getCookie(COOKIE_NOMBRE);
    return carritoStr ? JSON.parse(carritoStr) : [];
}

function guardarCarrito(carrito) {
    setCookie(COOKIE_NOMBRE, JSON.stringify(carrito), COOKIE_DIAS);
    actualizarBadgeCarrito();
}
function agregarAlCarrito(habitacion, cantidad = 1) {
    const usuario = obtenerUsuarioActual();
    if (!usuario) {
        alert('Debes iniciar sesión para agregar habitaciones al carrito');
        window.location.href = 'login.html';
        return false;
    }

    if (usuario.tipo !== 'huesped') {
        alert('Solo los huéspedes registrados pueden realizar reservaciones');
        return false;
    }
    if (cantidad < 1) {
        alert('La cantidad debe ser al menos 1');
        return false;
    }

    if (cantidad > habitacion.cantidad_disponible) {
        alert(`Solo hay ${habitacion.cantidad_disponible} habitaciones disponibles de este tipo`);
        return false;
    }
    const carrito = obtenerCarrito();
    const index = carrito.findIndex(item => item.id_habitacion === habitacion.id_habitacion);
    if (index !== -1) {
        const nuevaCantidad = carrito[index].cantidad + cantidad;

        if (nuevaCantidad > habitacion.cantidad_disponible) {
            alert(`No puedes agregar más habitaciones. Máximo disponible: ${habitacion.cantidad_disponible}`);
            return false;
        }

        carrito[index].cantidad = nuevaCantidad;
    } else {
        carrito.push({
            id_habitacion: habitacion.id_habitacion,
            nombre: habitacion.nombre,
            nombre_categoria: habitacion.nombre_categoria,
            precio_noche: parseFloat(habitacion.precio_noche),
            cantidad: cantidad,
            imagen_principal: habitacion.imagen_principal || null
        });
    }
    guardarCarrito(carrito);
    return true;
}
function actualizarCantidadCarrito(idHabitacion, nuevaCantidad) {
    if (nuevaCantidad < 1) {
        return eliminarDelCarrito(idHabitacion);
    }

    const carrito = obtenerCarrito();
    const index = carrito.findIndex(item => item.id_habitacion === idHabitacion);

    if (index !== -1) {
        carrito[index].cantidad = nuevaCantidad;
        guardarCarrito(carrito);
        return true;
    }

    return false;
}
function eliminarDelCarrito(idHabitacion) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta habitación del carrito?')) {
        return false;
    }

    let carrito = obtenerCarrito();
    carrito = carrito.filter(item => item.id_habitacion !== idHabitacion);
    guardarCarrito(carrito);
    return true;
}
function limpiarCarrito() {
    deleteCookie(COOKIE_NOMBRE);
    actualizarBadgeCarrito();
    return true;
}
function obtenerTotalHabitaciones() {
    const carrito = obtenerCarrito();
    return carrito.reduce((total, item) => total + item.cantidad, 0);
}
function calcularSubtotal(numeroNoches = 1) {
    const carrito = obtenerCarrito();
    return carrito.reduce((total, item) => {
        return total + (item.precio_noche * item.cantidad * numeroNoches);
    }, 0);
}
function calcularImpuestos(subtotal) {
    return subtotal * 0.16;
}
function calcularTotal(numeroNoches = 1) {
    const subtotal = calcularSubtotal(numeroNoches);
    const impuestos = calcularImpuestos(subtotal);
    return subtotal + impuestos;
}
function actualizarBadgeCarrito() {
    const badge = document.getElementById('cartBadge');
    if (badge) {
        const total = obtenerTotalHabitaciones();
        badge.textContent = total;
        badge.style.display = total > 0 ? 'flex' : 'none';
    }
}
function formatearPrecio(precio) {
    return '$' + parseFloat(precio).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
function estaEnCarrito(idHabitacion) {
    const carrito = obtenerCarrito();
    return carrito.some(item => item.id_habitacion === idHabitacion);
}
function obtenerCantidadEnCarrito(idHabitacion) {
    const carrito = obtenerCarrito();
    const item = carrito.find(item => item.id_habitacion === idHabitacion);
    return item ? item.cantidad : 0;
}
document.addEventListener('DOMContentLoaded', () => {
    actualizarBadgeCarrito();
});
