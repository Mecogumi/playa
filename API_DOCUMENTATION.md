# API Documentation - Hotel Playa

## Base URL
```
/api/
```

## Índice
- [Autenticación](#autenticación)
- [Habitaciones](#habitaciones)
- [Imágenes](#imágenes)
- [Reservaciones](#reservaciones)
- [Usuarios](#usuarios)
- [Códigos de Respuesta](#códigos-de-respuesta)

---

## Autenticación

### POST `/auth.php?accion=login`
Inicia sesión de usuario.

**Body:**
```json
{
  "correo": "string",
  "contrasena": "string"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "usuario": {
      "id_usuario": 1,
      "nombre": "Juan Pérez",
      "correo": "juan@example.com",
      "tipo_usuario": "admin"
    }
  }
}
```

---

### GET `/auth.php?accion=logout`
Cierra la sesión actual.

**Respuesta:**
```json
{
  "success": true,
  "message": "Sesión cerrada exitosamente"
}
```

---

### GET `/auth.php?accion=verificar`
Verifica si hay una sesión activa.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "sesion_activa": true,
    "usuario": {
      "id_usuario": 1,
      "nombre": "Juan Pérez",
      "correo": "juan@example.com",
      "tipo_usuario": "admin"
    }
  }
}
```

---

### POST `/auth.php?accion=registro`
Registra un nuevo usuario (huésped).

**Body:**
```json
{
  "nombre": "string",
  "correo": "string",
  "contrasena": "string",
  "telefono": "string (opcional)",
  "direccion": "string (opcional)"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario registrado exitosamente",
  "data": {
    "id_usuario": 2
  }
}
```

---

## Habitaciones

### GET `/habitaciones.php?accion=listar`
Lista todas las habitaciones disponibles.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "habitaciones": [
      {
        "id_habitacion": 1,
        "numero_habitacion": "101",
        "nombre": "Suite Deluxe",
        "descripcion": "Habitación con vista al mar",
        "capacidad_personas": 2,
        "precio_noche": 150.00,
        "cantidad_disponible": 5,
        "nombre_categoria": "Deluxe",
        "imagen_principal": "images/habitacion1.jpg"
      }
    ]
  }
}
```

---

### GET `/habitaciones.php?accion=obtener&id={id}`
Obtiene detalles completos de una habitación específica.

**Parámetros URL:**
- `id` (integer, requerido): ID de la habitación

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "habitacion": {
      "id_habitacion": 1,
      "numero_habitacion": "101",
      "nombre": "Suite Deluxe",
      "descripcion": "Habitación con vista al mar",
      "capacidad_personas": 2,
      "precio_noche": 150.00,
      "cantidad_disponible": 5,
      "caracteristicas": "WiFi, TV, Aire acondicionado",
      "nombre_categoria": "Deluxe",
      "imagen_principal": "images/habitacion1.jpg",
      "imagenes": [
        {
          "id_imagen": 1,
          "ruta_archivo": "images/habitacion1.jpg",
          "es_principal": 1
        },
        {
          "id_imagen": 2,
          "ruta_archivo": "images/habitacion1_2.jpg",
          "es_principal": 0
        }
      ]
    }
  }
}
```

---

### POST `/habitaciones.php?accion=crear` 🔒 Admin
Crea una nueva habitación.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Body:**
```json
{
  "numero_habitacion": "101",
  "nombre": "Suite Deluxe",
  "id_categoria": 2,
  "descripcion": "Habitación con vista al mar",
  "capacidad_personas": 2,
  "precio_noche": 150.00,
  "cantidad_disponible": 5,
  "caracteristicas": "WiFi, TV, Aire acondicionado"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Habitación creada exitosamente",
  "data": {
    "id_habitacion": 10
  }
}
```

---

### POST `/habitaciones.php?accion=actualizar` 🔒 Admin
Actualiza una habitación existente.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Body:**
```json
{
  "id_habitacion": 1,
  "numero_habitacion": "101",
  "nombre": "Suite Deluxe Premium",
  "id_categoria": 2,
  "descripcion": "Habitación renovada con vista al mar",
  "capacidad_personas": 2,
  "precio_noche": 180.00,
  "cantidad_disponible": 5,
  "caracteristicas": "WiFi, TV 4K, Aire acondicionado"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Habitación actualizada exitosamente"
}
```

---

### DELETE `/habitaciones.php?accion=eliminar&id={id}` 🔒 Admin
Elimina una habitación.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Parámetros URL:**
- `id` (integer, requerido): ID de la habitación

**Respuesta:**
```json
{
  "success": true,
  "message": "Habitación eliminada exitosamente"
}
```

---

### GET `/habitaciones.php?accion=por_categoria&id_categoria={id}`
Lista habitaciones por categoría.

**Parámetros URL:**
- `id_categoria` (integer, requerido): ID de la categoría

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "habitaciones": [
      {
        "id_habitacion": 1,
        "numero_habitacion": "101",
        "nombre": "Suite Deluxe",
        "descripcion": "Habitación con vista al mar",
        "capacidad_personas": 2,
        "precio_noche": 150.00,
        "cantidad_disponible": 5,
        "nombre_categoria": "Deluxe",
        "imagen_principal": "images/habitacion1.jpg"
      }
    ]
  }
}
```

---

### GET `/habitaciones.php?accion=categorias`
Lista todas las categorías de habitaciones.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "categorias": [
      {
        "id_categoria": 1,
        "nombre_categoria": "Estándar",
        "descripcion": "Habitaciones básicas y cómodas"
      },
      {
        "id_categoria": 2,
        "nombre_categoria": "Deluxe",
        "descripcion": "Habitaciones premium con amenidades extras"
      },
      {
        "id_categoria": 3,
        "nombre_categoria": "Suite",
        "descripcion": "Suites amplias con sala de estar"
      },
      {
        "id_categoria": 4,
        "nombre_categoria": "Presidencial",
        "descripcion": "La mejor experiencia de lujo"
      }
    ]
  }
}
```

---

### GET `/habitaciones.php?accion=buscar&termino={termino}`
Busca habitaciones por término.

**Parámetros URL:**
- `termino` (string, requerido): Texto a buscar en nombre, descripción o características

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "habitaciones": [
      {
        "id_habitacion": 1,
        "numero_habitacion": "101",
        "nombre": "Suite Deluxe",
        "descripcion": "Habitación con vista al mar",
        "capacidad_personas": 2,
        "precio_noche": 150.00,
        "cantidad_disponible": 5,
        "nombre_categoria": "Deluxe",
        "imagen_principal": "images/habitacion1.jpg"
      }
    ],
    "total_resultados": 1
  }
}
```

