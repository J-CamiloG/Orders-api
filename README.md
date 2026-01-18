<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Laravel API – Gestión de Órdenes

El objetivo de esta API es gestionar órdenes de manera eficiente y escalable.  
Permite:
- Procesamiento masivo de órdenes desde archivos CSV o JSON.
- Validación de datos y persistencia en PostgreSQL.
- Procesamiento asíncrono mediante Jobs y colas.
- Comunicación con un servicio externo NestJS para procesamiento especializado.
- Cacheo de listados y estados de órdenes usando Redis.

### Tecnologías principales

<details>
<summary>Laravel 12</summary>

- Framework PHP utilizado como **API principal y orquestador**.
- Maneja las rutas, controllers, services y repositories.
- Facilita la integración con Jobs y colas para procesamiento asíncrono.
- [Documentación oficial Laravel](https://laravel.com)
</details>

<details>
<summary>PostgreSQL</summary>

- Base de datos relacional utilizada para **persistencia de órdenes**.
- Soporta transacciones, índices y relaciones entre tablas.
- Optimizada para consultas de listados y detalle de órdenes.
- Ideal para escalar en volumen de datos.
</details>

<details>
<summary>Redis</summary>

- Sistema de cache en memoria para **listados y estados de órdenes**.
- Mejora la velocidad de respuesta de la API.
- Se utiliza para cachear consultas frecuentes y se invalida cuando cambia el estado de una orden.
- Permite integración fácil con Jobs y colas.
</details>

<details>
<summary>Jobs y colas</summary>

- Manejan el **procesamiento asíncrono de órdenes**.
- Permiten separar la recepción de las órdenes de su procesamiento pesado.
- Garantizan que la API responda rápidamente al usuario mientras las tareas se ejecutan en segundo plano.
- Se pueden configurar distintos “workers” y prioridades.
</details>

<details>
<summary>HTTP REST</summary>

- Comunicación con el **servicio externo NestJS** mediante peticiones HTTP.
- Uso de endpoints bien definidos para enviar órdenes y consultar estados.
- Facilita integración con otros sistemas o microservicios.
- Basado en estándares REST para mantener consistencia y escalabilidad.
</details>
