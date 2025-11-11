const API_BASE = 'api/';
document.addEventListener('DOMContentLoaded', async () => {
    await verificarSesion();
    actualizarUI();
    const btnCerrarSesion = document.getElementById('btnCerrarSesion');
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarSesion();
        });
    }
});
async function verificarSesion() {
    try {
        const response = await fetch(`${API_BASE}auth.php?accion=verificar`, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem('usuario', JSON.stringify(data.data.usuario));
            return data.data.usuario;
        } else {
            localStorage.removeItem('usuario');
            return null;
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        localStorage.removeItem('usuario');
        return null;
    }
}
function obtenerUsuarioActual() {
    const usuarioStr = localStorage.getItem('usuario');
    return usuarioStr ? JSON.parse(usuarioStr) : null;
}
function estaAutenticado() {
    return obtenerUsuarioActual() !== null;
}
function esAdmin() {
    const usuario = obtenerUsuarioActual();
    return usuario && usuario.tipo === 'admin';
}
function esHuesped() {
    const usuario = obtenerUsuarioActual();
    return usuario && usuario.tipo === 'huesped';
}
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
function actualizarUI() {
    const usuario = obtenerUsuarioActual();
    const guestOnly = document.querySelectorAll('.guest-only');
    const authOnly = document.querySelectorAll('.auth-only');
    const adminOnly = document.querySelectorAll('.admin-only');
    const huespedOnly = document.querySelectorAll('.huesped-only');
    const userInfo = document.getElementById('userInfo');
    const guestInfo = document.getElementById('guestInfo');
    const userName = document.getElementById('userName');

    if (usuario) {
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
        if (guestInfo) guestInfo.classList.add('hidden');
        if (userName) userName.textContent = usuario.nombre_completo;

    } else {
        guestOnly.forEach(el => el.classList.remove('hidden'));
        authOnly.forEach(el => el.classList.add('hidden'));
        adminOnly.forEach(el => el.classList.add('hidden'));
        huespedOnly.forEach(el => el.classList.add('hidden'));

        if (userInfo) userInfo.classList.add('hidden');
        if (guestInfo) guestInfo.classList.remove('hidden');
    }
}
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
