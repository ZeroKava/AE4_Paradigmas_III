# El Emporio Brillante — Sistema Web SPA (AE4 / TIFAS)

**Actividad de Evaluación N.° 4 (Trabajo Integrador Final de Asignatura)**  
**Cátedra:** Paradigmas y Lenguajes de Programación III  
**Carrera:** Ingeniería en Sistemas de Información — Universidad de la Cuenca del Plata (UCP)  
**Integrantes:** Luciano & Matías  

---

## 1. Descripción del Proyecto y Trazabilidad (AE1 → AE4)

**El Emporio Brillante** es una aplicación web integral para la comercialización de impresoras y servicios técnicos asociados. El sistema evoluciona directamente desde el prototipo estático maquetado en la **AE1**, transformándose en una **Single Page Application (SPA)** dinámica, accesible y segura bajo una arquitectura **MVC Desacoplada**.

### Evolución de Casos de Uso
* **CU-01 Consultar Catálogo y Detalle de Producto:** Visualización dinámica de impresoras disponibles desde MySQL sin recargas de página (`#catalogo` y `#producto/{id}`).
* **CU-02 Registrar Compra con Cupón de Descuento:** Selección de múltiples productos/servicios, validación de cupón promocional en servidor (`UCP10`) y cálculo seguro del total mediante transacciones SQL (`#comprar`).
* **CU-03 Enviar Consulta de Contacto:** Registro persistente de consultas comerciales o técnicas con validación en cliente y servidor (`#contacto`).
* **CU-04 Responder Encuesta de Satisfacción:** Captura de métricas de satisfacción (1 a 5), recomendación y comentarios (`#encuesta`).
* **CU-05 Autenticación y Administración de Catálogo (ABMC):** Inicio/cierre de sesión seguro para administradores y gestión completa (Alta, Baja, Modificación y Consulta) de productos y servicios (`#admin`).

---

## 2. Arquitectura del Proyecto (MVC Desacoplado)

El cliente (HTML5/CSS3/JS Vanilla) y el servidor (API RESTful en PHP 8 + PDO + MySQL) se comunican exclusivamente de forma asíncrona mediante `fetch()` e intercambio de estructuras **JSON**.

```text
/AE4_Paradigmas_III
├── /api                        # Controladores RESTful (Endpoints JSON y códigos HTTP semánticos)
│   ├── compras.php             # GET (validar cupón) / POST (registrar compra transaccional)
│   ├── contacto.php            # POST (registrar consulta)
│   ├── encuesta.php            # POST (registrar encuesta de satisfacción)
│   ├── login.php               # GET (estado sesión) / POST (login) / DELETE (logout)
│   └── productos.php           # GET (público) / POST, PUT, DELETE (protegido por sesión)
├── /config                     # Configuración transversal del servidor
│   ├── conexion.php            # Patrón Singleton para conexión PDO a MySQL
│   └── http.php                # Cabeceras JSON, cookies HttpOnly, sanitización y manejo de errores
├── /models                     # Capa de Acceso a Datos (PDO Prepared Statements)
│   ├── CompraModel.php         # Lógica transaccional (beginTransaction/commit/rollBack)
│   ├── MensajeModel.php        # Persistencia de consultas y encuestas
│   ├── ProductoModel.php       # Operaciones CRUD sobre la tabla productos
│   └── UsuarioModel.php        # Búsqueda de credenciales de administrador
├── /public                     # Vista SPA (Evolución de maqueta AE1 + WCAG 2.1)
│   ├── /css
│   │   └── estilos.css         # Diseño responsive Pip-Boy, alto contraste y :focus-visible
│   ├── /fuentes                # Tipografías personalizadas (Monofonto y Overseer)
│   ├── /imagenes               # Recursos gráficos del catálogo
│   ├── /js
│   │   └── app.js              # Router por hash, cliente fetch async/await y DOM seguro
│   └── index.html              # Documento HTML5 semántico único con regiones aria-live
├── /sql
│   └── database.sql            # Script DDL/DML para crear la base de datos y datos iniciales
└── README.md                   # Documentación técnica y guía de instalación
```

---

## 3. Estándares de Seguridad y Calidad Implementados