---

## Imágenes

**Nota:** Todos los endpoints de imágenes requieren autenticación de administrador.

### POST `/imagenes.php?accion=subir` 🔒 Admin
Sube imágenes para una habitación.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Content-Type:** `multipart/form-data`

**Form Data:**
- `id_habitacion` (integer, requerido): ID de la habitación
- `imagenes[]` (file[], requerido): Archivos de imagen (máximo 5MB cada uno)

**Respuesta:**
```json
{
  "success": true,
  "message": "Imágenes subidas exitosamente",
  "data": {
    "imagenes_subidas": 3
  }
}
```

---

### POST `/imagenes.php?accion=eliminar&id={id}` 🔒 Admin
Elimina una imagen.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Parámetros URL:**
- `id` (integer, requerido): ID de la imagen

**Respuesta:**
```json
{
  "success": true,
  "message": "Imagen eliminada exitosamente"
}
```

---

### POST `/imagenes.php?accion=principal` 🔒 Admin
Establece una imagen como principal para una habitación.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Body:**
```json
{
  "id_imagen": 5,
  "id_habitacion": 1
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Imagen principal establecida"
}
```

---

## Reservaciones

**Nota:** Todos los endpoints de reservaciones requieren autenticación (huésped o admin).

### POST `/reservaciones.php?accion=crear` 🔒
Crea una nueva reservación (puede incluir múltiples habitaciones).

**Requiere:** Sesión activa

**Body:**
```json
{
  "reservaciones": [
    {
      "id_habitacion": 1,
      "cantidad": 2,
      "fecha_checkin": "2024-12-25",
      "fecha_checkout": "2024-12-30"
    },
    {
      "id_habitacion": 3,
      "cantidad": 1,
      "fecha_checkin": "2024-12-25",
      "fecha_checkout": "2024-12-30"
    }
  ]
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Reservaciones creadas exitosamente",
  "data": {
    "reservaciones_creadas": 2,
    "ids_reservaciones": [15, 16]
  }
}
```

---

### GET `/reservaciones.php?accion=listar` 🔒
Lista reservaciones.
- Admin: ve todas las reservaciones
- Huésped: ve solo sus propias reservaciones

**Requiere:** Sesión activa

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "reservaciones": [
      {
        "id_reservacion": 1,
        "nombre_huesped": "Juan Pérez",
        "correo_huesped": "juan@example.com",
        "numero_habitacion": "101",
        "nombre_habitacion": "Suite Deluxe",
        "cantidad": 2,
        "fecha_checkin": "2024-12-25",
        "fecha_checkout": "2024-12-30",
        "precio_noche": 150.00,
        "precio_total": 1500.00,
        "estado": "confirmada",
        "fecha_reservacion": "2024-12-01 10:30:00"
      }
    ]
  }
}
```

---

### GET `/reservaciones.php?accion=mis_reservaciones` 🔒
Lista las reservaciones del usuario autenticado.

**Requiere:** Sesión activa

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "reservaciones": [
      {
        "id_reservacion": 1,
        "numero_habitacion": "101",
        "nombre_habitacion": "Suite Deluxe",
        "cantidad": 2,
        "fecha_checkin": "2024-12-25",
        "fecha_checkout": "2024-12-30",
        "precio_noche": 150.00,
        "precio_total": 1500.00,
        "estado": "confirmada",
        "fecha_reservacion": "2024-12-01 10:30:00"
      }
    ]
  }
}
```

