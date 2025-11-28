# Colombianos UNE - Sitio Web

## 📋 Descripción del Proyecto

**Colombianos UNE** es un sitio web institucional desarrollado con Drupal que proporciona información y servicios para la comunidad colombiana. El proyecto ha sido migrado exitosamente de Drupal 8 a Drupal 11, garantizando mayor seguridad, rendimiento y características modernas.

## 🚀 Tecnologías Utilizadas

- **CMS:** Drupal 11.2.8
- **PHP:** 8.3.27
- **Drush:** 13.6.2
- **Theme Base:** Bootstrap
- **Gestión de Dependencias:** Composer 2.x

## 📁 Estructura del Proyecto

```
colombianosune/
├── composer.json          # Dependencias y configuración de Composer
├── recipes/              # Recetas de Drupal
├── vendor/               # Dependencias de terceros
├── web/                  # Documento raíz web
│   ├── core/            # Núcleo de Drupal
│   ├── modules/         # Módulos (contrib y custom)
│   ├── themes/          # Temas (contrib y custom)
│   │   └── custom/
│   │       └── colombianosune/  # Tema personalizado del proyecto
│   ├── sites/           # Configuración del sitio
│   └── profiles/        # Perfiles de instalación
```

## 🔧 Módulos Principales Instalados

### Módulos de Funcionalidad
- **Admin Toolbar** (3.6) - Mejora la barra de administración
- **Backup Migrate** (5.1) - Respaldos de base de datos
- **CKEditor5 Plugin Pack** (1.4) - Editor de texto enriquecido
- **Pathauto** (1.14) - URLs amigables automáticas
- **Token** (1.16) - Sistema de tokens para contenido dinámico
- **Views Data Export** (1.8) - Exportación de datos de vistas

### Módulos de UI/UX
- **Gin** (5.0) - Tema administrativo moderno
- **Bootstrap** (5.0) - Framework CSS
- **Views Bootstrap** (5.4) - Componentes Bootstrap para vistas
- **Views Slideshow** (5.0) - Presentaciones de diapositivas
- **Views Accordion** (2.0) - Componente acordeón

### Módulos de SEO y Analytics
- **Google Analytics** (4.0) - Seguimiento de analytics
- **XML Sitemap** (2.0) - Mapas de sitio XML
- **Google Custom Search Engine** (5.0) - Búsqueda personalizada
- **ReCAPTCHA** (3.4) - Protección contra spam

### Módulos de Migración
- **Migrate Tools** (6.1) - Herramientas para migración de contenido

## 🔄 Migración de Drupal 8 a Drupal 11

### Proceso de Migración Realizado

#### 1. **Análisis Previo**
- Auditoría de módulos y temas personalizados
- Verificación de compatibilidad de módulos contribuidos
- Respaldo completo de archivos y base de datos

#### 2. **Preparación del Entorno**
- Actualización a PHP 8.3.27 (requerido para Drupal 11)
- Instalación de dependencias actualizadas
- Configuración de Composer para Drupal 11

#### 3. **Migración Paso a Paso**
```bash
# Respaldo de la instalación actual
./vendor/bin/drush sql-dump --result-file=cnuDB.sql

# Actualización de dependencias
composer update "drupal/core-*" --with-dependencies

# Actualización de módulos contribuidos
composer update drupal/* --with-dependencies

# Ejecutar actualizaciones de base de datos
./vendor/bin/drush updatedb

# Limpiar cachés
./vendor/bin/drush cache:rebuild
```

#### 4. **Verificaciones Post-Migración**
- ✅ Funcionalidad del tema personalizado `colombianosune`
- ✅ Compatibilidad de todos los módulos
- ✅ Integridad de contenido y configuraciones
- ✅ URLs y redirecciones funcionando correctamente

### Beneficios de la Migración

- **Seguridad Mejorada:** Drupal 11 incluye las últimas correcciones de seguridad
- **Rendimiento Optimizado:** Mejor gestión de memoria y velocidad de carga
- **PHP 8.3 Compatible:** Aprovecha las mejoras de rendimiento de PHP 8.3
- **Nuevas Características:** CKEditor 5, mejoras en la API, etc.
- **Soporte Extendido:** Soporte a largo plazo hasta 2030

