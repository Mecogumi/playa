# Solución de Problemas de Permisos para Subida de Imágenes

## Error Común
```
Warning: move_uploaded_file(): failed to open stream: Permission denied
```

Este error ocurre cuando el servidor web (Apache/XAMPP) no tiene permisos para escribir en la carpeta `uploads/habitaciones/`.

## Soluciones por Sistema Operativo

### Windows (XAMPP)
1. Hacer clic derecho en la carpeta `uploads` → Propiedades
2. Ir a la pestaña "Seguridad"
3. Dar permisos de "Control total" al usuario que ejecuta Apache (generalmente tu usuario actual)
4. Marcar "Aplicar a subcarpetas y archivos"

**Alternativamente, desde PowerShell (como Administrador):**
```powershell
cd C:\xampp\htdocs\playa
icacls uploads /grant Everyone:(OI)(CI)F /T
```

### macOS (XAMPP)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/playa
sudo chmod -R 777 uploads/
sudo chown -R daemon:daemon uploads/
```

### Linux
```bash
cd /var/www/html/playa  # o la ruta donde esté tu proyecto
sudo chmod -R 777 uploads/
sudo chown -R www-data:www-data uploads/
```

## Verificar que Funcionó

1. Ir al panel de administración de habitaciones
2. Editar una habitación
3. Intentar subir una imagen
4. Si aún no funciona, verifica que la carpeta `uploads/habitaciones/` exista y tenga los permisos correctos

## Alternativa: Crear Manualmente la Carpeta

Si el sistema no puede crear la carpeta automáticamente:

**Windows:**
```cmd
cd C:\xampp\htdocs\playa
mkdir uploads\habitaciones
```

**macOS/Linux:**
```bash
cd /ruta/a/playa
mkdir -p uploads/habitaciones
chmod 777 uploads/habitaciones
```

## Nota de Seguridad

⚠️ Los permisos `777` son adecuados para desarrollo local, pero en producción deberías usar permisos más restrictivos como `755` o `775` y asegurarte de que el usuario del servidor web sea el dueño de la carpeta.