### Seguridad Web (OWASP Top 10)
* **A01 — Control de Acceso:** Los métodos de escritura (`POST`, `PUT`, `DELETE`) en `/api/productos.php` invocan `requerirSesion()`, cortando peticiones anónimas con `401 Unauthorized`.
* **A02 — Fallas Criptográficas:** Contraseñas almacenadas con hash **BCRYPT** (`VARCHAR(255)`) y verificadas mediante `password_verify()`.
* **A03 — Inyección (SQLi y XSS):**
  * **Backend:** 100% de consultas SQL parametrizadas con **PDO Prepared Statements** (`:marcadores`) y `ATTR_EMULATE_PREPARES => false`.
  * **Frontend:** Inserción exclusiva de datos dinámicos mediante `.textContent` y nodos DOM (`document.createElement`), erradicando `.innerHTML`.
* **A07 — Gestión de Sesiones:** Cookies configuradas con `httponly => true` y `samesite => 'Lax'`, ejecutando `session_regenerate_id(true)` tras cada inicio de sesión exitoso.

### Calidad y Accesibilidad (ISO/IEC 25010 y WCAG 2.1)
* **Auditoría Google Lighthouse:** **100 / 100** en Accesibilidad.
* **Operabilidad 100% por Teclado (`Tab` / `Enter`):** Navegación semántica nativa (`<a>`, `<button>`), gestión de foco al cambiar de vista (`h2[tabindex="-1"]`) e indicadores visuales explícitos mediante `:focus-visible`.
* **Comunicación Asíncrona Accesible:** Contenedores de estado y tablas con atributo `aria-live="polite"`.

---

## 4. Guía de Instalación y Despliegue en XAMPP

### Requisitos Previos
* **XAMPP** (Apache + MariaDB/MySQL + PHP 8.0 o superior).
* Navegador web moderno (Google Chrome / Edge / Firefox).

### Paso 1: Clonar o ubicar el proyecto
1. Iniciá los módulos **Apache** y **MySQL** desde el Panel de Control de XAMPP.
2. Cloná este repositorio dentro de la carpeta `htdocs` de XAMPP:
   ```bash
   cd C:\xampp\htdocs
   git clone <URL_DEL_REPOSITORIO> AE4_Paradigmas_III
   ```

### Paso 2: Crear la Base de Datos
1. Abrí **phpMyAdmin** en tu navegador: `http://localhost/phpmyadmin`
2. Dirigite a la pestaña **Importar** (o **SQL**).
3. Seleccioná el archivo `sql/database.sql` ubicado en la raíz del proyecto y ejecutalo.
4. Verificá que se haya creado la base de datos `emporio_brillante` con sus 7 tablas (`usuarios`, `productos`, `cupones`, `compras`, `compra_items`, `consultas`, `encuestas`) y los registros iniciales.

*(Nota: Si tu usuario de MySQL en XAMPP tiene contraseña, ajustá los parámetros en `config/conexion.php`).*

### Paso 3: Ejecutar la Aplicación
Abrí en el navegador la siguiente dirección:
```text
http://localhost/AE4_Paradigmas_III/public/index.html
```

---

## 5. Datos de Prueba para Evaluación

* **Acceso al Panel de Administración (`#admin`):**
  * **Usuario:** `admin`
  * **Contraseña:** `Admin2026!`
* **Cupón de Descuento en Compras (`#comprar`):**
  * **Código:** `UCP10` (aplica un **10%** de descuento validado en servidor).

---

## 6. Inventario de Endpoints API RESTful

| Recurso | Método | Descripción | Acceso | Códigos HTTP |
| :--- | :---: | :--- | :---: | :--- |
| `/api/productos.php` | `GET` | Lista productos (admite `?tipo=impresora` o `?id={id}`) | Público | `200`, `400`, `404`, `500` |
| `/api/productos.php` | `POST` | Crea un nuevo producto o servicio | Admin (`$_SESSION`) | `201`, `400`, `401`, `500` |
| `/api/productos.php?id={id}` | `PUT` | Actualiza un producto existente | Admin (`$_SESSION`) | `200`, `400`, `401`, `404`, `500` |
| `/api/productos.php?id={id}` | `DELETE` | Elimina un producto sin compras asociadas | Admin (`$_SESSION`) | `200`, `400`, `401`, `404`, `500` |
| `/api/compras.php?cupon={cod}` | `GET` | Valida código de descuento en MySQL | Público | `200`, `404`, `500` |
| `/api/compras.php` | `POST` | Registra una orden de compra con cálculo en servidor | Público | `201`, `400`, `500` |
| `/api/contacto.php` | `POST` | Guarda una consulta de cliente | Público | `201`, `400`, `500` |
| `/api/encuesta.php` | `POST` | Guarda una respuesta de satisfacción | Público | `201`, `400`, `500` |
| `/api/login.php` | `GET` / `POST` / `DELETE` | Verifica sesión, autentica con BCRYPT o cierra sesión | Público | `200`, `400`, `401`, `500` |