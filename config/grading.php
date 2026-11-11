<?php

return [
    // 1.00 is the highest grade. 4.00 is the lowest and the last grade.
    'best'    => 1.00,
    'worst'   => 4.00,

    // 3.00 is the lowest passing grade. 3.01 to 4.00 is failed.
    'passing' => 3.00,

    // Decimals kept when averaging the periods
    'decimals' => 2,

    'periods' => [
        'prelim'   => 'Prelim',
        'midterm'  => 'Midterm',
        'prefinal' => 'Pre-Final',
        'finals'   => 'Final',
    ],

    // ONLY used by "Use exam scores as grades": exam percentage becomes a grade.
    // [minimum percentage, grade]. Edit this to match your school's transmutation table.
    'transmute' => [
        [97, 1.00], [94, 1.25], [91, 1.50], [88, 1.75], [85, 2.00],
        [82, 2.25], [79, 2.50], [76, 2.75], [75, 3.00], [65, 3.50], [0, 4.00],
    ],
];