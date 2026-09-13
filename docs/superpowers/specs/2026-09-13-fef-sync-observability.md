# Sincronización FEF observable (árbitros, partidos, clubes, campeonatos)

**Fecha:** 2026-09-13 · **Pedido:** "procesos para que siempre tenga actualizado todo de los árbitros, partidos, clubes… que sean procesos que vea el super admin".

## Situación previa
`SyncFefMatchesJob` corre cada hora (routes/console.php), ingesta campeonatos/clubes/partidos desde la API pública FEF (`FefIngestService`) y crea propuestas para los árbitros (`RefereeMatcher`). Solo deja un `Log::info`; el super admin no ve si corrió, cuándo, qué trajo ni si falló. `FefApiClient` se traga los errores de red (best-effort) sin reportarlos.

## Decisiones
| Tema | Decisión |
|---|---|
| Historial | Tabla `fef_sync_runs` (origen `schedule/manual/cli`, estado `running/success/partial/failed`, inicio/fin/duración, conteos, errores de API, mensaje, quién la lanzó). Se conservan 90 días. |
| Orquestación | `FefSyncService::run()` envuelve ingesta + matching + directorio de árbitros, registra la corrida y decide el estado: `partial` si hubo endpoints de la FEF caídos, `failed` si explotó (se relanza para que Horizon reintente). Job, comando y botón del panel pasan por él. |
| Errores de API | `FefApiClient` acumula `{path, motivo}` por corrida (`errors()`); sigue sin lanzar. |
| Directorio de árbitros | Tabla `fef_referees` derivada de `football_matches.officials`: nombre FEF, clave normalizada, partidos, roles, primer/último partido y `tenant_id` si coincide con una cuenta árbitro (mismo `RefereeMatcher::normalize`). Se recalcula en cada sync. La FEF no expone un padrón de árbitros: esta es la mejor fuente disponible. |
| Panel | Grupo "Árbitros": **Sincronización FEF** (historial, estado con color, conteos, errores, botón "Sincronizar ahora", auto-refresco, badge rojo si hubo fallos en 24 h) y **Árbitros FEF** (directorio, filtro con/sin cuenta). Widget en el dashboard: última corrida y su antigüedad, partidos, campeonatos, clubes, propuestas pendientes, árbitros detectados. |
| Alertas | `AdminEventNotification` (correo + campana) a los super admins en `fef_sync.failed` (solo al pasar a fallido, sin repetir cada hora) y `fef_sync.stale` (comando diario `arbitros:sync-health` si no hay corrida exitosa en `arbitros.sync.stale_hours`, por defecto 3 h). |
| Frecuencia | Se mantiene cada hora (la data es post-partido). `arbitros:sync-health` a las 08:00. |

## Fuera de alcance
Cambios en la app del árbitro; comisario/delegado/VAR (la API no los trae); padrón oficial de árbitros (no existe endpoint público).
