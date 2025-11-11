function validarRequerido(valor, nombreCampo = 'Este campo') {
    if (!valor || valor.trim() === '') {
        return `${nombreCampo} es requerido`;
    }
    return null;
}
function validarLongitudMinima(valor, minimo, nombreCampo = 'Este campo') {
    if (valor && valor.length < minimo) {
        return `${nombreCampo} debe tener al menos ${minimo} caracteres`;
    }
    return null;
}
function validarLongitudMaxima(valor, maximo, nombreCampo = 'Este campo') {
    if (valor && valor.length > maximo) {
        return `${nombreCampo} no debe exceder ${maximo} caracteres`;
    }
    return null;
}
function validarEmail(email) {
    if (!email || email.trim() === '') {
        return 'El email es requerido';
    }
    console.log(email)
    const regex = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
    if (!regex.test(email)) {
        return 'El email no es válido2';
    }

    return null;
}
function validarTelefono(telefono, requerido = false) {
    if (!telefono || telefono.trim() === '') {
        if (requerido) {
            return 'El teléfono es requerido';
        }
        return null;
    }
    const regex = /^[\d\s\-\(\)\+]+$/;
    if (!regex.test(telefono)) {
        return 'El teléfono no es válido';
    }

    return null;
}
function validarContrasenaCoincide(contrasena, confirmar) {
    if (contrasena !== confirmar) {
        return 'Las contraseñas no coinciden';
    }
    return null;
}
function validarNumero(valor, nombreCampo = 'Este campo') {
    if (valor === '' || valor === null || valor === undefined) {
        return `${nombreCampo} es requerido`;
    }

    if (isNaN(valor)) {
        return `${nombreCampo} debe ser un número válido`;
    }

    return null;
}
function validarNumeroPositivo(valor, nombreCampo = 'Este campo') {
    const errorNumero = validarNumero(valor, nombreCampo);
    if (errorNumero) return errorNumero;

    if (parseFloat(valor) < 0) {
        return `${nombreCampo} debe ser un número positivo`;
    }

    return null;
}
function validarRango(valor, min, max, nombreCampo = 'Este campo') {
    const errorNumero = validarNumero(valor, nombreCampo);
    if (errorNumero) return errorNumero;

    const num = parseFloat(valor);
    if (num < min || num > max) {
        return `${nombreCampo} debe estar entre ${min} y ${max}`;
    }

    return null;
}
function validarFecha(fecha, nombreCampo = 'La fecha') {
    if (!fecha || fecha.trim() === '') {
        return `${nombreCampo} es requerida`;
    }

    const date = new Date(fecha);
    if (isNaN(date.getTime())) {
        return `${nombreCampo} no es válida`;
    }

    return null;
}
function validarFechaFutura(fecha, nombreCampo = 'La fecha') {
    const errorFecha = validarFecha(fecha, nombreCampo);
    if (errorFecha) return errorFecha;

    const date = new Date(fecha);
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    if (date < hoy) {
        return `${nombreCampo} debe ser igual o posterior a hoy`;
    }

    return null;
}
function validarFechaPosterior(fechaInicio, fechaFin, nombreInicio = 'La fecha de inicio', nombreFin = 'La fecha de fin') {
    const errorInicio = validarFecha(fechaInicio, nombreInicio);
    if (errorInicio) return errorInicio;

    const errorFin = validarFecha(fechaFin, nombreFin);
    if (errorFin) return errorFin;

    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);

    if (fin <= inicio) {
        return `${nombreFin} debe ser posterior a ${nombreInicio.toLowerCase()}`;
    }

    return null;
}
function validarArchivo(archivo, extensionesPermitidas = [], tamanoMaxMB = 5) {
    if (!archivo) {
        return null;
    }
    if (extensionesPermitidas.length > 0) {
        const extension = archivo.name.split('.').pop().toLowerCase();
        if (!extensionesPermitidas.includes(extension)) {
            return `Solo se permiten archivos ${extensionesPermitidas.join(', ')}`;
        }
    }
    const tamanoMB = archivo.size / (1024 * 1024);
    if (tamanoMB > tamanoMaxMB) {
        return `El archivo no debe exceder ${tamanoMaxMB}MB`;
    }

    return null;
}
function mostrarError(idCampo, mensaje) {
    const campo = document.getElementById(idCampo);
    const errorSpan = document.getElementById(`error${idCampo.charAt(0).toUpperCase() + idCampo.slice(1)}`);

    if (campo) {
        campo.classList.add('error');
    }

    if (errorSpan) {
        errorSpan.textContent = mensaje;
        errorSpan.style.display = 'block';
    }
}
function limpiarError(idCampo) {
    const campo = document.getElementById(idCampo);
    const errorSpan = document.getElementById(`error${idCampo.charAt(0).toUpperCase() + idCampo.slice(1)}`);

    if (campo) {
        campo.classList.remove('error');
    }

    if (errorSpan) {
        errorSpan.textContent = '';
        errorSpan.style.display = 'none';
    }
}
function limpiarErroresFormulario(formularioId) {
    const formulario = document.getElementById(formularioId);
    if (!formulario) return;

    const campos = formulario.querySelectorAll('.form-control');
    campos.forEach(campo => {
        campo.classList.remove('error');
    });

    const errores = formulario.querySelectorAll('.error-message');
    errores.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
    });
}
function agregarValidacionTiempoReal(idCampo, funcionValidacion) {
    const campo = document.getElementById(idCampo);
    if (!campo) return;

    campo.addEventListener('blur', () => {
        const error = funcionValidacion(campo.value);
        if (error) {
            mostrarError(idCampo, error);
        } else {
            limpiarError(idCampo);
        }
    });

    campo.addEventListener('input', () => {
        limpiarError(idCampo);
    });
}
function sanitizarHTML(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}
function validarFormulario(formularioId, validaciones) {
    limpiarErroresFormulario(formularioId);

    let valido = true;
    let primerError = null;

    for (const [campo, validacion] of Object.entries(validaciones)) {
        const valor = document.getElementById(campo)?.value;
        const error = validacion(valor);

        if (error) {
            mostrarError(campo, error);
            valido = false;
            if (!primerError) {
                primerError = campo;
            }
        }
    }
    if (primerError) {
        document.getElementById(primerError)?.focus();
    }

    return valido;
}
