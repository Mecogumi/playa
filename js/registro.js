/**
 * registro.js
 * Funcionalidad para registro.html
 */



document.addEventListener('DOMContentLoaded', () => {
    if (estaAutenticado()) {
        window.location.href = 'index.html';
        return;
    }

    const form = document.getElementById('formRegistro');
    form.addEventListener('submit', handleRegistro);

    // Agregar validaciones en tiempo real
    agregarValidacionTiempoReal('usuario', (valor) => validarLongitudMinima(valor, 4, 'El usuario'));
    agregarValidacionTiempoReal('nombre_completo', (valor) => validarLongitudMinima(valor, 3, 'El nombre completo'));
    agregarValidacionTiempoReal('email', validarEmail);
    agregarValidacionTiempoReal('telefono', (valor) => validarTelefono(valor, false));
    agregarValidacionTiempoReal('contrasena', (valor) => validarLongitudMinima(valor, 6, 'La contraseña'));

    // Validar confirmación de contraseña cuando cambie
    const confirmarInput = document.getElementById('confirmar_contrasena');
    confirmarInput.addEventListener('blur', () => {
        const contrasena = document.getElementById('contrasena').value;
        const confirmar = confirmarInput.value;
        const error = validarContrasenaCoincide(contrasena, confirmar);
        if (error) {
            mostrarError('confirmar_contrasena', error);
        } else {
            limpiarError('confirmar_contrasena');
        }
    });
});

async function handleRegistro(e) {
    e.preventDefault();

    limpiarErroresFormulario('formRegistro');
    ocultarAlerta();

    // Obtener valores
    const datos = {
        usuario: document.getElementById('usuario').value.trim(),
        nombre_completo: document.getElementById('nombre_completo').value.trim(),
        email: document.getElementById('email').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        contrasena: document.getElementById('contrasena').value,
        confirmar_contrasena: document.getElementById('confirmar_contrasena').value
    };

    // Validar
    let valido = true;
    const errores = {
        usuario: validarLongitudMinima(datos.usuario, 4, 'El usuario'),
        nombre_completo: validarLongitudMinima(datos.nombre_completo, 3, 'El nombre completo'),
        email: validarEmail(datos.email),
        telefono: validarTelefono(datos.telefono, false),
        contrasena: validarLongitudMinima(datos.contrasena, 6, 'La contraseña'),
        confirmar_contrasena: validarContrasenaCoincide(datos.contrasena, datos.confirmar_contrasena)
    };

    for (const [campo, error] of Object.entries(errores)) {
        if (error) {
            mostrarError(campo, error);
            valido = false;
        }
    }

    if (!valido) return;

    const btnSubmit = document.getElementById('btnSubmit');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Registrando...';

    try {
        const response = await fetch(`${API_BASE}auth.php?accion=registro`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        const data = await response.json();

        if (data.success) {
            mostrarAlerta('Registro exitoso. Redirigiendo al login...', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            mostrarAlerta(data.error, 'error');
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Registrarse';
        }
    } catch (error) {
        console.error('Error en registro:', error);
        mostrarAlerta('Error al registrarse. Por favor intenta nuevamente.', 'error');
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Registrarse';
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
    if (alert) alert.classList.add('hidden');
}
