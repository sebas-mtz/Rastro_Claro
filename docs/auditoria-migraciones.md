Auditoría técnica de Valuaciones, datos de ejemplo y pruebas

Las correcciones están aplicadas en el código del proyecto. El esquema de referencia es el resultado de ejecutar todas las migraciones actuales, incluidas sus alteraciones e índices. **No se modificó ninguna migración ni se eliminó ninguna columna vigente.** Se conservaron los cambios locales que ya existían antes de esta auditoría.

La ejecución inicial produjo 9 pruebas fallidas de 50 y la siembra se detuvo en `pajillas.fecha_vencimiento`. La verificación final produjo **81 pruebas aprobadas, 529 aserciones, cero fallos**, además de una siembra completa sin excepciones.

Inconsistencias de Valuaciones y sus dependencias:

| Archivo | Inconsistencia | Corrección aplicada |
| --- | --- | --- |
| `app/Http/Controllers/AnimalValuationController.php` | `show()` y `exportarPdf()` cargaban `Animal::genetica()`, relación inexistente. | Cargan las relaciones vigentes; los atributos genéticos se leen directamente de `animals`. |
| `app/Services/AnimalValuationService.php` | Leía `genetica.porcentaje_margen_genetico` y cargaba `servicio.macho.genetica`. | El margen procede del valor solicitado o de `animal_valuations.porcentaje_margen_genetico`; el registro del semental procede de `animals.numero_registro`. Sin cotización previa, el margen inicial es cero. |
| `app/Services/AnimalValuationService.php` | Comparaba `animals.sexo` con `F`, aunque la migración admite `M` y `H`. | Usa el método vigente `Animal::esHembra()`. |
| `app/Services/AnimalValuationService.php` | Un `estado` nulo aceptado por validación podía sobrescribir una columna no nula. | Solo actualiza el estado cuando llega un valor no nulo. |
| `resources/js/Pages/Valuaciones/Show.jsx` | Usaba `microchip_codigo`, `genetica.numero_registro` y `genetica.porcentaje_pureza`. | Usa `lectura_microchip`, `numero_registro` y `grado_pureza`. Este último se muestra como texto, conforme a su tipo en la migración, sin inventar un porcentaje. |
| `app/Models/Animal.php` | `$fillable` contenía `proovedor_origen`; omitía `estado_reproductivo`, que sí existe en las migraciones. | Corrige a `proveedor_origen` y permite persistir `estado_reproductivo`. Conserva el accessor existente de estado calculado. |
| `app/Http/Controllers/AnimalValuationController.php` | La vista y el PDF recalculaban sin conservar el plus y los ajustes guardados. | Ambos reciben las decisiones persistidas de la cotización activa. |
| `app/Http/Controllers/AnimalValuationController.php` | `required_with:ajuste_manual` exigía motivo incluso con ajuste cero; el simulador envía cero por defecto. | Exige motivo cuando el ajuste es distinto de cero y conserva la justificación adicional para márgenes superiores al 100 %. |
| `app/Http/Controllers/AnimalValuationController.php` | Validaba `fecha_venta` pero descartaba ese dato. No existe esa columna en `animal_valuations`. | Conserva la fecha en el JSON vigente `animal_valuation_historial.datos_nuevos`. La confirmación registra el precio; no crea una operación en `ventas`. |
| `resources/views/valuaciones/pdf.blade.php` | La ruta de exportación apuntaba a una vista inexistente. | Se añadió la plantilla con identificación vigente, desglose, margen, ajustes y precio estimado. |
| `config/tenancy.php` | Incluía `Auditoria` en el filtro que escribe `owner_id`, provocando `Column not found` al cambiar el plus. | Se retiró del filtro genérico. Su migración define expresamente la bitácora como global, sin `owner_id`; se conservan los controles de acceso existentes. |

Se revisaron también `ConfirmarVentaModal.jsx`, `DesgloseCostos.jsx`, `HistorialPrecio.jsx`, `LineaTiempo.jsx`, `SimuladorModal.jsx`, los modelos de valuación, `ConfiguracionValuacion` y `ValuacionRecalculoObserver`. No necesitaron sustituciones adicionales de columnas o métodos. `costoPorKg()`, `costoTotal()`, `fechaNacimiento()`, `criaOrigen()` y las relaciones de historial y detalles existen. La validación de Valuaciones está dentro del controlador; no hay Requests separados para ese módulo.

Las claves `buckets`, `avisos`, `calculo`, `lineaTiempo` y `utilidad` son datos calculados o contenido JSON, no columnas que deban añadirse a la base de datos.

Inconsistencias de seeders:

| Archivo | Inconsistencia | Corrección aplicada |
| --- | --- | --- |
| `database/seeders/PajillaSeeder.php` | Insertaba `fecha_vencimiento`, inexistente, y omitía `capacidad_pajilla`, obligatoria. | Elimina el atributo inexistente y proporciona capacidad de ejemplo de 0.25. No sustituye vencimiento por una fecha con significado diferente. |
| `database/seeders/AnimalSeeder.php` | Insertaba `animals.peso_inicial` y `animals.fecha_peso_inicial`, inexistentes. | Retira esos atributos y el cálculo auxiliar que solo los alimentaba; conserva `peso`, `fecha_nac` y la genealogía. |
| `database/seeders/ReproduccionSeeder.php` | Actualizaba esos mismos campos inexistentes. | Conserva el peso natal en `crias.peso_nacimiento`, además del peso y fecha de nacimiento vigentes del animal. |
| `database/seeders/PesajeSeeder.php` | Guardaba la referencia inicial en columnas inexistentes de `animals` y la omitía del historial. | Registra el peso de nacimiento como primer `pesaje`; actualiza únicamente `animals.peso` como peso actual.|
| `database/seeders/ControlPartosSeeder.php` | Consultaba/escribía `muertes` y actualizaba columnas de peso inicial inexistentes. Sin variable de entorno, se omitía toda su ejecución. | Usa `bajas` con `tipo_salida = fallecimiento`, guarda el peso inicial en `pesajes` y usa la cuenta demo cuando no se especifica otra. |
| `database/seeders/UserSeeder.php` | Con `CONTROL_PARTOS_SEED_EMAIL` definido, exigía que la cuenta ya existiera incluso después de `migrate:fresh`. | Permite crear la cuenta de ejemplo en una base vacía. Conserva la comprobación de cuenta existente cuando la base ya contiene usuarios. |
| `database/seeders/ValuacionEjemploSeeder.php` | Importaba `AnimalGenetica`, inexistente; generaba hembras con `F` y anunciaba registros genéticos sin persistir los atributos vigentes. | Usa `H`, `numero_registro` y `grado_pureza`; el margen se guarda en la cotización. |
| `database/seeders/ValuacionEjemploSeeder.php` | La limpieza de ejemplos omitía el filtro de cuenta y el cierre de sesión no estaba protegido frente a excepciones. | La limpieza respeta la cuenta seleccionada, participa en la transacción y restaura la sesión anterior mediante `finally`. |

También se revisaron `DatabaseSeeder`, `AlimentacionSeeder`, `DonadoresExternosSeeder`, `EventoSaludSeeder`, `LoteSeeder`, `ProduccionSeeder`, `TermoSeeder`, `TratamientoSeeder`, `VacunaSeeder` y `VentaSeeder`; la siembra ejecuta sus datos contra las migraciones sin errores de columnas ni claves foráneas. `AlertaSeeder` y `SaludSeeder` están vacíos, y los cuerpos de `FaenaSeeder` y `SacrificioSeeder` están comentados: se conserva ese comportamiento. Sus factories sí se verifican con inserciones reales.

Inconsistencias de factories:

| Archivo | Corrección aplicada |
| --- | --- |
| `database/factories/AlertaFactory.php` | Completa tipo, fecha objetivo, severidad y estado. |
| `database/factories/AlimentacionFactory.php` | Completa fecha, cantidad y unidad, y crea animal y ración válidos. |
| `database/factories/AnimalFactory.php` | Completa especie, arete y sexo válidos, con datos de nacimiento y peso. |
| `database/factories/InventarioInsumoFactory.php` | Completa nombre, tipo, existencias, unidad y costo. |
| `database/factories/LoteFactory.php` | Proporciona el nombre obligatorio. |
| `database/factories/ProduccionFactory.php` | Completa datos vigentes y crea el animal relacionado. |
| `database/factories/RacionFactory.php` | Proporciona nombre, precio por kg y estado activo. |
| `database/factories/VacunaFactory.php` | Proporciona nombre y especie objetivo. |
| `database/factories/CompradoresFactory.php` | Delega en la nueva `CompradorFactory`, asociada al modelo singular vigente. |
| `database/factories/FaenasFactory.php` | Delega en la nueva `FaenaFactory`, con animal y pesos válidos. |
| `database/factories/SacrificiosFactory.php` | Delega en la nueva `SacrificioFactory`, con animal, motivo y pesos válidos. |
| `database/factories/VentasFactory.php` | Delega en la nueva `VentaFactory`, con vendedor, comprador, relación polimórfica e importes válidos. |
| `database/factories/ReproduccionFactory.php` | Delega en la nueva `EventoReproductivoFactory`, sustituyendo la referencia al modelo eliminado `Reproduccion`. |
| `database/factories/SaludFactory.php` | Delega en la nueva `EventoSaludFactory`, sustituyendo la referencia al modelo eliminado `Salud`. |
| `app/Models/Comprador.php` | Añade `HasFactory` para habilitar `Comprador::factory()`. |
| `app/Models/EventoReproductivo.php` | Añade `HasFactory` para habilitar `EventoReproductivo::factory()`. |
| `app/Models/EventoSalud.php` | Añade `HasFactory` para habilitar `EventoSalud::factory()`. |
| `app/Models/Racion.php` | Añade `HasFactory` para habilitar `Racion::factory()`. |
| `app/Models/Venta.php` | Añade `HasFactory` para habilitar `Venta::factory()`. |