## 🛠️ Instalación y Configuración

### Requisitos del Sistema
- **PHP:** 8.3.x
- **MySQL/MariaDB:** 5.7.8+ / 10.3.7+
- **Apache/Nginx:** Configurado para Drupal
- **Composer:** 2.x

### Instalación Local

1. **Clonar el repositorio:**
   ```bash
   git clone <repository-url> colombianosune
   cd colombianosune
   ```

2. **Instalar dependencias:**
   ```bash
   composer install
   ```

3. **Configurar base de datos:**
   ```bash
   # Crear base de datos
   mysql -u root -p -e "CREATE DATABASE colombianosune;"

   # Importar datos
   mysql -u root -p colombianosune < cnuDB.sql
   ```

4. **Configurar archivos:**
   ```bash
   # Copiar configuración de ejemplo
   cp web/sites/default/default.settings.php web/sites/default/settings.php

   # Configurar permisos
   chmod 666 web/sites/default/settings.php
   mkdir web/sites/default/files
   chmod 777 web/sites/default/files
   ```

5. **Configurar servidor local:**
   ```bash
   # Con PHP built-in server
   cd web && php -S localhost:8080
   ```

## 🎨 Tema Personalizado

El proyecto utiliza un tema personalizado basado en Bootstrap:

- **Ubicación:** `web/themes/custom/colombianosune/`
- **Preprocesador:** Less
- **Framework:** Bootstrap 5.x
- **Personalización:** Componentes específicos para Colombianos UNE

### Desarrollo del Tema
```bash
# Navegar al directorio del tema
cd web/themes/custom/colombianosune/

# Compilar estilos (requiere compilador Less)
# Ver README.md del tema para instrucciones específicas
```

## 🚀 Comandos Útiles con Drush

```bash
# Estado del sitio
./vendor/bin/drush status

# Limpiar cachés
./vendor/bin/drush cache:rebuild

# Habilitar modo de mantenimiento
./vendor/bin/drush state:set system.maintenance_mode 1

# Deshabilitar modo de mantenimiento
./vendor/bin/drush state:set system.maintenance_mode 0

# Actualizar base de datos
./vendor/bin/drush updatedb

# Exportar configuración
./vendor/bin/drush config:export

# Importar configuración
./vendor/bin/drush config:import

# Crear respaldo de base de datos
./vendor/bin/drush sql:dump --result-file=backup_$(date +%Y%m%d_%H%M%S).sql
```

## 🔒 Seguridad y Mantenimiento

### Prácticas de Seguridad Implementadas
- Actualizaciones regulares de núcleo y módulos
- Configuración segura de archivos y directorios
- Módulo ReCAPTCHA para protección contra spam
- Respaldos automáticos programados

### Tareas de Mantenimiento
- **Semanal:** Verificar actualizaciones de seguridad
- **Mensual:** Actualizar módulos contribuidos
- **Trimestral:** Auditoría completa de seguridad
- **Diario:** Respaldos automáticos de base de datos

## 📝 Notas de Migración

### Cambios Importantes en Drupal 11
- **CKEditor 4 → CKEditor 5:** Migración automática del editor
- **jQuery UI:** Eliminado del núcleo, agregado como módulo contribuido
- **Twig 3:** Mejoras en plantillas y rendimiento
- **Symfony 6:** Framework actualizado para mejor rendimiento

### Módulos Actualizados Durante la Migración
- Todos los módulos han sido actualizados a versiones compatibles con Drupal 11
- Se mantuvieron todas las funcionalidades existentes
- Mejoras de rendimiento en módulos de vistas y administración

## 📞 Soporte y Contacto

Para reportar problemas o solicitar nuevas funcionalidades:

1. Crear un issue en el repositorio del proyecto
2. Documentar pasos para reproducir el problema
3. Incluir información del entorno (versión PHP, navegador, etc.)

## 📄 Licencia

Este proyecto está licenciado bajo GPL-2.0-or-later, siguiendo las prácticas estándar de Drupal.

---

**Última actualización:** Noviembre 2024
**Versión de Drupal:** 11.2.8
**Estado del proyecto:** Producción estable
