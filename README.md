# Sistema de Gestión de Reservaciones Hoteleras

Sistema completo de gestión de reservaciones hoteleras desarrollado con PHP, MySQL, HTML, CSS y JavaScript.

## 📋 Requisitos

- XAMPP (Apache + MySQL + PHP 7.4+)
- Navegador web moderno (Chrome, Firefox, Edge, etc.)

## 🚀 Instalación

### 1. Configurar la Base de Datos

1. Inicia XAMPP y asegúrate de que Apache y MySQL estén corriendo
2. Abre phpMyAdmin en tu navegador: `http://localhost/phpmyadmin`
3. Importa el archivo `database.sql` que se encuentra en la raíz del proyecto
4. El script creará automáticamente:
   - La base de datos `playa`
   - Todas las tablas necesarias
   - Datos de ejemplo (categorías, habitaciones, usuarios)

### 2. Verificar Configuración

El archivo `config.inc.php` ya está configurado con los valores por defecto de XAMPP:
- Servidor: `localhost`
- Usuario: `root`
- Contraseña: (vacía)
- Base de datos: `playa`

Si tu configuración de MySQL es diferente, edita estos valores en `config.inc.php`.

### 3. Acceder a la Aplicación

Abre tu navegador y ve a: `http://localhost/playa2/`

## 👥 Usuarios de Prueba

El sistema incluye dos usuarios de prueba:

### Administrador
- **Usuario:** admin
- **Contraseña:** admin123
- **Acceso:** Gestión completa del catálogo de habitaciones + todas las funciones

### Huésped
- **Usuario:** huesped1
- **Contraseña:** huesped123
- **Acceso:** Reservaciones + visualización de habitaciones (sin gestión de catálogo)

## 📚 Estructura del Proyecto

```
playa2/
├── api/                    # Endpoints PHP de la API
│   ├── auth.php           # Autenticación y sesiones
│   ├── db.php             # Utilidades de base de datos
│   ├── habitaciones.php   # CRUD de habitaciones
│   ├── imagenes.php       # Upload de imágenes
│   └── reservaciones.php  # Gestión de reservaciones
├── css/
│   └── styles.css         # Estilos de la aplicación
├── js/                    # Scripts JavaScript
│   ├── auth.js            # Gestión de sesión
│   ├── carrito.js         # Carrito con cookies
│   ├── validaciones.js    # Validación de formularios
│   ├── main.js            # Página principal
│   ├── login.js           # Login
│   ├── registro.js        # Registro de usuarios
│   ├── habitaciones.js    # Catálogo con búsqueda
│   ├── admin-habitaciones.js  # Panel de administración
│   ├── carrito-page.js    # Página del carrito
│   └── mis-reservaciones.js  # Gestión de reservaciones
├── uploads/               # Directorio para imágenes subidas
├── images/                # Imágenes estáticas
├── index.html             # Página principal
├── login.html             # Página de login
├── registro.html          # Registro de usuarios
├── habitaciones.html      # Catálogo de habitaciones
├── admin-habitaciones.html  # Administración (solo admin)
├── carrito.html           # Carrito de compras
├── mis-reservaciones.html # Mis reservaciones
├── config.inc.php         # Configuración de BD
├── database.sql           # Script de base de datos
└── README.md              # Esta documentación
```

## ✨ Funcionalidades Implementadas

### A. Administración de Sesiones (4 puntos) ✅

1. **Autenticación con mensajes de error**
   - Sistema completo de login con validación
   - Mensajes claros para credenciales incorrectas
   - Validación en cliente y servidor

2. **3 Tipos de Usuarios**
   - **Administrador:** Acceso total + gestión de habitaciones
   - **Huésped:** Puede hacer reservaciones, sin acceso a administración
   - **No registrado:** Solo visualización, no puede reservar

3. **Restricción de Páginas Protegidas**
   - Las páginas verifican permisos automáticamente
   - Redirección automática si no tiene permisos
   - Control de acceso por tipo de usuario

4. **Cerrar Sesión**
   - Botón de logout en todas las páginas
   - Limpia sesión y cookies del carrito
   - Confirmación antes de cerrar sesión

