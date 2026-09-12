# Matemáticas financieras

Aplicativo para resolver problemas de matemáticas financieras, construido sobre
*Fundamentos de matemáticas financieras* de Carlos Ramírez Molinares, Milton
García Barbosa, Cristo Pantoja Algarín y Ariel Zambrano Meza (Universidad Libre,
Cartagena, 2009).

Material educativo de uso privado.

## Estado

**En desarrollo. El núcleo de cálculo no está verificado contra la totalidad de
los ejercicios del texto.** No lo use para cálculos reales de crédito sin
validarlo primero contra su propia fuente.

Lo que sí está comprobado: 29 de 31 valores publicados en el libro se reproducen
con diferencias por debajo del peso. Los dos casos restantes difieren por
redondeo del propio texto, no del código (ver más abajo).

Lo que falta:
- Los tests se escribieron pero nunca se ejecutaron con PHPUnit.
- Sin cobertura de moneda extranjera ni de interés simple.
- El cierre de las tablas largas acumula error de punto flotante.

## Qué resuelve

Tres herramientas que cubren los siete capítulos:

**Conversión de tasas.** Pasa cualquier tasa entre efectiva, nominal, anticipada
y continua, en cualquier periodo. El texto plantea dieciséis igualdades según la
combinación de origen y destino; acá hay un solo camino: toda tasa sube a
efectiva anual y de ahí baja a lo que se pida. El resultado es el mismo y no hay
dieciséis fórmulas que mantener.

**Tablas de amortización.** Cuota uniforme, cuotas extras pactadas y no pactadas,
periodo de gracia muerto y con cuota reducida, abono constante a capital con
interés vencido y anticipado, cuotas con gradiente aritmético o geométrico, y
deuda en moneda extranjera con ajuste por devaluación.

**Ecuaciones de valor.** El diagrama se arma flujo por flujo y se despeja el
monto, el tiempo o la tasa en cualquier fecha focal. Admite incógnitas con
coeficiente y término constante, de modo que "el 300% de X" o "X más 200.000"
se plantean sin casos especiales. También admite tasa que cambia por tramos.

## Instalación

Requiere PHP 8.4, Composer y Node.

Este repositorio contiene solo el código propio; se monta sobre una
instalación limpia de Laravel:

```bash
composer create-project laravel/laravel matematicas-financieras
cd matematicas-financieras
composer require inertiajs/inertia-laravel tightenco/ziggy
php artisan inertia:middleware
npm install --save-dev @inertiajs/vue3 vue @vitejs/plugin-vue \
  tailwindcss@^3 postcss autoprefixer @tailwindcss/forms ziggy-js
```

Luego copie encima `src/`, `app/`, `config/`, `routes/`, `resources/`,
`tests/`, `package.json`, `vite.config.js`, `tailwind.config.js`,
`postcss.config.js` y `phpunit.xml`; registre `App\Providers\FinMathServiceProvider`
en `bootstrap/providers.php` y agregue el autoload `"FinMath\\": "src/"` en
`composer.json`. Termine con `composer dump-autoload && npm run build`.

Las vistas usan el helper `route()` de [Ziggy](https://github.com/tighten/ziggy);
sin él, los `Link` del layout fallan.

Después:

```bash
cd matematicas-financieras
npm run dev        # en una terminal
php artisan serve  # en otra
```

## Estructura

```
src/                 Núcleo de cálculo, sin dependencias de Laravel
  Rate/              Tasas: objeto de valor, conversión, enums de periodo
  Simple/            Interés simple y descuentos
  Compound/          Interés compuesto y descuento compuesto
  Annuity/           Series uniformes y las tres salidas para n fraccionario
  Gradient/          Gradientes aritmético y geométrico
  Amortization/      Tabla y las siete estrategias de pago
  Equation/          Ecuaciones de valor, flujos y curva de tasas
  Exchange/          Devaluación, revaluación, inflación, UVR
  Solver/            Bisección con traza para reproducir la interpolación
app/                 Controladores, validación, servicios
resources/js/        Vue 3 con Inertia
tests/               Casos tomados de los ejemplos del texto
```

El directorio `src/` no depende de Laravel: se puede extraer como paquete y usar
desde la línea de comandos o desde otro framework.

## Decisiones de diseño

**Punto flotante, no decimales exactos.** Se evaluó `brick/math` con
`BigDecimal`. Con `float` las tablas de más de cien periodos cierran con un saldo
final de unos pocos pesos en vez de cero exacto. La interfaz avisa cuando eso
pasa, en lugar de esconderlo redondeando. Para un aplicativo de estudio es
aceptable; para liquidar créditos reales habría que migrar.

**Bisección en vez de interpolación lineal.** El texto usa interpolación porque
asume tablas financieras impresas. La bisección da la raíz exacta, pero el
resultado incluye las dos cotas que la encierran y el valor que daría la
interpolación, para poder comparar ambos métodos.

**La variación del gradiente entra con signo.** Un decreciente del 1,8% es
`j = -0.018`, no `0.018` con una fórmula aparte. Esto simplifica el núcleo pero
traslada la responsabilidad al formulario, que recibe magnitud y dirección por
separado y debe negar el valor. Es el punto más fácil de romper del proyecto y
tiene test propio.

## Diferencias con el texto

Dos casos donde el código y el libro no coinciden. Ninguno es un error del
código.

**Ejemplo 7.1.** El enunciado dice 36% capitalizable trimestralmente, y el texto
escribe `i = 0,36/4 = 8 trimestral`. Pero 0,36/4 es 0,09, no 0,08. La tabla
publicada usa 8% (el interés del primer periodo es 600.000 × 0,08 = 48.000), así
que la cuota de 104.408,86 corresponde a 8%. Es un error aritmético del libro. El
test reproduce la tabla publicada con 8%; si usted entra "36% CT" en el
aplicativo, obtendrá 9% y una cuota distinta, que es la correcta.

**Ejemplo 6.28.** El texto redondea el factor del gradiente a 11,4261 y divide
sobre ese valor, con lo que la primera cuota da 1.750.378,52. Con el factor
completo (11,42608275) da 1.750.381,16. La diferencia de 2,64 pesos se propaga al
saldo. Los tests usan tolerancia amplia en este caso y lo explican en un
comentario.

## Verificación

`verify.py` en la raíz reimplementa las fórmulas en Python y las compara contra
los valores publicados. Sirve como control cruzado independiente del código PHP:
si ambos dan lo mismo, es poco probable que el error esté en la fórmula.

```bash
python3 verify.py
```

## Licencia

Material educativo de uso privado. El libro base permite reproducción total o
parcial citando la fuente, los autores y las instituciones; esa atribución está
en el pie de todas las páginas del aplicativo.
