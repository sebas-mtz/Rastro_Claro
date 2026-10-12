#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Predicción de pesajes de ovinos — Rastro Claro
==============================================

Ajusta varios modelos de crecimiento a los pesajes de un ejemplar, elige el que
mejor describe SUS datos y proyecta el peso futuro, con gráfica.

Por qué no solo regresión lineal
--------------------------------
Un animal no crece en línea recta. Crece rápido de joven, se frena al acercarse
a su peso adulto y termina en una meseta: la curva es sigmoide. Una recta
ajustada a los primeros meses proyecta borregas de 200 kg a los dos años.

Pero la recta tampoco sobra: en tramos cortos —las semanas de engorda, que es
donde se decide vender— describe muy bien el crecimiento, y su pendiente es la
ganancia diaria de peso (GDP), la cifra con la que de verdad se trabaja.

Por eso el script ajusta cuatro modelos y compara:

  · Lineal            peso = a + b·días
  · Gompertz          el estándar en zootecnia ovina
  · Logístico         sigmoide simétrica
  · von Bertalanffy   sigmoide de saturación temprana

y se queda con el mejor según AICc, que premia el ajuste pero castiga meter
parámetros de más. Con pocos pesajes eso favorece a la recta, que es lo
correcto: con tres puntos no se puede afirmar dónde está la meseta.

Uso
---
    python prediccion_pesajes.py --animal 12
    python prediccion_pesajes.py --arete OV-142 --dias 90
    python prediccion_pesajes.py --csv pesajes.csv --animal 12
    python prediccion_pesajes.py --animal 12 --json salida.json

