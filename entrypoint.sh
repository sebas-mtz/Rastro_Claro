#!/bin/sh

# Ejecutar las migraciones automáticamente antes de iniciar el servidor
php artisan migrate --force

# Iniciar el servidor Apache en primer plano (el comando original de Docker)
exec apache2-foreground