---

### GET `/reservaciones.php?accion=obtener&id={id}` 🔒
Obtiene detalles de una reservación específica.

**Requiere:** Sesión activa

**Parámetros URL:**
- `id` (integer, requerido): ID de la reservación

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "reservacion": {
      "id_reservacion": 1,
      "nombre_huesped": "Juan Pérez",
      "correo_huesped": "juan@example.com",
      "telefono_huesped": "555-1234",
      "numero_habitacion": "101",
      "nombre_habitacion": "Suite Deluxe",
      "cantidad": 2,
      "fecha_checkin": "2024-12-25",
      "fecha_checkout": "2024-12-30",
      "precio_noche": 150.00,
      "precio_total": 1500.00,
      "estado": "confirmada",
      "fecha_reservacion": "2024-12-01 10:30:00"
    }
  }
}
```

---

### POST `/reservaciones.php?accion=cancelar&id={id}` 🔒
Cancela una reservación.

**Requiere:** Sesión activa

**Parámetros URL:**
- `id` (integer, requerido): ID de la reservación

**Respuesta:**
```json
{
  "success": true,
  "message": "Reservación cancelada exitosamente"
}
```

---

### POST `/reservaciones.php?accion=verificar_disponibilidad`
Verifica la disponibilidad de habitaciones para fechas específicas.

**Body:**
```json
{
  "habitaciones": [
    {
      "id_habitacion": 1,
      "cantidad": 2,
      "fecha_checkin": "2024-12-25",
      "fecha_checkout": "2024-12-30"
    },
    {
      "id_habitacion": 3,
      "cantidad": 1,
      "fecha_checkin": "2024-12-25",
      "fecha_checkout": "2024-12-30"
    }
  ]
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "disponibilidad": [
      {
        "id_habitacion": 1,
        "disponible": true,
        "cantidad_disponible": 5,
        "cantidad_solicitada": 2
      },
      {
        "id_habitacion": 3,
        "disponible": false,
        "cantidad_disponible": 0,
        "cantidad_solicitada": 1
      }
    ],
    "todas_disponibles": false
  }
}
```

---

## Usuarios

**Nota:** Todos los endpoints de usuarios requieren autenticación de administrador.

### GET `/usuarios.php?accion=listar` 🔒 Admin
Lista todos los usuarios del sistema.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "usuarios": [
      {
        "id_usuario": 1,
        "nombre": "Juan Pérez",
        "correo": "juan@example.com",
        "telefono": "555-1234",
        "direccion": "Calle Principal 123",
        "tipo_usuario": "admin",
        "estado": "activo",
        "fecha_registro": "2024-01-15 10:30:00"
      },
      {
        "id_usuario": 2,
        "nombre": "María García",
        "correo": "maria@example.com",
        "telefono": "555-5678",
        "direccion": "Avenida Central 456",
        "tipo_usuario": "huesped",
        "estado": "activo",
        "fecha_registro": "2024-02-20 14:15:00"
      }
    ]
  }
}
```

---

### GET `/usuarios.php?accion=obtener&id={id}` 🔒 Admin
Obtiene detalles de un usuario específico.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Parámetros URL:**
- `id` (integer, requerido): ID del usuario

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "usuario": {
      "id_usuario": 2,
      "nombre": "María García",
      "correo": "maria@example.com",
      "telefono": "555-5678",
      "direccion": "Avenida Central 456",
      "tipo_usuario": "huesped",
      "estado": "activo",
      "fecha_registro": "2024-02-20 14:15:00"
    }
  }
}
```

---

### POST `/usuarios.php?accion=crear` 🔒 Admin
Crea un nuevo usuario.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Body:**
```json
{
  "nombre": "María García",
  "correo": "maria@example.com",
  "contrasena": "password123",
  "telefono": "555-5678",
  "direccion": "Avenida Central 456",
  "tipo_usuario": "huesped"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario creado exitosamente",
  "data": {
    "id_usuario": 10
  }
}
```

---

### POST `/usuarios.php?accion=actualizar` 🔒 Admin
Actualiza un usuario existente.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Body:**
```json
{
  "id_usuario": 2,
  "nombre": "María García López",
  "correo": "maria.garcia@example.com",
  "telefono": "555-9999",
  "direccion": "Nueva Calle 789",
  "tipo_usuario": "huesped"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario actualizado exitosamente"
}
```

---

### DELETE `/usuarios.php?accion=eliminar&id={id}` 🔒 Admin
Elimina un usuario.

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Parámetros URL:**
- `id` (integer, requerido): ID del usuario

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario eliminado exitosamente"
}
```

