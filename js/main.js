document.addEventListener('DOMContentLoaded', async () => {
    await cargarCategorias();
    await cargarHabitacionesDestacadas();
    configurarBusqueda();
    configurarModal();
});
async function cargarCategorias() {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=categorias`);
        const data = await response.json();

        if (data.success) {
            mostrarCategorias(data.data.categorias);
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}
function mostrarCategorias(categorias) {
    const grid = document.getElementById('categoriasGrid');
    if (!grid) return;

    grid.innerHTML = '';

    categorias.forEach(categoria => {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.onclick = () => window.location.href = `habitaciones.html?categoria=${categoria.id_categoria}`;

        card.innerHTML = `
            <h3>${sanitizarHTML(categoria.nombre_categoria)}</h3>
            <p>${sanitizarHTML(categoria.descripcion || '')}</p>
            <div class="count">${categoria.total_habitaciones}</div>
            <small>${categoria.total_habitaciones} habitación(es)</small>
        `;

        grid.appendChild(card);
    });
}
async function cargarHabitacionesDestacadas() {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=listar`);
        const data = await response.json();

        if (data.success) {
            const habitaciones = data.data.habitaciones.slice(0, 6);
            mostrarHabitaciones(habitaciones);
        }
    } catch (error) {
        console.error('Error al cargar habitaciones:', error);
    }
}
function mostrarHabitaciones(habitaciones) {
    const grid = document.getElementById('habitacionesGrid');
    if (!grid) return;

    grid.innerHTML = '';

    if (habitaciones.length === 0) {
        grid.innerHTML = '<p class="empty-message">No hay habitaciones disponibles</p>';
        return;
    }

    habitaciones.forEach(habitacion => {
        const card = crearTarjetaHabitacion(habitacion);
        grid.appendChild(card);
    });
}
function crearTarjetaHabitacion(habitacion) {
    const card = document.createElement('div');
    card.className = 'room-card';

    const imagenUrl = habitacion.imagen_principal || 'images/placeholder.jpg';

    card.innerHTML = `
        ${habitacion.imagen_principal ?
            `<img src="${imagenUrl}" alt="${sanitizarHTML(habitacion.nombre)}" class="room-image" onclick="mostrarDetalles(${habitacion.id_habitacion})">` :
            `<div class="room-image-placeholder" onclick="mostrarDetalles(${habitacion.id_habitacion})">🏨</div>`
        }
        <div class="room-content">
            <span class="room-category">${sanitizarHTML(habitacion.nombre_categoria)}</span>
            <h3 class="room-title">${sanitizarHTML(habitacion.nombre)}</h3>
            <p class="room-description">${sanitizarHTML(habitacion.descripcion?.substring(0, 100) || '')}...</p>
            <div class="room-details">
                <span>👥 ${habitacion.capacidad_personas} personas</span>
                <span>🚪 Disponibles: ${habitacion.cantidad_disponible}</span>
            </div>
            <div class="room-price">
                ${formatearPrecio(habitacion.precio_noche)} <small>/noche</small>
            </div>
            <div class="room-actions">
                <button class="btn btn-secondary" onclick="mostrarDetalles(${habitacion.id_habitacion})">
                    Ver Detalles
                </button>
                ${esHuesped() && habitacion.cantidad_disponible > 0 ?
                    `<button class="btn btn-primary" onclick="agregarHabitacionAlCarrito(${habitacion.id_habitacion})">
                        Agregar al Carrito
                    </button>` :
                    ''
                }
            </div>
        </div>
    `;

    return card;
}
async function mostrarDetalles(idHabitacion) {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=obtener&id=${idHabitacion}`);
        const data = await response.json();

        if (data.success) {
            const habitacion = data.data.habitacion;
            mostrarModalDetalles(habitacion);
        }
    } catch (error) {
        console.error('Error al cargar detalles:', error);
        alert('Error al cargar los detalles de la habitación');
    }
}
function mostrarModalDetalles(habitacion) {
    const modal = document.getElementById('modalDetalles');
    const modalBody = document.getElementById('modalBody');

    if (!modal || !modalBody) return;

    let imagenesHTML = '';
    if (habitacion.imagenes && habitacion.imagenes.length > 0) {
        imagenesHTML = '<div class="images-gallery">';
        habitacion.imagenes.forEach(imagen => {
            imagenesHTML += `
                <div class="image-item">
                    <img src="${imagen.ruta_archivo}" alt="${sanitizarHTML(habitacion.nombre)}">
                    ${imagen.es_principal ? '<span class="image-badge">Principal</span>' : ''}
                </div>
            `;
        });
        imagenesHTML += '</div>';
    }

    modalBody.innerHTML = `
        <h2>${sanitizarHTML(habitacion.nombre)}</h2>
        <span class="room-category">${sanitizarHTML(habitacion.nombre_categoria)}</span>

        ${imagenesHTML}

        <div style="margin: 1.5rem 0;">
            <h3>Descripción</h3>
            <p>${sanitizarHTML(habitacion.descripcion || 'Sin descripción')}</p>
        </div>

        <div style="margin: 1.5rem 0;">
            <h3>Detalles</h3>
            <p><strong>Número:</strong> ${sanitizarHTML(habitacion.numero_habitacion)}</p>
            <p><strong>Capacidad:</strong> ${habitacion.capacidad_personas} personas</p>
            <p><strong>Disponibles:</strong> ${habitacion.cantidad_disponible} habitaciones</p>
            <p><strong>Precio por noche:</strong> ${formatearPrecio(habitacion.precio_noche)}</p>
        </div>

        ${habitacion.caracteristicas ? `
            <div style="margin: 1.5rem 0;">
                <h3>Características</h3>
                <p>${sanitizarHTML(habitacion.caracteristicas)}</p>
            </div>
        ` : ''}

        ${esHuesped() && habitacion.cantidad_disponible > 0 ? `
            <div style="margin-top: 2rem;">
                <button class="btn btn-primary btn-block" onclick="agregarHabitacionAlCarrito(${habitacion.id_habitacion}); cerrarModal();">
                    Agregar al Carrito
                </button>
            </div>
        ` : ''}
    `;

    modal.classList.add('active');
}
async function agregarHabitacionAlCarrito(idHabitacion) {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=obtener&id=${idHabitacion}`);
        const data = await response.json();

        if (data.success) {
            const habitacion = data.data.habitacion;
            if (agregarAlCarrito(habitacion, 1)) {
                alert('Habitación agregada al carrito exitosamente');
            }
        }
    } catch (error) {
        console.error('Error al agregar al carrito:', error);
        alert('Error al agregar la habitación al carrito');
    }
}
function configurarBusqueda() {
    const form = document.getElementById('formBusqueda');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const termino = document.getElementById('searchTerm').value;
        if (termino.trim()) {
            window.location.href = `habitaciones.html?buscar=${encodeURIComponent(termino)}`;
        }
    });
}
function configurarModal() {
    const modal = document.getElementById('modalDetalles');
    const closeBtn = document.getElementById('closeModal');

    if (closeBtn) {
        closeBtn.onclick = cerrarModal;
    }

    if (modal) {
        window.onclick = (e) => {
            if (e.target === modal) {
                cerrarModal();
            }
        };
    }
}
function cerrarModal() {
    const modal = document.getElementById('modalDetalles');
    if (modal) {
        modal.classList.remove('active');
    }
}
