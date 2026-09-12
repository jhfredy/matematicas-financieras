<?php

return [
    // Tolerancia de la bisección. El cálculo interno nunca redondea.
    'tolerance' => 1e-10,
    'max_iterations' => 200,

    // Decimales solo de presentación
    'display_decimals' => 2,

    // Tope de filas para no reventar la vista con créditos a 30 años
    'max_schedule_rows' => 480,

    // Umbral en pesos a partir del cual se avisa del arrastre de redondeo
    'closing_error_threshold' => 1.0,
];