Las factories anteriores estaban vacías. Los seis nombres antiguos se conservan como clases de compatibilidad; ahora resuelven modelos existentes. `UserFactory` ya coincidía con las migraciones y no se modificó. Se probaron las 21 factories finales mediante inserciones reales, incluidos los nombres de compatibilidad.

Inconsistencias y cobertura de pruebas:

| Archivo | Corrección o cobertura |
| --- | --- |
| `tests/Feature/AnimalEstadoContextualTest.php` | Sustituye `Muerte::create()` por `Baja::create()` con el tipo de salida obligatorio. |
| `tests/Feature/ReproduccionDesteteTest.php` | Sustituye el modelo `Muerte` y la tabla `muertes`; usa especie y estados admitidos por los validadores actuales de destete. Las aserciones siguen comprobando peso, lote, madre y exclusión de crías no disponibles. |
| `tests/Feature/ReproduccionDesteteTest.php` | El cambio temporal de correo del seeder ahora actualiza y restaura `getenv`, `$_ENV` y `$_SERVER`; evita depender de cómo se heredó el entorno al iniciar PHPUnit. |
| `tests/Feature/PesajeGananciaTest.php` | Sustituye las columnas de peso inicial por un primer `pesaje`; comprueba la ganancia y recuperación del peso previo al borrar el último registro. Para nacimientos, comprueba `crias.peso_nacimiento` y el pesaje natal, y envía el vigor obligatorio vigente (`B`). Las fechas se contrastan con `whereDate` para respetar la representación del motor. |
| `tests/Feature/AnimalValuationTest.php` | Añade cobertura de atributos vigentes, conservación de ajustes, simulación sin persistencia, validación, identificación de hembras y sementales, deduplicación de costos, observer, recálculo, confirmación de precio, exportación PDF, configuración y aislamiento de cuentas. |
| `tests/Feature/DatabaseSchemaTest.php` | Inserta todas las factories, ejecuta todos los seeders activos y opcionales, repite el ejemplo de Valuaciones, comprueba importes conocidos, claves foráneas, atributos vigentes de Animal y compatibilidad de los filtros de cuenta con las columnas reales. |

El resto de `tests/Feature`, las pruebas de autenticación y `tests/Unit` se revisó y pasó sin sustituciones adicionales. Se conservaron las rutas existentes.

Comprobación reproducible:

```powershell
.\scripts\verify-schema.ps1
```

El script fuerza `APP_ENV=testing`, SQLite en memoria y almacenamiento de sesión/caché en memoria. Evita reutilizar configuración cacheada y restaura las variables de proceso al terminar. Ejecuta estos dos comandos sin tocar los datos locales:

```text
php artisan test
php artisan migrate:fresh --seed --no-interaction
```

Resultados de la ejecución final:

| Comprobación | Resultado |
| --- | --- |
| Suite completa | 81 pruebas aprobadas, 529 aserciones, cero fallos. |
| Migración desde cero y siembra | Código de salida 0, incluido Control de Partos. |
| Seeders opcionales y repetición de Valuaciones | Aprobados por `DatabaseSchemaTest`; ejemplo de 8,000 de costo y 17,000 de precio, y gestación gemelar de 600 por cría. |
| Integridad referencial en SQLite | `PRAGMA foreign_key_check` sin registros inválidos. |
| Sintaxis PHP | 68 archivos revisados sin errores. |
| Frontend | `npm.cmd run build` completó correctamente. |
| Revisión del diff de la auditoría | Sin errores de espacios; ninguna migración modificada. |

Evidencia local: `storage/logs/audit-verification.log` y `storage/logs/audit-build.log`. Vite informó que Node 22.11.0 está por debajo de su requisito 22.12+, aunque la compilación concluyó; no se alteraron dependencias ni la instalación de Node.

La aprobación al 100 % corresponde al esquema generado por las migraciones en **SQLite**. No se ejecutó una segunda validación contra MySQL/MariaDB. Para certificar ese motor, deben repetirse migración, siembra y suite con su conexión configurada hacia una base exclusiva de auditoría. `migrate:fresh` elimina las tablas de la conexión seleccionada; el script entregado evita ese efecto sobre la base local al usar memoria.