### B. Administración de Catálogo de Habitaciones (4 puntos) ✅

1. **Agregar Habitaciones**
   - Formulario completo con todos los campos
   - Upload de múltiples imágenes
   - Validación de datos

2. **Editar Habitaciones**
   - Toda la información es editable excepto el ID
   - Gestión de imágenes (agregar/eliminar/establecer principal)
   - Actualización en tiempo real

3. **Eliminar Habitaciones**
   - Eliminación lógica (marca como inactivo)
   - Confirmación antes de eliminar
   - No afecta reservaciones existentes

4. **Listados Dinámicos Agrupados**
   - Habitaciones agrupadas por categoría
   - Filtros por categoría
   - Contadores de habitaciones por categoría

### C. Carrito de Reservaciones con Cookies (5 puntos) ✅

**IMPORTANTE: NO usa variables de sesión PHP para el carrito**

1. **Agregar al Carrito**
   - Sistema de cookies JavaScript puro
   - Validación de disponibilidad
   - Feedback visual inmediato

2. **Editar Cantidad**
   - Controles +/- para cambiar cantidad
   - Validación contra stock disponible
   - Actualización automática de totales

3. **Eliminar del Carrito**
   - Botón de eliminación con confirmación
   - Actualización del badge
   - Limpieza automática

4. **Costos Subtotales y Total**
   - Cálculo dinámico de subtotales por habitación
   - Impuestos (16%) calculados automáticamente
   - Total general visible siempre
   - Cálculo por número de noches

5. **Descuento de Inventario**
   - Al confirmar pago, descuenta del inventario
   - Transacciones atómicas (todo o nada)
   - Validación de disponibilidad antes de procesar

### D. Uso de JavaScript (3 puntos) ✅

1. **Validación de Formularios**
   - Validación en tiempo real
   - Mensajes de error específicos
   - Validación antes de enviar

2. **Confirmación de Acciones**
   - Confirmaciones para:
     - Eliminar habitaciones
     - Cancelar reservaciones
     - Cerrar sesión
     - Procesar pago
     - Vaciar carrito

3. **Ventanas Emergentes con Información**
   - Modal con detalles completos de habitación
   - Galería de imágenes
   - Información sintetizada y clara

### E. Búsquedas (4 puntos) ✅

- **Sistema de búsqueda completo**
  - Búsqueda por nombre, categoría, características
  - Resultados en formato de reporte
  - Contador de resultados encontrados
  - Búsqueda en tiempo real desde cualquier página

### F. Diseño de la Aplicación (10 puntos) ✅

1. **Funcionalidad:** Sistema completamente funcional con todas las características implementadas

2. **Estética:** Diseño moderno con gradientes, sombras y animaciones sutiles

3. **Cross-Browser:** Compatible con Chrome, Firefox, Edge, Safari

4. **Navegación Accesible:**
   - Menú fijo en la parte superior
   - Enlaces claros y organizados
   - Breadcrumbs visuales

5. **Menús Desplegables:**
   - Menú de categorías
   - Menú de cuenta de usuario
   - Organización jerárquica

6. **Sin Callejones sin Salida:**
   - Navegación siempre visible
   - Botones de regreso en formularios
   - Enlaces a todas las secciones

7. **Botones de Cancelación:**
   - Todos los formularios tienen botón cancelar
   - Confirmaciones cancelables
   - Navegación alternativa disponible

8. **Imágenes de Calidad:**
   - Placeholders elegantes
   - Soporte para múltiples imágenes
   - Optimización de carga

9. **Uso Apropiado de Colores:**
   - Paleta de colores profesional
   - Contraste adecuado
   - Jerarquía visual clara

10. **Idioma Correcto:**
    - Todo en español correcto
    - Sin spanglish
    - Mensajes claros y profesionales

## 🔧 Características Técnicas

### Backend (PHP)
- Arquitectura REST API
- Sesiones con `session_start()`
- Prepared statements para prevenir SQL injection
- Validación en servidor
- Manejo de errores robusto
- Upload de archivos seguro

