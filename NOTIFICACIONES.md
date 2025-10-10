# Sistema de Notificaciones en Segundo Plano

Este documento describe el sistema de notificaciones asíncronas implementado en Testigo.cl.

## Resumen

El sistema envía notificaciones por email y las almacena en base de datos de forma asíncrona, sin bloquear las operaciones principales de la aplicación. Utiliza **Redis** como cola de trabajos y un **worker dedicado** que procesa las notificaciones en segundo plano.

## Arquitectura

### Componentes

1. **Events** (`app/Events/`): Eventos de dominio que se disparan cuando ocurren acciones importantes
2. **Listeners** (`app/Listeners/`): Escuchan eventos y encolan notificaciones
3. **Notifications** (`app/Notifications/`): Clases que definen el contenido de emails y notificaciones en BD
4. **Queue Worker** (Docker): Contenedor dedicado que procesa la cola de trabajos
5. **Redis**: Sistema de colas para almacenar trabajos pendientes

### Flujo de Notificaciones

```
Acción del usuario (crear caso, invertir, etc.)
    ↓
Evento disparado (CaseCreated, InvestmentCreated, etc.)
    ↓
Listener escucha el evento y encola Notification
    ↓
Redis almacena el trabajo en la cola
    ↓
Queue Worker procesa el trabajo en segundo plano
    ↓
Se envía el email y se guarda en tabla notifications
```

## Eventos y Notificaciones Implementados

### 1. Caso Creado (CaseCreated)

**Cuándo se dispara**: Cuando una víctima envía un nuevo caso

**Notificación**: `CaseCreatedNotification`
- **Destinatario**: Víctima que creó el caso
- **Canales**: Email + Base de datos
- **Contenido**: Confirmación de que el caso fue enviado exitosamente

### 2. Cambio de Estado de Caso (CaseStatusChanged)

**Cuándo se dispara**: Cuando el estado de un caso cambia

**Notificación**: `CaseStatusChangedNotification`
- **Destinatarios**:
  - Víctima propietaria del caso
  - Inversionistas (si el caso llega a FUNDED)
- **Canales**: Email + Base de datos
- **Contenido**: Depende del nuevo estado:
  - `UNDER_REVIEW`: Caso en revisión por abogados
  - `APPROVED`: Caso aprobado con probabilidad de éxito
  - `PUBLISHED`: Caso publicado para inversionistas
  - `FUNDED`: Meta de financiamiento alcanzada
  - `IN_PROGRESS`: Procedimientos legales iniciados
  - `COMPLETED`: Caso finalizado
  - `REJECTED`: Caso rechazado

### 3. Inversión Creada (InvestmentCreated)

**Cuándo se dispara**: Cuando un inversionista financia un caso

**Notificación**: `InvestmentCreatedNotification`
- **Destinatarios**:
  - Inversionista (confirmación de inversión)
  - Víctima (notificación de nueva inversión recibida)
- **Canales**: Email + Base de datos
- **Contenido**:
  - Para inversionista: detalles de inversión y retorno esperado
  - Para víctima: progreso del financiamiento

### 4. Caso Financiado (CaseFundedNotification)

**Cuándo se dispara**: Cuando un caso alcanza su meta de financiamiento

**Notificación**: `CaseFundedNotification`
- **Destinatarios**: Todos los inversionistas que participaron
- **Canales**: Email + Base de datos
- **Contenido**: Celebración de meta alcanzada y próximos pasos

## Configuración

### Variables de Entorno (.env)

```env
# Cola de trabajos usando Redis
QUEUE_CONNECTION=redis

# Configuración de Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Email (configurar según proveedor)
MAIL_MAILER=log  # Cambiar a smtp, mailgun, etc. en producción
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="noreply@testigo.cl"
MAIL_FROM_NAME="Testigo.cl"
```

### Docker Compose

El servicio `queue` en `docker-compose.yml` ejecuta el worker:

```yaml
queue:
  build:
    context: .
    dockerfile: docker/Dockerfile
  container_name: testigos_queue
  restart: unless-stopped
  command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
  depends_on:
    - db
    - redis
```

## Comandos Útiles

### Gestión de Colas

```bash
# Ver estado de contenedores
docker-compose ps

# Ver logs del worker en tiempo real
docker logs -f testigos_queue

# Reiniciar el worker (después de cambios en código)
docker-compose restart queue

# Ver trabajos fallidos
docker exec testigos_app php artisan queue:failed

# Reintentar trabajos fallidos
docker exec testigos_app php artisan queue:retry all

# Limpiar trabajos fallidos
docker exec testigos_app php artisan queue:flush

# Ver estadísticas de la cola (requiere Horizon o similar)
docker exec testigos_app php artisan queue:monitor redis:default
```

### Desarrollo y Testing