El CSV debe traer columnas: animal_id, fecha, peso   (y opcionalmente arete)
"""

from __future__ import annotations

import argparse
import json
import math
import os
import sys
from dataclasses import dataclass, asdict
from pathlib import Path

import numpy as np
import pandas as pd

# Backend sin ventana: el script se ejecuta en servidores sin escritorio.
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.dates import DateFormatter

from scipy.optimize import curve_fit

RAIZ = Path(__file__).resolve().parent.parent

# La consola de Windows usa cp1252 por omisión y destroza los acentos.
# `errors="replace"` evita que un carácter raro tumbe el script entero.
for flujo in (sys.stdout, sys.stderr):
    try:
        flujo.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, ValueError):
        # Salida redirigida a un archivo o a otro proceso: ya viene fijada.
        pass

# Peso adulto de referencia de una oveja de pelo. Solo se usa como punto de
# partida del ajuste, nunca como resultado: el modelo lo corrige con los datos.
PESO_ADULTO_TIPICO = 65.0

# Con menos de este número de pesajes no se intenta una sigmoide: haría falta
# inventar dónde está la meseta, y eso no es predecir, es adivinar.
MINIMO_PARA_SIGMOIDE = 5


# ─────────────────────────────────────────────────────────────────────────────
# Modelos de crecimiento
# ─────────────────────────────────────────────────────────────────────────────

def lineal(t, a, b):
    """Recta. `b` es la ganancia diaria de peso en kg/día."""
    return a + b * t


def gompertz(t, A, b, k):
    """
    A = peso asintótico (adulto), b = escala inicial, k = tasa de madurez.

    Es el modelo más usado para curvas de crecimiento en ovinos: su punto de
    inflexión cae antes de la mitad del peso adulto, que es como crecen de
    verdad los corderos.
    """
    return A * np.exp(-b * np.exp(-k * t))


def logistico(t, A, b, k):
    """Sigmoide simétrica: inflexión justo en la mitad del peso adulto."""
    return A / (1.0 + b * np.exp(-k * t))


def von_bertalanffy(t, A, b, k):
    """Sigmoide con saturación temprana, habitual en crecimiento corporal."""
    return A * np.power(1.0 - b * np.exp(-k * t), 3)


@dataclass
class Ajuste:
    nombre: str
    params: list[float]
    r2: float
    rmse: float
    aicc: float
    funcion: object = None

    def predecir(self, t):
        return self.funcion(np.asarray(t, dtype=float), *self.params)


def _aicc(y, y_pred, n_params: int) -> float:
    """
    Criterio de Akaike corregido para muestras pequeñas.

    Con los cinco o diez pesajes que suele tener un animal, el AIC normal se
    queda corto y premia modelos demasiado complejos; la corrección lo evita.
    Devuelve infinito cuando no hay grados de libertad suficientes, de modo que
    ese modelo queda descartado en la comparación.
    """
    n = len(y)
    rss = float(np.sum((y - y_pred) ** 2))

    if n <= n_params + 1:
        return math.inf

    # Un ajuste perfecto daría log(0); se acota para no romper la comparación.
    rss = max(rss, 1e-12)

    aic = n * math.log(rss / n) + 2 * n_params
    return aic + (2 * n_params * (n_params + 1)) / (n - n_params - 1)


def ajustar(nombre, funcion, t, y, p0, bounds, n_params) -> Ajuste | None:
    """Intenta un modelo. Devuelve None si no converge o da algo absurdo."""
    try:
        params, _ = curve_fit(funcion, t, y, p0=p0, bounds=bounds, maxfev=20000)
    except (RuntimeError, ValueError):
        # No convergió: es información, no un error. Simplemente ese modelo
        # no describe estos datos y se queda fuera de la comparación.
        return None

    y_pred = funcion(t, *params)

    if not np.all(np.isfinite(y_pred)):
        return None

    ss_res = float(np.sum((y - y_pred) ** 2))
    ss_tot = float(np.sum((y - np.mean(y)) ** 2))
    r2 = 1.0 - ss_res / ss_tot if ss_tot > 0 else 0.0

    return Ajuste(
        nombre=nombre,
        params=[float(p) for p in params],
        r2=r2,
        rmse=float(np.sqrt(ss_res / len(y))),
        aicc=_aicc(y, y_pred, n_params),
        funcion=funcion,
    )


def elegir_modelo(t: np.ndarray, y: np.ndarray) -> tuple[Ajuste, list[Ajuste]]:
    """Ajusta todo lo que tenga sentido con estos datos y devuelve el mejor."""
    candidatos: list[Ajuste] = []

    recta = ajustar(
        "Lineal", lineal, t, y,
        p0=[float(y[0]), 0.15],
        bounds=([-np.inf, -np.inf], [np.inf, np.inf]),
        n_params=2,
    )
    if recta:
        candidatos.append(recta)

    if len(t) >= MINIMO_PARA_SIGMOIDE:
        techo = max(float(np.max(y)) * 1.15, PESO_ADULTO_TIPICO)

        for nombre, funcion, p0, bounds in (
            ("Gompertz", gompertz,
             [techo, 2.0, 0.01],
             ([float(np.max(y)), 1e-6, 1e-6], [techo * 3, 50, 1.0])),
            ("Logístico", logistico,
             [techo, 5.0, 0.01],
             ([float(np.max(y)), 1e-6, 1e-6], [techo * 3, 500, 1.0])),
            ("von Bertalanffy", von_bertalanffy,
             [techo, 0.7, 0.01],
             ([float(np.max(y)), 1e-6, 1e-6], [techo * 3, 0.999, 1.0])),
        ):
            fit = ajustar(nombre, funcion, t, y, p0, bounds, n_params=3)
            if fit:
                candidatos.append(fit)

    if not candidatos:
        raise RuntimeError("Ningún modelo pudo ajustarse a estos pesajes.")

    candidatos.sort(key=lambda a: a.aicc)
    return candidatos[0], candidatos


# ─────────────────────────────────────────────────────────────────────────────
# Datos
# ─────────────────────────────────────────────────────────────────────────────

def leer_env() -> dict:
    """Lee la conexión del .env del proyecto, sin depender de Laravel."""
    from dotenv import dotenv_values

    env = dotenv_values(RAIZ / ".env")

    return {
        "host": env.get("DB_HOST", "127.0.0.1"),
        "port": int(env.get("DB_PORT", 3306) or 3306),
        "user": env.get("DB_USERNAME", "root"),
        "password": env.get("DB_PASSWORD") or "",
        "database": env.get("DB_DATABASE", "rastro_claro"),
    }


def cargar_desde_mysql(animal_id=None, arete=None) -> tuple[pd.DataFrame, dict]:
    import pymysql

    cfg = leer_env()

    try:
        conexion = pymysql.connect(charset="utf8mb4", **cfg)
    except pymysql.err.OperationalError as e:
        raise SystemExit(
            f"\nNo se pudo conectar a MySQL en {cfg['host']}:{cfg['port']}.\n"
            f"  {e}\n\n"
            "Arranca MySQL (XAMPP o Laragon) y vuelve a intentarlo, o usa un\n"
            "archivo exportado:  --csv pesajes.csv\n"
        )

    with conexion:
        with conexion.cursor(pymysql.cursors.DictCursor) as cur:
            if arete:
                cur.execute(
                    "SELECT id, arete, alias, fecha_nac FROM animals WHERE arete = %s LIMIT 1",
                    (arete,),
                )
            else:
                cur.execute(
                    "SELECT id, arete, alias, fecha_nac FROM animals WHERE id = %s LIMIT 1",
                    (animal_id,),
                )

            animal = cur.fetchone()

            if not animal:
                raise SystemExit(
                    f"No existe el ejemplar {arete or animal_id} en la base de datos."
                )

            cur.execute(
                "SELECT fecha, peso FROM pesajes WHERE animal_id = %s ORDER BY fecha",
                (animal["id"],),
            )
            filas = cur.fetchall()

    return pd.DataFrame(filas), animal


def cargar_desde_csv(ruta, animal_id=None, arete=None) -> tuple[pd.DataFrame, dict]:
    df = pd.read_csv(ruta)

    faltan = {"fecha", "peso"} - set(df.columns)
    if faltan:
        raise SystemExit(f"Al CSV le faltan columnas: {', '.join(sorted(faltan))}")

    if arete and "arete" in df.columns:
        df = df[df["arete"].astype(str) == str(arete)]
        etiqueta = arete
    elif animal_id is not None and "animal_id" in df.columns:
        df = df[df["animal_id"].astype(str) == str(animal_id)]
        etiqueta = str(animal_id)
    else:
        etiqueta = Path(ruta).stem

    return df[["fecha", "peso"]].copy(), {"id": animal_id, "arete": etiqueta,
                                          "alias": None, "fecha_nac": None}


def preparar(df: pd.DataFrame) -> pd.DataFrame:
    """Normaliza, ordena y promedia los pesajes del mismo día."""
    df = df.copy()
    df["fecha"] = pd.to_datetime(df["fecha"], errors="coerce")
    df["peso"] = pd.to_numeric(df["peso"], errors="coerce")

    df = df.dropna(subset=["fecha", "peso"])
    df = df[df["peso"] > 0]

    # Dos básculas el mismo día no son dos puntos: es uno, medido dos veces.
    df = df.groupby("fecha", as_index=False)["peso"].mean()

    return df.sort_values("fecha").reset_index(drop=True)


# ─────────────────────────────────────────────────────────────────────────────
# Análisis
# ─────────────────────────────────────────────────────────────────────────────

def analizar(df: pd.DataFrame, dias_futuro: int) -> dict:
    fechas = df["fecha"]
    origen = fechas.iloc[0]

    t = (fechas - origen).dt.days.to_numpy(dtype=float)
    y = df["peso"].to_numpy(dtype=float)

    mejor, todos = elegir_modelo(t, y)

    # Ganancia diaria observada, medida punto a punto sobre los datos reales.
    # No sale del modelo: es lo que de hecho pasó.
    gdp_real = float((y[-1] - y[0]) / (t[-1] - t[0])) if t[-1] > t[0] else None

    # Ganancia del último tramo: dice cómo va AHORA, que suele importar más
    # que el promedio de toda su vida.
    gdp_reciente = None
    if len(y) >= 2 and t[-1] > t[-2]:
        gdp_reciente = float((y[-1] - y[-2]) / (t[-1] - t[-2]))

    t_futuro = np.arange(t[-1] + 1, t[-1] + dias_futuro + 1, dtype=float)
    y_futuro = mejor.predecir(t_futuro)

    # Banda de incertidumbre: el error típico del ajuste, ensanchándose al
    # alejarse del último dato real. No es un intervalo de confianza formal;
    # es un recordatorio visual de que extrapolar tiene coste.
    distancia = (t_futuro - t[-1]) / max(t[-1] - t[0], 1.0)
    margen = mejor.rmse * (1.0 + distancia)

    fechas_futuro = [origen + pd.Timedelta(days=int(d)) for d in t_futuro]

    # Hitos habituales, más el horizonte que se pidió: sin esto, pedir 120
    # días mostraba la gráfica hasta 120 pero la tabla se quedaba en 90.
    marcas = sorted({d for d in (30, 60, 90, 180) if d <= dias_futuro} | {dias_futuro})

    hitos = {}
    for d in marcas:
        peso = float(mejor.predecir([t[-1] + d])[0])
        hitos[d] = {
            "fecha": (origen + pd.Timedelta(days=int(t[-1] + d))).strftime("%Y-%m-%d"),
            "peso": round(peso, 2),
            "margen": round(float(mejor.rmse * (1.0 + d / max(t[-1] - t[0], 1.0))), 2),
        }

    return {
        "n_pesajes": len(y),
        "periodo_dias": int(t[-1]),
        "peso_inicial": float(y[0]),
        "peso_actual": float(y[-1]),
        "fecha_inicial": origen.strftime("%Y-%m-%d"),
        "fecha_actual": fechas.iloc[-1].strftime("%Y-%m-%d"),
        "gdp_promedio": round(gdp_real, 4) if gdp_real is not None else None,
        "gdp_reciente": round(gdp_reciente, 4) if gdp_reciente is not None else None,
        "modelo": mejor.nombre,
        "r2": round(mejor.r2, 4),
        "rmse": round(mejor.rmse, 3),
        "comparacion": [
            {"modelo": a.nombre, "r2": round(a.r2, 4), "rmse": round(a.rmse, 3),
             "aicc": round(a.aicc, 2) if math.isfinite(a.aicc) else None}
            for a in todos
        ],
        "hitos": hitos,
        # Series para la gráfica y para quien quiera consumirlas desde fuera.
        "_t": t, "_y": y, "_origen": origen,
        "_t_futuro": t_futuro, "_y_futuro": y_futuro, "_margen": margen,
        "_fechas_futuro": fechas_futuro, "_mejor": mejor, "_todos": todos,
    }


# ─────────────────────────────────────────────────────────────────────────────
# Gráfica
# ─────────────────────────────────────────────────────────────────────────────

def graficar(res: dict, animal: dict, salida: Path) -> Path:
    origen = res["_origen"]
    t, y = res["_t"], res["_y"]
    mejor = res["_mejor"]

    fechas_reales = [origen + pd.Timedelta(days=int(d)) for d in t]

    # Curva ajustada sobre el tramo con datos, en fino, para ver si el modelo
    # realmente sigue a los puntos o los está atravesando de lado.
    t_suave = np.linspace(t[0], t[-1], 200)
    y_suave = mejor.predecir(t_suave)
    fechas_suave = [origen + pd.Timedelta(days=float(d)) for d in t_suave]

    fig, ax = plt.subplots(figsize=(11, 6))

    ax.fill_between(
        res["_fechas_futuro"],
        res["_y_futuro"] - res["_margen"],
        res["_y_futuro"] + res["_margen"],
        color="#10b981", alpha=0.15, label="Margen de error",
    )

    ax.plot(fechas_suave, y_suave, color="#0ea5e9", lw=2,
            label=f"Modelo {mejor.nombre} (R²={mejor.r2:.3f})")

    ax.plot(res["_fechas_futuro"], res["_y_futuro"], color="#10b981",
            lw=2, ls="--", label=f"Predicción {len(res['_t_futuro'])} días")

    ax.plot(fechas_reales, y, "o", color="#1e293b", ms=7,
            zorder=5, label="Pesajes registrados")

    # Línea que separa lo medido de lo proyectado: la distinción más
    # importante de toda la gráfica.
    ax.axvline(fechas_reales[-1], color="#94a3b8", ls=":", lw=1.5)
    ax.annotate("hoy", xy=(fechas_reales[-1], ax.get_ylim()[0]),
                xytext=(4, 6), textcoords="offset points",
                fontsize=9, color="#64748b")

    for dias, hito in res["hitos"].items():
        fecha = pd.to_datetime(hito["fecha"])
        ax.annotate(
            f"{hito['peso']:.1f} kg",
            xy=(fecha, hito["peso"]),
            xytext=(0, 10), textcoords="offset points",
            ha="center", fontsize=9, color="#047857", fontweight="bold",
        )
        ax.plot([fecha], [hito["peso"]], "o", color="#10b981", ms=5)

    nombre = animal.get("alias") or animal.get("arete") or "Ejemplar"
    gdp = res["gdp_promedio"]

    titulo = f"Curva de crecimiento — {nombre}"
    if gdp is not None:
        titulo += (f"\n{res['n_pesajes']} pesajes en {res['periodo_dias']} días · "
                   f"GDP {gdp * 1000:.0f} g/día")

    ax.set_title(titulo, fontsize=13, fontweight="bold", pad=15)

    ax.set_xlabel("Fecha")
    ax.set_ylabel("Peso (kg)")
    ax.grid(alpha=0.25, ls="--")
    ax.legend(loc="upper left", framealpha=0.95)
    ax.xaxis.set_major_formatter(DateFormatter("%d/%m/%y"))
    fig.autofmt_xdate()

    fig.text(
        0.99, 0.02,
        "Proyección estadística sobre los pesajes registrados.\n"
        "No considera cambios de alimentación, sanidad ni clima.",
        ha="right", va="bottom", fontsize=8, color="#94a3b8",
    )

    fig.tight_layout()
    fig.savefig(salida, dpi=140, bbox_inches="tight")
    plt.close(fig)

    return salida


# ─────────────────────────────────────────────────────────────────────────────
# Salida por consola
# ─────────────────────────────────────────────────────────────────────────────

def informar(res: dict, animal: dict, grafica: Path) -> None:
    nombre = animal.get("alias") or animal.get("arete") or animal.get("id")

    print()
    print("=" * 62)
    print(f"  Predicción de peso — {nombre}")
    print("=" * 62)
    print(f"  Pesajes:        {res['n_pesajes']} en {res['periodo_dias']} días")
    print(f"  Del             {res['fecha_inicial']}  ({res['peso_inicial']:.1f} kg)")
    print(f"  Al              {res['fecha_actual']}  ({res['peso_actual']:.1f} kg)")

    if res["gdp_promedio"] is not None:
        print(f"  Ganancia media: {res['gdp_promedio'] * 1000:.0f} g/día")
    if res["gdp_reciente"] is not None:
        print(f"  Último tramo:   {res['gdp_reciente'] * 1000:.0f} g/día")

    print()
    print(f"  Modelo elegido: {res['modelo']}   R² = {res['r2']:.4f}   "
          f"error típico ±{res['rmse']:.2f} kg")

    if len(res["comparacion"]) > 1:
        print()
        print("  Modelos probados (menor AICc = mejor):")
        for c in res["comparacion"]:
            aicc = f"{c['aicc']:>8.2f}" if c["aicc"] is not None else "      —"
            print(f"    {c['modelo']:<16} R²={c['r2']:.4f}  "
                  f"RMSE={c['rmse']:.2f}  AICc={aicc}")

    if res["hitos"]:
        print()
        print("  Proyección:")
        for dias, h in res["hitos"].items():
            print(f"    {dias:>3} días ({h['fecha']}):  "
                  f"{h['peso']:6.2f} kg  ±{h['margen']:.2f}")

    print()
    print(f"  Gráfica: {grafica}")
    print("=" * 62)
    print()


# ─────────────────────────────────────────────────────────────────────────────

def main() -> int:
    p = argparse.ArgumentParser(
        description="Predice el peso futuro de un ovino a partir de sus pesajes.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__.split("Uso\n---")[-1],
    )
    grupo = p.add_mutually_exclusive_group(required=True)
    grupo.add_argument("--animal", type=int, help="ID del ejemplar")
    grupo.add_argument("--arete", type=str, help="Número de arete")

    p.add_argument("--dias", type=int, default=90,
                   help="Días a predecir (por omisión 90)")
    p.add_argument("--csv", type=str,
                   help="Leer de un CSV en vez de la base de datos")
    p.add_argument("--salida", type=str,
                   help="Ruta del PNG (por omisión scripts/salida/)")
    p.add_argument("--json", type=str,
                   help="Guardar también el resultado en JSON")

    args = p.parse_args()

    if args.dias < 1:
        print("Los días a predecir deben ser al menos 1.", file=sys.stderr)
        return 1

    if args.csv:
        df, animal = cargar_desde_csv(args.csv, args.animal, args.arete)
    else:
        df, animal = cargar_desde_mysql(args.animal, args.arete)

    df = preparar(df)

    if len(df) < 2:
        etiqueta = animal.get("arete") or animal.get("id")
        print(
            f"\nEl ejemplar {etiqueta} tiene {len(df)} pesaje(s) utilizables.\n"
            "Hacen falta al menos 2 para estimar cómo crece.\n"
            "Con 5 o más se pueden ajustar además las curvas sigmoides, que\n"
            "describen mejor el crecimiento completo.\n",
            file=sys.stderr,
        )
        return 1

    res = analizar(df, args.dias)

    destino = Path(args.salida) if args.salida else RAIZ / "scripts" / "salida" / \
        f"pesajes_{animal.get('arete') or animal.get('id')}.png"
    destino.parent.mkdir(parents=True, exist_ok=True)

    graficar(res, animal, destino)
    informar(res, animal, destino)

    if args.json:
        publico = {k: v for k, v in res.items() if not k.startswith("_")}
        publico["animal"] = {
            "id": animal.get("id"),
            "arete": animal.get("arete"),
            "alias": animal.get("alias"),
        }
        publico["grafica"] = str(destino)

        Path(args.json).parent.mkdir(parents=True, exist_ok=True)
        Path(args.json).write_text(
            json.dumps(publico, indent=2, ensure_ascii=False, default=str),
            encoding="utf-8",
        )
        print(f"  JSON: {args.json}\n")

    if len(df) < MINIMO_PARA_SIGMOIDE:
        avisar_limite_de_la_recta(res, len(df))

    return 0


def avisar_limite_de_la_recta(res: dict, n: int) -> None:
    """
    Explica hacia dónde se equivoca la recta con este animal en concreto.

    El sentido del error depende de la fase de crecimiento, no del modelo:

      · Cordero acelerando (aún no llega a su punto de inflexión):
        la recta se queda CORTA, porque el animal cada día gana más.
      · Animal cerca de su peso adulto:
        la recta se PASA, porque proyecta un crecimiento que ya se frenó.

    Se distingue comparando la ganancia del último tramo con la media: si va
    ganando más que su promedio, sigue acelerando.
    """
    media = res["gdp_promedio"]
    reciente = res["gdp_reciente"]

    print(f"  Nota: con {n} pesajes solo se pudo ajustar la recta. El crecimiento")
    print("  real es una curva que se aplana, así que a más de 60 días conviene")
    print("  tomar la proyección con reserva.")

    if media is not None and reciente is not None:
        if reciente > media * 1.1:
            print("  Este ejemplar viene ACELERANDO (gana más que su promedio):")
            print("  la recta probablemente se queda CORTA.")
        elif reciente < media * 0.9:
            print("  Este ejemplar viene FRENANDO (gana menos que su promedio):")
            print("  la recta probablemente se PASA.")
        else:
            print("  Crece a ritmo parejo; la recta es razonable en el corto plazo.")

    print("  Registra más pesajes para poder ajustar la curva completa.\n")


if __name__ == "__main__":
    sys.exit(main())