### Frontend (JavaScript)
- JavaScript Vanilla (sin frameworks)
- Fetch API para comunicación con servidor
- Gestión de carrito con cookies JavaScript
- Validaciones en tiempo real
- Separación de archivos por funcionalidad
- Código modular y reutilizable

### Base de Datos
- MySQL con InnoDB
- Relaciones con claves foráneas
- Vistas para consultas complejas
- Índices para optimización
- Transacciones para integridad

## 📖 Guía de Uso

### Para Usuarios No Registrados
1. Explora el catálogo de habitaciones
2. Usa la búsqueda para encontrar habitaciones específicas
3. Ve detalles de habitaciones en ventanas emergentes
4. Regístrate para poder hacer reservaciones

### Para Huéspedes
1. Inicia sesión con tus credenciales
2. Navega por las habitaciones
3. Agrega habitaciones al carrito
4. Selecciona fechas de entrada/salida
5. Revisa costos y confirma reservación
6. Gestiona tus reservaciones en "Mis Reservaciones"
7. Cancela reservaciones si es necesario

### Para Administradores
1. Inicia sesión como administrador
2. Accede a "Administrar Habitaciones"
3. Agrega nuevas habitaciones con imágenes
4. Edita información de habitaciones existentes
5. Sube/elimina imágenes de habitaciones
6. Gestiona disponibilidad
7. Ve todas las reservaciones del sistema

## 🔒 Seguridad Implementada

- Contraseñas hasheadas con `password_hash()`
- Prepared statements contra SQL injection
- Sanitización de HTML para prevenir XSS
- Validación en cliente Y servidor
- Control de sesiones con timeout
- Verificación de permisos en cada endpoint
- Upload de archivos con validación de tipo y tamaño

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
- Verifica que MySQL esté corriendo en XAMPP
- Confirma que la base de datos `playa` exista
- Revisa las credenciales en `config.inc.php`

### Las imágenes no se suben
- Verifica que el directorio `uploads/habitaciones/` exista
- Asegúrate de que tenga permisos de escritura
- Confirma que los archivos sean imágenes válidas (JPG, PNG, GIF, WebP)
- Verifica el tamaño (máximo 5MB por imagen)

### El carrito no funciona
- Asegúrate de que las cookies estén habilitadas en tu navegador
- Verifica que hayas iniciado sesión como huésped
- Limpia las cookies del navegador si hay problemas

### Error al procesar reservaciones
- Verifica que haya habitaciones disponibles
- Asegúrate de que las fechas sean válidas
- Confirma que el carrito no esté vacío

## 📞 Soporte

Para dudas o problemas, revisa:
1. Este README completo
2. Los comentarios en el código fuente
3. La consola del navegador para errores JavaScript
4. Los logs de PHP en XAMPP

## 📝 Notas Adicionales

- El sistema usa transacciones para garantizar la integridad de las reservaciones
- El carrito se limpia automáticamente al cerrar sesión
- Las fechas mínimas de reservación son el día actual
- Los impuestos se calculan automáticamente (16%)
- Las habitaciones eliminadas se marcan como inactivas, no se borran físicamente

## ✅ Checklist de Funcionalidades

- [x] A1. Autenticación con mensajes de error
- [x] A2. 3 tipos de usuarios (admin, huésped, no registrado)
- [x] A3. Restricción de páginas protegidas
- [x] A4. Cerrar sesión
- [x] B1. Agregar habitaciones con imágenes
- [x] B2. Editar toda la información (excepto ID)
- [x] B3. Eliminar habitaciones
- [x] B4. Listados dinámicos agrupados por categoría
- [x] C1. Agregar al carrito (con cookies JavaScript)
- [x] C2. Editar cantidad en carrito
- [x] C3. Eliminar del carrito
- [x] C4. Mostrar subtotales y total
- [x] C5. Descontar inventario al pagar
- [x] D1. Validar formularios con JavaScript
- [x] D2. Confirmar acciones
- [x] D3. Ventanas emergentes con información
- [x] E1. Búsqueda con reportes
- [x] F1-F10. Todos los aspectos de diseño

---

**Desarrollado para el curso de Desarrollo Web**

**Versión:** 1.0
**Fecha:** 2024
