<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Lote;
use App\Models\PuestoTrabajador;
use App\Models\Raza;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Prueba de humo: recorre TODAS las rutas GET de la aplicación y verifica que
 * respondan sin error del servidor.
 *
 * Nació de un error real: al retirar los catálogos de otras especies quedó una
 * referencia colgada en la página de Lotes que ninguna prueba detectaba, porque
 * la suite cubría el backend y el build de Vite no falla por una variable no
 * definida (JavaScript solo revienta al ejecutarla).
 */
class PaginasCarganTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Páginas cuyas consultas usan funciones exclusivas de MySQL
     * (DATE_FORMAT, YEAR, MONTH) que SQLite no implementa.
     *
     * En producción funcionan; aquí darían un falso positivo. Se excluyen de
     * forma explícita en vez de silenciar el fallo, para que quede claro que
     * NO están verificadas por esta prueba.
     */
    private const SOLO_MYSQL = [
        'salud.estadisticas',
        'producciones.index',
        'faenas.index',
        'ventas.index',
        'sacrificios.index',
    ];

    /** Rutas que no son páginas navegables o requieren un contexto especial. */
    private const EXCLUIDAS = [
        'sanctum.csrf-cookie',
        'ignition.healthCheck',
        'ignition.executeSolution',
        'ignition.updateConfig',
        'storage.local',
        'auth.google',
        'logout',
        'verification.notice',
        'verification.verify',
        'password.confirm',
        'documentos.download',
    ];

    private function prepararDatos(User $user): array
    {
        $raza = Raza::create(['nombre' => 'Dorper', 'activo' => true]);

        $lote = Lote::create([
            'nombre' => 'Lote de prueba',
            'corral_potrero' => 'Norte',
            'responsable_id' => $user->id,
        ]);

        $animal = Animal::create([
            'especie' => Animal::ESPECIE,
            'arete' => 'OV-SMOKE',
            'sexo' => 'F',
            'fecha_nac' => now()->subYear()->toDateString(),
            'raza_id' => $raza->id,
            'lote_id' => $lote->id,
        ]);

        $puesto = PuestoTrabajador::create([
            'clave' => 'ganadero',
            'nombre' => 'Ganadero',
            'area' => 'Manejo del rebaño',
            'activo' => true,
        ]);

        $trabajador = Trabajador::create([
            'nombre' => 'Trabajador',
            'apellido_paterno' => 'De prueba',
            'puesto_id' => $puesto->id,
            'costo_hora' => 90,
            'activo' => true,
        ]);

        return [
            'animal' => $animal,
            'lote' => $lote,
            'raza' => $raza,
            'trabajador' => $trabajador,
        ];
    }

    public function test_every_get_page_responds_without_a_server_error(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($user);

        $datos = $this->prepararDatos($user);

        $fallos = [];
        $revisadas = 0;

        foreach (Route::getRoutes() as $ruta) {
            if (! in_array('GET', $ruta->methods(), true)) {
                continue;
            }

            $nombre = $ruta->getName();
            $uri = $ruta->uri();

            // Solo páginas web autenticadas del sistema
            if (! $nombre
                || in_array($nombre, self::EXCLUIDAS, true)
                || in_array($nombre, self::SOLO_MYSQL, true)) {
                continue;
            }

            if (str_starts_with($uri, 'api/') || str_starts_with($uri, '_')) {
                continue;
            }

            // Sustituye los parámetros de ruta por registros reales
            $url = $uri;
            $url = str_replace(
                ['{animal}', '{lote}', '{trabajador}', '{eventoSalud}', '{tratamiento}', '{costo}', '{baja}', '{documento}'],
                [$datos['animal']->id, $datos['lote']->id, $datos['trabajador']->id, '1', '1', '1', '1', '1'],
                $url
            );

            // Si quedan parámetros sin resolver, se omite: necesitaría datos
            // específicos de otro módulo y no es lo que esta prueba vigila.
            if (str_contains($url, '{')) {
                continue;
            }

            $revisadas++;
            $respuesta = $this->get('/' . ltrim($url, '/'));

            // Las descargas devuelven un stream, que no expone status():
            // se consulta getStatusCode(), disponible en ambos tipos.
            $codigo = $respuesta->getStatusCode();

            // 200, redirecciones y 404 son respuestas válidas; 5xx no lo es.
            if ($codigo >= 500) {
                $fallos[] = "{$nombre} (/{$url}) → {$codigo}";
            }
        }

        $this->assertGreaterThan(15, $revisadas, 'La prueba debería recorrer varias páginas.');

        $this->assertSame(
            [],
            $fallos,
            "Estas páginas devolvieron error del servidor:\n" . implode("\n", $fallos)
        );
    }
}
