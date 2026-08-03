<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convierte la raza de texto libre en referencias al catálogo.
 *
 * Reglas aplicadas:
 *   - El texto original se conserva íntegro en `animals.raza_original` y la
 *     columna `raza` tampoco se toca. Nada se pierde.
 *   - "Pelibuey x Dorper" se separa en raza principal + segunda raza y se
 *     marca como cruza.
 *   - Errores de captura conocidos se normalizan ("Kathadin" → "Katahdin").
 *   - Una raza que no esté en el catálogo se agrega en lugar de descartarse.
 *   - En cruzas de tres o más razas solo caben las dos primeras en los campos
 *     estructurados; el texto completo queda en `raza_original`.
 *
 * Se usa el query builder en vez de modelos Eloquent para que la migración no
 * dependa del scope de tenencia ni de cambios futuros en los modelos.
 */
return new class extends Migration
{
    private const CATALOGO = [
        ['Dorper', 'Sudáfrica', 'Carne'],
        ['Katahdin', 'Estados Unidos', 'Carne (pelo)'],
        ['Pelibuey', 'Caribe', 'Carne (pelo)'],
        ['Suffolk', 'Inglaterra', 'Carne'],
        ['Hampshire', 'Inglaterra', 'Carne'],
        ['Dorset', 'Inglaterra', 'Carne'],
        ['Texel', 'Países Bajos', 'Carne'],
        ['Charollais', 'Francia', 'Carne'],
        ['Blackbelly', 'Barbados', 'Carne (pelo)'],
        ['Rambouillet', 'Francia', 'Lana'],
        ['Merino', 'España', 'Lana'],
        ['Criollo', 'México', 'Doble propósito'],
        ['Otra raza', null, null],
    ];

    private const EQUIVALENCIAS = [
        'kathadin' => 'Katahdin',
        'katadhin' => 'Katahdin',
        'peliguey' => 'Pelibuey',
        'otra' => 'Otra raza',
        'otro' => 'Otra raza',
    ];

    public function up(): void
    {
        // Cuentas que tienen animales, más las que ya existan como usuarios.
        $owners = DB::table('animals')->distinct()->pluck('owner_id')
            ->merge(DB::table('users')->pluck('id'))
            ->unique()
            ->values();

        foreach ($owners as $ownerId) {
            $this->sembrarCatalogo($ownerId);
        }

        $animales = DB::table('animals')
            ->whereNotNull('raza')
            ->where('raza', '!=', '')
            ->get(['id', 'owner_id', 'raza']);

        foreach ($animales as $animal) {
            $partes = $this->separarCruza($animal->raza);

            if (empty($partes)) {
                continue;
            }

            $principalId = $this->idDeRaza($animal->owner_id, $partes[0]);
            $secundariaId = isset($partes[1]) ? $this->idDeRaza($animal->owner_id, $partes[1]) : null;

            DB::table('animals')->where('id', $animal->id)->update([
                'raza_id' => $principalId,
                'raza_secundaria_id' => $secundariaId,
                'es_cruza' => count($partes) > 1,
                'raza_original' => $animal->raza,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('animals')->update([
            'raza_id' => null,
            'raza_secundaria_id' => null,
            'es_cruza' => false,
            'raza_original' => null,
        ]);
    }

    private function sembrarCatalogo(?int $ownerId): void
    {
        foreach (self::CATALOGO as [$nombre, $origen, $aptitud]) {
            $existe = DB::table('razas')
                ->where('owner_id', $ownerId)
                ->where('nombre', $nombre)
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('razas')->insert([
                'owner_id' => $ownerId,
                'nombre' => $nombre,
                'origen' => $origen,
                'aptitud' => $aptitud,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * "Pelibuey x Dorper x Suffolk" → ['Pelibuey', 'Dorper', 'Suffolk']
     * Acepta los separadores " x ", " X " y " / ".
     */
    private function separarCruza(string $texto): array
    {
        $partes = preg_split('/\s*(?:x|X|\/)\s*/u', trim($texto));

        return array_values(array_filter(
            array_map('trim', $partes ?: []),
            fn ($p) => $p !== ''
        ));
    }

    /**
     * Devuelve el id de la raza en el catálogo de esa cuenta, creándola si el
     * nombre no existe todavía (para no perder razas capturadas a mano).
     */
    private function idDeRaza(?int $ownerId, string $nombre): ?int
    {
        $clave = mb_strtolower(trim($nombre));
        $clave = strtr($clave, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        $nombreCanonico = self::EQUIVALENCIAS[$clave] ?? null;

        if ($nombreCanonico === null) {
            // Coincidencia contra el catálogo ignorando mayúsculas y acentos.
            foreach (self::CATALOGO as [$catalogo]) {
                $claveCatalogo = strtr(mb_strtolower($catalogo), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

                if ($claveCatalogo === $clave) {
                    $nombreCanonico = $catalogo;
                    break;
                }
            }
        }

        // Raza desconocida: se agrega al catálogo con el texto tal como se capturó.
        $nombreCanonico ??= $nombre;

        $id = DB::table('razas')
            ->where('owner_id', $ownerId)
            ->where('nombre', $nombreCanonico)
            ->value('id');

        if ($id) {
            return $id;
        }

        return DB::table('razas')->insertGetId([
            'owner_id' => $ownerId,
            'nombre' => $nombreCanonico,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
