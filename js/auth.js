/**
 * auth.js
 * Maneja la autenticación y gestión de sesión
 */

const API_BASE = 'api/';

// Verificar sesión al cargar la página
document.addEventListener('DOMContentLoaded', async () => {
    await verificarSesion();
    actualizarUI();

    // Agregar evento al botón de cerrar sesión si existe
    const btnCerrarSesion = document.getElementById('btnCerrarSesion');
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarSesion();
        });
    }
});

/**
 * Verifica si hay una sesión activa
 */
async function verificarSesion() {
    try {
        const response = await fetch(`${API_BASE}auth.php?accion=verificar`, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            // Guardar información del usuario en localStorage
            localStorage.setItem('usuario', JSON.stringify(data.data.usuario));
            return data.data.usuario;
        } else {
            // No hay sesión activa
            localStorage.removeItem('usuario');
            return null;
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        localStorage.removeItem('usuario');
        return null;
    }
}

/**
 * Obtiene el usuario actual desde localStorage
 */
function obtenerUsuarioActual() {
    const usuarioStr = localStorage.getItem('usuario');
    return usuarioStr ? JSON.parse(usuarioStr) : null;
}

/**
 * Verifica si el usuario está autenticado
 */
function estaAutenticado() {
    return obtenerUsuarioActual() !== null;
}

/**
 * Verifica si el usuario es administrador
 */
function esAdmin() {
    const usuario = obtenerUsuarioActual();
    return usuario && usuario.tipo === 'admin';
}

/**
 * Verifica si el usuario es huésped
 */
function esHuesped() {
    const usuario = obtenerUsuarioActual();
    return usuario && usuario.tipo === 'huesped';
}

/**
 * Cierra la sesión del usuario
 */
async function cerrarSesion() {
    if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}auth.php?accion=logout`, {
            method: 'POST',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            localStorage.removeItem('usuario');
            // Limpiar carrito también
            if (typeof limpiarCarrito === 'function') {
                limpiarCarrito();
            }
            window.location.href = 'index.html';
        } else {
            alert('Error al cerrar sesión');
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        alert('Error al cerrar sesión');
    }
}

/**
 * Actualiza la UI según el estado de autenticación
 */
function actualizarUI() {
    const usuario = obtenerUsuarioActual();

    // Elementos de navegación
    const guestOnly = document.querySelectorAll('.guest-only');
    const authOnly = document.querySelectorAll('.auth-only');
    const adminOnly = document.querySelectorAll('.admin-only');
    const huespedOnly = document.querySelectorAll('.huesped-only');
    const userInfo = document.getElementById('userInfo');
    const userName = document.getElementById('userName');

    if (usuario) {
        // Usuario autenticado
        guestOnly.forEach(el => el.classList.add('hidden'));
        authOnly.forEach(el => el.classList.remove('hidden'));

        if (usuario.tipo === 'admin') {
            adminOnly.forEach(el => el.classList.remove('hidden'));
            huespedOnly.forEach(el => el.classList.add('hidden'));
        } else if (usuario.tipo === 'huesped') {
            adminOnly.forEach(el => el.classList.add('hidden'));
            huespedOnly.forEach(el => el.classList.remove('hidden'));
        }

        if (userInfo) userInfo.classList.remove('hidden');
        if (userName) userName.textContent = usuario.nombre_completo;

    } else {
        // Usuario no autenticado
        guestOnly.forEach(el => el.classList.remove('hidden'));
        authOnly.forEach(el => el.classList.add('hidden'));
        adminOnly.forEach(el => el.classList.add('hidden'));
        huespedOnly.forEach(el => el.classList.add('hidden'));

        if (userInfo) userInfo.classList.add('hidden');
    }
}

/**
 * Redirige a login si no está autenticado
 */
function requerirAutenticacion(tipoRequerido = null) {
    const usuario = obtenerUsuarioActual();

    if (!usuario) {
        alert('Debes iniciar sesión para acceder a esta página');
        window.location.href = 'login.html';
        return false;
    }

    if (tipoRequerido && usuario.tipo !== tipoRequerido) {
        alert('No tienes permisos para acceder a esta página');
        window.location.href = 'index.html';
        return false;
    }

    return true;
}

/**
 * Realiza una petición autenticada a la API
 */
async function fetchAutenticado(url, options = {}) {
    const defaultOptions = {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    };

    const finalOptions = { ...defaultOptions, ...options };

    try {
        const response = await fetch(url, finalOptions);
        const data = await response.json();

        // Si la sesión expiró, redirigir a login
        if (!data.success && response.status === 401) {
            localStorage.removeItem('usuario');
            alert('Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
            window.location.href = 'login.html';
            return null;
        }

        return data;
    } catch (error) {
        console.error('Error en petición autenticada:', error);
        throw error;
    }
}