```bash
# Procesar un solo trabajo (para testing)
docker exec testigos_app php artisan queue:work redis --once

# Ver tablas de jobs y failed_jobs
docker exec testigos_db psql -U postgres -d testigos -c "SELECT * FROM jobs;"
docker exec testigos_db psql -U postgres -d testigos -c "SELECT * FROM failed_jobs;"

# Ver notificaciones en base de datos
docker exec testigos_db psql -U postgres -d testigos -c "SELECT * FROM notifications;"
```

## Base de Datos

### Tabla `notifications`

```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    type VARCHAR(255) NOT NULL,        -- Clase de la notificación
    notifiable_type VARCHAR(255) NOT NULL,  -- 'App\Models\User'
    notifiable_id BIGINT NOT NULL,     -- ID del usuario
    data TEXT NOT NULL,                -- JSON con datos de la notificación
    read_at TIMESTAMP NULL,            -- NULL = no leída
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Consultas Útiles

```sql
-- Notificaciones no leídas de un usuario
SELECT * FROM notifications
WHERE notifiable_id = 1
  AND notifiable_type = 'App\Models\User'
  AND read_at IS NULL;

-- Marcar notificación como leída
UPDATE notifications
SET read_at = NOW()
WHERE id = 'uuid-here';

-- Contar notificaciones por tipo
SELECT type, COUNT(*)
FROM notifications
GROUP BY type;
```

## Cómo Disparar Eventos

### En Controladores

```php
use App\Events\CaseCreated;
use App\Events\CaseStatusChanged;

// Al crear un caso
event(new CaseCreated($case));

// Al cambiar estado de caso
event(new CaseStatusChanged($case, $oldStatus, $newStatus));
```

### En Actions o Services

```php
// Ejemplo en CaseService
public function createCase(array $data): CaseModel
{
    $case = CaseModel::create($data);

    // Disparar evento (notificación se procesa en segundo plano)
    event(new CaseCreated($case));

    return $case;
}
```

## Agregar Nuevas Notificaciones

### 1. Crear Notification

```bash
docker exec testigos_app php artisan make:notification NombreNotification
```

### 2. Implementar la Notificación

```php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NombreNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $data
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Asunto del email')
            ->greeting('¡Hola!')
            ->line('Contenido del mensaje')
            ->action('Botón', url('/'))
            ->line('Despedida');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Mensaje para BD',
            'type' => 'tipo_notificacion'
        ];
    }
}
```

### 3. Crear Listener (Opcional)

Si quieres escuchar un evento específico:

```bash
docker exec testigos_app php artisan make:listener SendNombreNotification --event=EventoNombre
```

### 4. Registrar en AppServiceProvider

```php
Event::listen(
    EventoNombre::class,
    SendNombreNotification::class,
);
```

## Monitoreo y Troubleshooting

### Problemas Comunes

1. **Worker no procesa trabajos**
   - Verificar que Redis esté corriendo: `docker-compose ps`
   - Reiniciar worker: `docker-compose restart queue`
   - Ver logs: `docker logs testigos_queue`

2. **Trabajos fallando constantemente**
   - Revisar `failed_jobs` table
   - Ver excepción en columna `exception`
   - Verificar configuración de email

3. **Emails no se envían**
   - Verificar `MAIL_*` en `.env`
   - Si `MAIL_MAILER=log`, los emails se guardan en `storage/logs/laravel.log`
   - Configurar servicio real (Mailgun, SES, SMTP) en producción

### Logs Importantes

```bash
# Logs de Laravel
docker exec testigos_app tail -f storage/logs/laravel.log

# Logs del queue worker
docker logs -f testigos_queue

# Logs de Redis
docker logs -f testigos_redis
```

## Producción

### Recomendaciones

1. **Usar supervisor o systemd** en servidor para mantener el worker corriendo
2. **Configurar servicio de email real** (Mailgun, Amazon SES, SendGrid)
3. **Implementar Horizon** para monitoreo avanzado de colas
4. **Configurar alertas** para trabajos fallidos
5. **Usar múltiples workers** si el volumen de notificaciones es alto

### Múltiples Workers

```yaml
# docker-compose.yml
queue1:
  # ... configuración similar
  command: php artisan queue:work redis --queue=default,emails --sleep=3

queue2:
  # ... configuración similar
  command: php artisan queue:work redis --queue=default --sleep=3
```

## API Endpoints (Futuro)

Endpoints sugeridos para gestión de notificaciones desde el frontend:

```
GET    /api/v1/notifications              # Listar notificaciones del usuario
GET    /api/v1/notifications/unread       # Solo no leídas
PATCH  /api/v1/notifications/{id}/read    # Marcar como leída
PATCH  /api/v1/notifications/read-all     # Marcar todas como leídas
DELETE /api/v1/notifications/{id}         # Eliminar notificación
```

## Conclusión

El sistema de notificaciones está completamente funcional y procesando trabajos en segundo plano. Las notificaciones se disparan automáticamente cuando ocurren eventos importantes en la plataforma y se procesan de forma asíncrona sin afectar el rendimiento de las operaciones principales.
