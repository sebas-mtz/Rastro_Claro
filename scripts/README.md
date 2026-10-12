# Predicción de pesajes

Proyecta el peso futuro de un ovino a partir de sus pesajes registrados y lo
grafica.

---

## Por qué no basta la regresión lineal

Un animal no crece en línea recta. Crece rápido de joven, se frena al acercarse
a su peso adulto y termina en una meseta: la curva es **sigmoide**.

Los números del ejemplar de prueba lo muestran:

| Modelo | R² | Error típico | AICc |
|---|---|---|---|
| **Gompertz** | 0.9995 | ±0.41 kg | **−12.32** |
| von Bertalanffy | 0.9986 | ±0.67 kg | −0.60 |
| Logístico | 0.9973 | ±0.93 kg | 7.35 |
| Lineal | 0.9777 | ±2.69 kg | 29.04 |

La recta tiene un R² de 0.978, que suena excelente — y aun así proyecta a 90
días **80 kg** para una borrega que en realidad se estabiliza en **61 kg**. Un R²
alto no salva a un modelo con la forma equivocada.

Pero la recta no sobra: en tramos cortos describe bien el crecimiento, y su
pendiente **es** la ganancia diaria de peso (GDP), la cifra con la que de verdad
se trabaja en el rancho.

Por eso el script ajusta los cuatro modelos y elige por **AICc**, que premia el
ajuste pero castiga meter parámetros de más. Con pocos pesajes eso favorece a la
recta, y es lo correcto: con tres puntos nadie puede afirmar dónde está la
meseta.

---

## Instalación

Una sola vez:

```bash
python -m venv scripts/.venv
```

```bash
scripts\.venv\Scripts\pip install -r scripts/requirements.txt
```

Probado con **Python 3.14.6** en Windows.

---

## Uso

```bash
scripts\.venv\Scripts\python scripts/prediccion_pesajes.py --arete OV-142
```

| Opción | Para qué |
|---|---|
| `--animal 12` | Por ID interno |
| `--arete OV-142` | Por número de arete |
| `--dias 90` | Horizonte a predecir (90 por omisión) |
| `--csv archivo.csv` | Leer de un CSV en vez de la base de datos |
| `--salida ruta.png` | Dónde guardar la gráfica |
| `--json ruta.json` | Guardar además el resultado en JSON |

Lee la conexión del `.env` del proyecto, así que no hay que configurar nada
aparte. Si MySQL está apagado, lo dice y sugiere el modo CSV.

### Sin base de datos

El CSV necesita las columnas `fecha` y `peso`; opcionalmente `animal_id` y
`arete` para filtrar:

```csv
animal_id,arete,fecha,peso
1,OV-142,2026-01-10,3.41
1,OV-142,2026-01-24,6.51
1,OV-142,2026-02-09,10.67
```

---

## Qué devuelve

```
  Pesajes:        12 en 200 días
  Ganancia media: 266 g/día
  Último tramo:   109 g/día

  Modelo elegido: Gompertz   R² = 0.9995   error típico ±0.41 kg

  Proyección:
     30 días (2026-08-28):   58.82 kg  ±0.47
     60 días (2026-09-27):   60.24 kg  ±0.53
     90 días (2026-10-27):   61.11 kg  ±0.60
```

Más un PNG en `scripts/salida/` con los pesajes reales, la curva ajustada, la
proyección y una línea que separa **lo medido de lo proyectado**.

**Ganancia media** es de toda su vida; **último tramo** es cómo va ahora. Cuando
la segunda cae muy por debajo de la primera, el animal está llegando a su techo.

---

## Cuando hay pocos pesajes

Con menos de 5 solo se ajusta la recta, y el script avisa **hacia dónde** se
equivoca, comparando la ganancia reciente con la media:

- **Acelerando** (gana más que su promedio): la recta se queda **corta**.
- **Frenando** (gana menos): la recta se **pasa**.

La dirección del error depende de la fase del animal, no del modelo. Un cordero
de 30 días proyectado con recta llega a 32 kg cuando la realidad son 42; un
animal cerca de su peso adulto proyectado igual llega a 66 kg cuando la realidad
son 61.

Con menos de 2 pesajes no se intenta nada: se dice y se sale.

---

## Límites

Esto es **estadística sobre los pesajes registrados**, no un modelo biológico.

- No sabe si cambió la ración, si hubo una enfermedad o si viene la seca.
- Extrapolar siempre es más frágil que interpolar: la banda de la gráfica se
  ensancha al alejarse del último dato real, como recordatorio.
- El margen mostrado es el error típico del ajuste, no un intervalo de confianza
  formal.
- Dos pesajes del mismo día se promedian: es una medición repetida, no dos
  puntos.

---

## Conectarlo con la aplicación

La opción `--json` deja el resultado en un formato que Laravel puede leer:

```json
{
  "modelo": "Gompertz",
  "r2": 0.9995,
  "gdp_promedio": 0.2656,
  "hitos": {
    "30": { "fecha": "2026-08-28", "peso": 58.82, "margen": 0.47 }
  },
  "grafica": "scripts/salida/pesajes_OV-142.png"
}
```

Con eso se puede mostrar la proyección en la ficha del ejemplar, junto al
historial de peso que ya existe. Dilo si quieres que lo integre.
