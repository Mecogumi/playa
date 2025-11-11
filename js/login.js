document.addEventListener('DOMContentLoaded', () => {
    if (estaAutenticado()) {
        window.location.href = 'index.html';
        return;
    }

    const form = document.getElementById('formLogin');
    form.addEventListener('submit', handleLogin);
    agregarValidacionTiempoReal('usuario', (valor) => validarRequerido(valor, 'El usuario'));
    agregarValidacionTiempoReal('contrasena', (valor) => validarRequerido(valor, 'La contraseña'));
});
async function handleLogin(e) {
    e.preventDefault();
    limpiarErroresFormulario('formLogin');
    ocultarAlerta();
    const usuario = document.getElementById('usuario').value.trim();
    const contrasena = document.getElementById('contrasena').value;
    let valido = true;

    const errorUsuario = validarRequerido(usuario, 'El usuario');
    if (errorUsuario) {
        mostrarError('usuario', errorUsuario);
        valido = false;
    }

    const errorContrasena = validarRequerido(contrasena, 'La contraseña');
    if (errorContrasena) {
        mostrarError('contrasena', errorContrasena);
        valido = false;
    }

    if (!valido) {
        return;
    }
    const btnSubmit = document.getElementById('btnSubmit');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Iniciando sesión...';

    try {
        const response = await fetch(`${API_BASE}auth.php?accion=login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ usuario, contrasena }),
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem('usuario', JSON.stringify(data.data.usuario));
            mostrarAlerta('Inicio de sesión exitoso. Redirigiendo...', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            mostrarAlerta(data.error, 'error');
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Iniciar Sesión';
        }
    } catch (error) {
        console.error('Error en login:', error);
        mostrarAlerta('Error al iniciar sesión. Por favor intenta nuevamente.', 'error');
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Iniciar Sesión';
    }
}
function mostrarAlerta(mensaje, tipo = 'info') {
    const alert = document.getElementById('alertMessage');
    if (!alert) return;

    alert.className = `alert alert-${tipo}`;
    alert.textContent = mensaje;
    alert.classList.remove('hidden');
}
function ocultarAlerta() {
    const alert = document.getElementById('alertMessage');
    if (alert) {
        alert.classList.add('hidden');
    }
}
