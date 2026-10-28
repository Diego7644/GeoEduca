# 🌍 GEOEDUCA - Sistema de Control Escolar y Asistencia

GEOEDUCA es una plataforma web moderna diseñada para la gestión de asistencia escolar en tiempo real. Utiliza geolocalización (GPS), códigos QR dinámicos y paneles especializados para optimizar el control de acceso en las instituciones educativas.

Este repositorio contiene el código fuente de la aplicación, dividida en un **Backend (API REST)** construido con Laravel y un **Frontend (SPA)** desarrollado con React y Vite.

---

## 🚀 Tecnologías Principales

### Frontend
*   **React 18** (con Vite como empaquetador)
*   **React Router DOM** (Manejo de rutas protegidas por roles)
*   **Tailwind CSS** (Diseño responsivo y sistema de diseño personalizado)
*   **Axios** (Cliente HTTP con interceptores para manejo de tokens)

### Backend
*   **Laravel** (API REST)
*   **PHP 8+**
*   **MySQL / PostgreSQL** (Base de datos relacional)

---

## 👥 Roles del Sistema

La aplicación cuenta con un sistema de autenticación basado en roles. Cada usuario es redirigido a un dashboard específico al iniciar sesión:

1.  **Estudiante (`student`)**: Genera un token QR dinámico (rotación de 60s) para marcar su asistencia, visualiza su horario y su racha de puntualidad.
2.  **Padre / Tutor (`parent`)**: Monitorea la asistencia de sus hijos en tiempo real, recibe alertas de entrada/salida, y puede enviar justificaciones médicas o permisos de salida anticipada.
3.  **Auxiliar / Staff (`staff`)**: Consola operativa para gestionar contingencias en la puerta (fallas de GPS, QR expirados, falta de batería). Permite el registro forzado de ingreso y mantiene una auditoría en vivo.

---

## ⚙️ Requisitos Previos

Asegúrate de tener instalado en tu entorno local:
*   [Node.js](https://nodejs.org/) (v18 o superior)
*   [PHP](https://www.php.net/) (v8.1 o superior)
*   [Composer](https://getcomposer.org/)
*   Un gestor de base de datos (MySQL, MariaDB o SQLite)

---

## 🛠️ Instalación y Ejecución Local

El proyecto está dividido en dos carpetas principales: `backend` y `frontend`. Debes iniciar ambos servidores para que la aplicación funcione correctamente.

### 1. Configurar el Backend (Laravel)

Abre una terminal y navega a la carpeta del backend:

```bash
cd backend

# Instalar dependencias de PHP
composer install

# Copiar el archivo de entorno y generar la clave de la aplicación
cp .env.example .env
php artisan key:generate

# Configurar la base de datos en el archivo .env y luego ejecutar las migraciones
php artisan migrate

# Iniciar el servidor de desarrollo (se ejecutará en http://127.0.0.1:8000)
php artisan serve