---

### POST `/usuarios.php?accion=cambiar_estado` 🔒 Admin
Cambia el estado de un usuario (activo/inactivo).

**Requiere:** Sesión activa con tipo_usuario = 'admin'

**Body:**
```json
{
  "id_usuario": 2,
  "estado": "inactivo"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Estado del usuario actualizado"
}
```

---

## Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| `200` | Operación exitosa |
| `400` | Solicitud inválida (parámetros faltantes o incorrectos) |
| `401` | No autenticado (sesión no iniciada) |
| `403` | Sin permisos (requiere rol de administrador) |
| `404` | Recurso no encontrado |
| `405` | Método HTTP no permitido |
| `500` | Error interno del servidor |

---

## Estructura de Respuesta de Error

Todas las respuestas de error siguen esta estructura:

```json
{
  "success": false,
  "error": "Descripción del error"
}
```

**Ejemplos:**

```json
{
  "success": false,
  "error": "No hay sesión activa"
}
```

```json
{
  "success": false,
  "error": "No tienes permisos para realizar esta acción"
}
```

```json
{
  "success": false,
  "error": "Habitación no encontrada"
}
```

---

## Autenticación y Sesiones

El sistema utiliza sesiones PHP para la autenticación. Una vez que el usuario inicia sesión mediante `/auth.php?accion=login`, se crea una sesión que persiste en el servidor.

### Variables de Sesión

Después de un login exitoso, se establecen las siguientes variables de sesión:

```php
$_SESSION['sesion_iniciada'] = true;
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nombre'] = "Juan Pérez";
$_SESSION['usuario_correo'] = "juan@example.com";
$_SESSION['usuario_tipo'] = "admin"; // o "huesped"
```

### Permisos

- **Público:** Endpoints de habitaciones (listar, obtener, buscar, categorías)
- **Autenticado:** Endpoints de reservaciones
- **Admin:** Endpoints de usuarios, imágenes, y CRUD de habitaciones

---

## Notas Adicionales

### Formatos de Fecha

- **Fecha:** `YYYY-MM-DD` (ejemplo: `2024-12-25`)
- **Fecha y Hora:** `YYYY-MM-DD HH:MM:SS` (ejemplo: `2024-12-25 14:30:00`)

### Tipos de Usuario

- `admin`: Administrador del sistema
- `huesped`: Cliente/huésped del hotel

### Estados de Reservación

- `pendiente`: Reservación creada, esperando confirmación
- `confirmada`: Reservación confirmada
- `completada`: Estadía completada
- `cancelada`: Reservación cancelada

### Estados de Usuario

- `activo`: Usuario activo en el sistema
- `inactivo`: Usuario desactivado

---

## Ejemplos de Uso

### Ejemplo 1: Buscar habitaciones disponibles

```javascript
// Buscar habitaciones con "vista"
fetch('api/habitaciones.php?accion=buscar&termino=vista')
  .then(response => response.json())
  .then(data => {
    console.log(data.data.habitaciones);
  });
```

### Ejemplo 2: Crear una reservación

```javascript
// Login primero
fetch('api/auth.php?accion=login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    correo: 'usuario@example.com',
    contrasena: 'password123'
  })
})
.then(response => response.json())
.then(data => {
  // Ahora crear la reservación
  return fetch('api/reservaciones.php?accion=crear', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      reservaciones: [
        {
          id_habitacion: 1,
          cantidad: 1,
          fecha_checkin: '2024-12-25',
          fecha_checkout: '2024-12-30'
        }
      ]
    })
  });
})
.then(response => response.json())
.then(data => {
  console.log('Reservación creada:', data);
});
```

### Ejemplo 3: Verificar disponibilidad antes de reservar

```javascript
fetch('api/reservaciones.php?accion=verificar_disponibilidad', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    habitaciones: [
      {
        id_habitacion: 1,
        cantidad: 2,
        fecha_checkin: '2024-12-25',
        fecha_checkout: '2024-12-30'
      }
    ]
  })
})
.then(response => response.json())
.then(data => {
  if (data.data.todas_disponibles) {
    console.log('Todas las habitaciones están disponibles');
  } else {
    console.log('Algunas habitaciones no están disponibles');
  }
});
```

---

## Changelog

### Versión 1.0 (2024)
- Implementación inicial de la API
- Endpoints de autenticación
- CRUD completo de habitaciones
- Sistema de reservaciones
- Gestión de usuarios
- Sistema de imágenes para habitaciones
