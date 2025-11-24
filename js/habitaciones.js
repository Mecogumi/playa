let habitacionesData = [];
let categoriasData = [];

document.addEventListener('DOMContentLoaded', async () => {
    await cargarCategorias();

    const urlParams = new URLSearchParams(window.location.search);
    const categoriaId = urlParams.get('categoria');
    const terminoBusqueda = urlParams.get('buscar');

    if (terminoBusqueda) {
        await buscarHabitaciones(terminoBusqueda);
    } else if (categoriaId) {
        await cargarHabitacionesPorCategoria(parseInt(categoriaId));
        actualizarBotonActivo(categoriaId);
    } else {
        await cargarTodasLasHabitaciones();
        actualizarBotonActivo('todas');
    }

    configurarBusqueda();
    configurarFiltros();
    configurarModal();
});

async function cargarCategorias() {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=categorias`);
        const data = await response.json();
        if (data.success) {
            categoriasData = data.data.categorias;
            mostrarFiltrosCategorias();
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

function mostrarFiltrosCategorias() {
    const container = document.getElementById('categoryFilters');
    if (!container) return;

    categoriasData.forEach(cat => {
        const btn = document.createElement('button');
        btn.className = 'filter-btn';
        btn.textContent = cat.nombre_categoria;
        btn.dataset.categoria = cat.id_categoria;
        btn.onclick = () => filtrarPorCategoria(cat.id_categoria);
        container.appendChild(btn);
    });
}

async function cargarTodasLasHabitaciones() {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=listar`);
        const data = await response.json();
        if (data.success) {
            habitacionesData = data.data.habitaciones;
            mostrarTodasLasHabitaciones();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cargar habitaciones', 'error');
    }
}

async function cargarHabitacionesPorCategoria(idCategoria) {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=por_categoria&id_categoria=${idCategoria}`);
        const data = await response.json();
        if (data.success) {
            habitacionesData = data.data.habitaciones;
            mostrarHabitacionesAgrupadasPorCategoria();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function buscarHabitaciones(termino) {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=buscar&termino=${encodeURIComponent(termino)}`);
        const data = await response.json();
        if (data.success) {
            habitacionesData = data.data.habitaciones;
            mostrarResultadosBusqueda(termino, data.data.total_resultados);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al buscar habitaciones', 'error');
    }
}

function mostrarTodasLasHabitaciones() {
    const container = document.getElementById('habitacionesPorCategoria');
    if (!container) return;

    container.innerHTML = '';

    if (habitacionesData.length === 0) {
        container.innerHTML = '<div class="empty-message"><p>No se encontraron habitaciones</p></div>';
        return;
    }

    const section = document.createElement('div');
    section.className = 'category-section';

    const header = document.createElement('div');
    header.className = 'category-header';
    header.innerHTML = `<h2>Todas las habitaciones (${habitacionesData.length})</h2>`;

    const grid = document.createElement('div');
    grid.className = 'rooms-grid';

    habitacionesData.forEach(hab => {
        grid.appendChild(crearTarjetaHabitacion(hab));
    });

    section.appendChild(header);
    section.appendChild(grid);
    container.appendChild(section);
}

function mostrarHabitacionesAgrupadasPorCategoria() {
    const container = document.getElementById('habitacionesPorCategoria');
    if (!container) return;

    container.innerHTML = '';

    if (habitacionesData.length === 0) {
        container.innerHTML = '<div class="empty-message"><p>No se encontraron habitaciones</p></div>';
        return;
    }

    const habitacionesPorCategoria = {};
    habitacionesData.forEach(hab => {
        if (!habitacionesPorCategoria[hab.nombre_categoria]) {
            habitacionesPorCategoria[hab.nombre_categoria] = [];
        }
        habitacionesPorCategoria[hab.nombre_categoria].push(hab);
    });

    Object.entries(habitacionesPorCategoria).forEach(([categoria, habitaciones]) => {
        const section = document.createElement('div');
        section.className = 'category-section';

        const header = document.createElement('div');
        header.className = 'category-header';
        header.innerHTML = `<h2>${sanitizarHTML(categoria)} (${habitaciones.length})</h2>`;

        const grid = document.createElement('div');
        grid.className = 'rooms-grid';

        habitaciones.forEach(hab => {
            grid.appendChild(crearTarjetaHabitacion(hab));
        });

        section.appendChild(header);
        section.appendChild(grid);
        container.appendChild(section);
    });
}

