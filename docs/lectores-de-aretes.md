# Lectores de aretes — guía para el cliente

Rastro Claro no exige ninguna marca de lector. Funciona con cualquiera que
cumpla la norma internacional y sepa comportarse como teclado. Esta guía dice
qué pedir al comprar y qué hacer si algo no lee.

---

## Qué exigir al comprar

Al pedir cotización, que la ficha técnica diga estas tres cosas:

| Requisito | Por qué |
|---|---|
| **ISO 11784 / 11785** | Es la norma de identificación animal. Sin ella, el código que devuelve no sirve para el padrón. |
| **HDX y FDX-B** | Son las dos formas de transmisión del arete. Un lector que solo lee una **no detecta** los aretes de la otra. |
| **Modo teclado (HID)** | Hace que el lector funcione sin instalar nada ni configurar puertos. |

Un lector que cumple las tres funciona el primer día, sin ajustes.

### Sobre HDX y FDX-B

Son dos maneras de que el arete le conteste al lector. La diferencia se
resuelve **dentro del lector**: con cualquiera de las dos, al sistema llega el
mismo código.

Esto importa por una razón práctica: **si el rebaño mezcla aretes de las dos
tecnologías y el lector solo soporta una, la mitad de los animales parecerán no
existir.** No es un fallo del sistema y ningún cambio de software lo corrige.

Por eso el sistema permite anotar qué tecnología trae cada ejemplar: si un día
un lector deja de detectar a ciertos animales, el dato está a la mano.

---

## Cómo se conecta

### Modo teclado — el recomendado

El lector se comporta como un teclado: al pasar por un arete, "escribe" el
código donde esté el cursor y cierra con Enter.

- No necesita instalar nada
- Funciona en cualquier computadora y en cualquier navegador
- No pide permisos

Casi todos los lectores traen este modo. Suele activarse con una tarjeta de
configuración que viene en la caja, o con un comando del manual.

**Cómo comprobarlo sin el sistema:** abre un bloc de notas, haz clic dentro y
pasa el lector por un arete. Si el número aparece solo, está en modo teclado.

### Por cable — el respaldo

Para lectores que no ofrecen modo teclado. El sistema abre el puerto serie
directamente.

- Solo funciona en **Chrome** o **Edge**
- Requiere que el sitio esté en **HTTPS**
- El usuario debe autorizar el puerto la primera vez

Cubre los lectores que envían el código como texto. Los que usan un protocolo
binario propio necesitan trabajo específico: se resuelve caso por caso, con el
manual del equipo.

---

## Si no lee

Antes de reportar nada, entra a **Diagnóstico del lector** dentro del sistema y
pasa un arete. Esa pantalla muestra exactamente qué llegó y casi siempre
identifica el problema sola.

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| No aparece nada | El lector no está en modo teclado | Revisa el manual. Comprueba primero en un bloc de notas. |
| Aparece pero dice «parece escrito a mano» | Llegó demasiado lento | Se tecleó en vez de escanear. |
| Salen caracteres de más | El lector añade un prefijo o sufijo | El sistema descarta espacios y saltos de línea solo. Si hay letras antes del número, anótalo y repórtalo. |
| Salen menos de 15 dígitos | Lectura incompleta, o arete sin electrónica | Acerca más el lector. Si el arete es solo visual, captura el número a mano. |
| Unos aretes leen y otros no | El lector no soporta las dos tecnologías | Limitación del equipo. Hace falta uno que declare HDX **y** FDX-B. |

Si nada de eso resuelve, usa el botón **Copiar** de la pantalla de diagnóstico y
envía ese texto: contiene todo lo necesario para atender el caso sin pedir más
datos.

---

## Ajustes para lectores poco comunes

**Herramientas → Ajustes del lector.** Los edita el dueño del rancho.

La mayoría no necesita entrar aquí: el sistema descarta solo los espacios, los
saltos de línea y cualquier letra que el lector añada alrededor del número.

Hace falta configurarlo en un caso concreto: cuando el lector antepone o añade
**dígitos** que no son parte del código. Ahí la limpieza automática no puede
ayudar, porque no hay forma de distinguir un cero añadido por el equipo de un
cero del propio código.

| Ajuste | Para qué |
|---|---|
| Descartar al inicio | Texto fijo que el lector antepone |
| Descartar al final | Texto fijo que añade al terminar |
| Longitud esperada | Si el código no son los 15 dígitos de la norma |
| Descartar lo que no sea dígito | Último recurso: **borra letras**, así que perderías los aretes internos y alias |
| Velocidad del puerto | Solo para conexiones por cable; debe coincidir con la del lector |

La pantalla trae un **probador**: escribes una lectura, ves cómo quedaría con los
valores puestos, y solo entonces guardas. Nada se aplica al rancho hasta que
pulsas Guardar.

Conviene anotar la **marca y modelo** del lector en esa misma pantalla. Cuando
algo falle, ese dato ahorra media conversación.

---

## Qué guarda el sistema de cada ejemplar

Tres identificadores distintos, que conviene no confundir:

| Campo | Qué es | Ejemplo |
|---|---|---|
| **Arete interno** | Tu propia numeración | `OV-142` |
| **Arete visual SINIIGA** | El número impreso en el arete oficial | `0712345678` |
| **Código electrónico** | Los 15 dígitos que devuelve el lector | `484 000123456789` |

La búsqueda encuentra al ejemplar por **cualquiera** de los tres, y también por
su alias o su código QR.

### Cómo se lee el código electrónico

```
484 000123456789
└┬┘ └─────┬────┘
 │        └── número del animal (12 dígitos)
 └── país: 484 es México
```

Un código que empieza en **900 o más** no identifica un país sino al fabricante
del arete: ese ejemplar no proviene del padrón nacional. El sistema lo acepta,
pero lo advierte para que nadie lo dé por registrado sin serlo.

Un código de otro país —un animal importado— también se acepta, con aviso.
