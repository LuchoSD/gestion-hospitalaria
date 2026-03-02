Global Care Digital - TSS
Sistema de Gestion Hospitalaria

Este Proyecto es un app web integral diseñada para la gestión de un centro medico, desarrollada como parte de mis practicas en programación web.

Implementación de Seguridad:

El sistema ha sido desarrollado siguiendo principios de codificación segura para mitigar las vulnerabilidades más comunes:
Control de Acceso basado en Roles (RBAC): Gestión granular de permisos para Administradores, Médicos, Recepcionistas y Pacientes.
Prevención de Inyecciones SQL: Uso estricto de sentencias preparadas (PDO) en todas las interacciones con la base de datos.
Protección de Credenciales: Implementación de hashing para el almacenamiento seguro de contraseñas.
Validación y Saneamiento: Doble capa de validación de inputs tanto en el lado del cliente (JavaScript) como en el servidor (PHP) para prevenir ataques XSS.
Gestión de Sesiones: Implementación de sesiones seguras para prevenir el secuestro de las mismas.

Stack Tecnico:
Backend: PHP.
Base de Datos: MySQL (Diseño relacional optimizado).
Frontend: JavaScript (lógica dinámica), HTML5 y CSS3 (diseño responsivo).

Instalación y Configuración
1. Base de Datos: Importar el archivo gestion_pacientes.sql a través de phpMyAdmin o la línea de comandos de MySQL.
2. Configuración: Ajustar las credenciales de conexión en el archivo de configuración correspondiente.

Credenciales e prueba:

Administrador:
	User: admin@hospital.com
	Pass: Admin123456789.
Recepcionista:
	User: recepcionista@hospital.com
	Pass: Admin123456789.
Paciente:
	User: paciente@hospital.com
	Pass: Admin123456789.
Medico:
	User: medico@hospital.com
	Pass: Admin123456789.