function mostrarResultadosBusqueda(termino, total) {
    const container = document.getElementById('habitacionesPorCategoria');
    if (!container) return;

    container.innerHTML = '';

    const section = document.createElement('div');
    section.className = 'category-section';

    const header = document.createElement('div');
    header.className = 'category-header';
    header.innerHTML = `<h2>Resultados de búsqueda para "${sanitizarHTML(termino)}" (${total})</h2>`;

    section.appendChild(header);

    if (total === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-message';
        emptyMessage.innerHTML = '<p>No se encontraron resultados</p>';
        section.appendChild(emptyMessage);
    } else {
        const grid = document.createElement('div');
        grid.className = 'rooms-grid';
        habitacionesData.forEach(hab => {
            grid.appendChild(crearTarjetaHabitacion(hab));
        });
        section.appendChild(grid);
    }

    container.appendChild(section);
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
            <p class="room-description">${sanitizarHTML((habitacion.descripcion || '').substring(0, 100))}...</p>
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
                        ${estaEnCarrito(habitacion.id_habitacion) ? 'Agregar Más' : 'Agregar'}
                    </button>` : ''
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
            mostrarModalDetalles(data.data.habitacion);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar detalles');
    }
}

function mostrarModalDetalles(habitacion) {
    const modal = document.getElementById('modalDetalles');
    const modalBody = document.getElementById('modalBody');
    if (!modal || !modalBody) return;

    let imagenesHTML = '';
    if (habitacion.imagenes && habitacion.imagenes.length > 0) {
        const imagenPrincipal = habitacion.imagenes.find(img => img.es_principal) || habitacion.imagenes[0];

        imagenesHTML = `
            <div class="product-gallery">
                <div class="main-image-container">
                    <img id="mainImage" src="${imagenPrincipal.ruta_archivo}" alt="${sanitizarHTML(habitacion.nombre)}" class="main-image">
                </div>
                <div class="thumbnails-container">
                    ${habitacion.imagenes.map(img => `
                        <div class="thumbnail ${img.id_imagen === imagenPrincipal.id_imagen ? 'active' : ''}"
                             onclick="cambiarImagenPrincipal('${img.ruta_archivo}', this)">
                            <img src="${img.ruta_archivo}" alt="${sanitizarHTML(habitacion.nombre)}">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
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
            <p><strong>Disponibles:</strong> ${habitacion.cantidad_disponible}</p>
            <p><strong>Precio:</strong> ${formatearPrecio(habitacion.precio_noche)}/noche</p>
        </div>
        ${habitacion.caracteristicas ? `<div style="margin: 1.5rem 0;"><h3>Características</h3><p>${sanitizarHTML(habitacion.caracteristicas)}</p></div>` : ''}
        ${esHuesped() && habitacion.cantidad_disponible > 0 ? `
            <button class="btn btn-primary btn-block" onclick="agregarHabitacionAlCarrito(${habitacion.id_habitacion}); cerrarModal();">Agregar al Carrito</button>
        ` : ''}
    `;

    modal.classList.add('active');
}

function cambiarImagenPrincipal(rutaImagen, thumbnailElement) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.src = rutaImagen;
    }

    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumb => thumb.classList.remove('active'));

    if (thumbnailElement) {
        thumbnailElement.classList.add('active');
    }
}

async function agregarHabitacionAlCarrito(idHabitacion) {
    try {
        const response = await fetch(`${API_BASE}habitaciones.php?accion=obtener&id=${idHabitacion}`);
        const data = await response.json();
        if (data.success) {
            if (agregarAlCarrito(data.data.habitacion, 1)) {
                alert('Habitación agregada al carrito');
            }
        }
    } catch (error) {
        console.error('Error:', error);
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

function configurarFiltros() {
    const btnTodas = document.querySelector('.filter-btn[data-categoria="todas"]');
    if (btnTodas) {
        btnTodas.onclick = () => {
            window.location.href = 'habitaciones.html';
        };
    }
}

function filtrarPorCategoria(idCategoria) {
    window.location.href = `habitaciones.html?categoria=${idCategoria}`;
}

function actualizarBotonActivo(categoriaId) {
    const botones = document.querySelectorAll('.filter-btn');
    botones.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.categoria === String(categoriaId)) {
            btn.classList.add('active');
        }
    });
}

function configurarModal() {
    const modal = document.getElementById('modalDetalles');
    const closeBtn = document.getElementById('closeModal');
    if (closeBtn) closeBtn.onclick = cerrarModal;
    if (modal) {
        window.onclick = (e) => {
            if (e.target === modal) cerrarModal();
        };
    }
}

function cerrarModal() {
    const modal = document.getElementById('modalDetalles');
    if (modal) modal.classList.remove('active');
}

function mostrarAlerta(mensaje, tipo) {
    const alert = document.getElementById('alertMessage');
    if (!alert) return;
    alert.className = `alert alert-${tipo}`;
    alert.textContent = mensaje;
    alert.classList.remove('hidden');
}
