# Nikenza Diseño e Impresión - Aplicación Web

Una aplicación web completa con autenticación de usuarios y página promocional para Nikenza Diseño e Impresión.

## Características

- ✅ **Sistema de autenticación** con roles (Cliente/Administrador)
- ✅ **Backend PHP** con clase `pwclass` para manejo de base de datos
- ✅ **Frontend React** moderno con Tailwind CSS y shadcn/ui
- ✅ **Base de datos SQLite** integrada
- ✅ **Página promocional** con catálogo de productos
- ✅ **Dashboard diferenciado** por roles de usuario
- ✅ **Integración con WhatsApp** para contacto

## Estructura del Proyecto

```
nikenza-app/
├── php-backend/           # Backend PHP
│   ├── pwclass.php       # Clase principal para DB
│   ├── config.php        # Configuración y CORS
│   ├── auth.php          # Endpoints de autenticación
│   ├── users.php         # Gestión de usuarios
│   ├── index.php         # Archivo principal
│   └── database/         # Base de datos SQLite
├── frontend/             # Frontend React
│   ├── src/
│   │   ├── components/   # Componentes React
│   │   └── assets/       # Imágenes y recursos
│   └── dist/            # Build de producción
└── README.md
```

## Usuarios Predeterminados

### Administrador
- **Usuario:** admin
- **Contraseña:** admin123
- **Permisos:** Acceso completo al panel de administración

### Cliente
- **Usuario:** cliente
- **Contraseña:** cliente123
- **Permisos:** Acceso a página promocional y perfil

## Instalación y Ejecución

### Requisitos
- PHP 8.1+ con extensiones: sqlite3, json, mbstring
- Node.js 20+ con npm
- Navegador web moderno

### Backend PHP

1. Navegar al directorio del backend:
```bash
cd php-backend
```

2. Iniciar el servidor PHP:
```bash
php -S 0.0.0.0:8000 index.php
```

El backend estará disponible en: http://localhost:8000

### Frontend React

1. Navegar al directorio del frontend:
```bash
cd frontend
```

2. Instalar dependencias (si es necesario):
```bash
npm install
```

3. Iniciar el servidor de desarrollo:
```bash
npm run dev -- --host
```

El frontend estará disponible en: http://localhost:5173

## Endpoints de la API

### Autenticación
- `POST /auth/login` - Iniciar sesión
- `POST /auth/register` - Registrar usuario
- `POST /auth/logout` - Cerrar sesión
- `GET /auth/check-session` - Verificar sesión
- `GET /auth/me` - Obtener usuario actual

### Gestión de Usuarios (Solo Admin)
- `GET /users` - Listar usuarios
- `GET /users/{id}` - Obtener usuario por ID
- `POST /users` - Crear usuario
- `PUT /users/{id}` - Actualizar usuario
- `DELETE /users/{id}` - Eliminar usuario

## Clase pwclass

La clase `pwclass` proporciona una interfaz simplificada para operaciones de base de datos:

### Métodos Principales
- `query($sql, $params)` - Ejecutar consulta SQL
- `getAll($table, $conditions, $params)` - Obtener todos los registros
- `getById($table, $id)` - Obtener registro por ID
- `getOne($table, $conditions, $params)` - Obtener un registro
- `insert($table, $data)` - Insertar registro
- `update($table, $data, $conditions, $params)` - Actualizar registro
- `delete($table, $conditions, $params)` - Eliminar registro
- `count($table, $conditions, $params)` - Contar registros
- `exists($table, $conditions, $params)` - Verificar existencia
- `getList($table, $page, $limit, $conditions, $params, $orderBy)` - Paginación

### Ejemplo de Uso
```php
$db = new pwclass();

// Insertar usuario
$userId = $db->insert('users', [
    'username' => 'nuevo_usuario',
    'email' => 'usuario@example.com',
    'password_hash' => password_hash('password', PASSWORD_DEFAULT),
    'role' => 'cliente'
]);

// Obtener usuario
$user = $db->getById('users', $userId);

// Actualizar usuario
$db->update('users', 
    ['email' => 'nuevo_email@example.com'], 
    'id = ?', 
    [$userId]
);
```

## Funcionalidades

### Para Clientes
- Acceso a página promocional con catálogo completo
- Visualización de productos y paquetes especiales
- Contacto directo via WhatsApp
- Gestión de perfil personal

### Para Administradores
- Panel de administración completo
- Gestión de usuarios del sistema
- Acceso a estadísticas de la plataforma
- Todas las funcionalidades de cliente

## Productos Disponibles

### Productos Individuales
- **Tazas Personalizadas** - Desde $85
- **Tapetes Afelpados** - $300
- **Sudaderas** - Desde $425
- **Camisas de Uniforme** - Desde $195

### Paquetes Especiales
- **Paquete Básico** - $250
- **Paquete Viajero** - $320 (Más popular)
- **Paquete Deportivo** - $575
- **Paquete Potterhead** - $635

## Tecnologías Utilizadas

### Backend
- PHP 8.1
- SQLite
- Sesiones PHP para autenticación
- CORS habilitado

### Frontend
- React 19
- Vite
- Tailwind CSS
- shadcn/ui
- Lucide React (iconos)

## Contacto

- **WhatsApp:** 449-189-4483
- **Instagram:** @nikenzadiseno
- **Facebook:** Nikenza Mon

## Notas de Desarrollo

- La base de datos se crea automáticamente al iniciar la aplicación
- Los usuarios predeterminados se crean en el primer arranque
- El frontend se comunica con el backend via fetch API
- CORS está configurado para permitir requests desde localhost:5173

