<?php

return [
    // 1.00 is the highest grade. 4.00 is the lowest and the last grade.
    'best'    => 1.00,
    'worst'   => 4.00,

    // 3.00 is the lowest passing grade. 3.01 to 4.00 is failed.
    'passing' => 3.00,

    // Decimals kept in averages
    'decimals' => 1,

    // The grading periods, in the order they appear
    'periods' => [
        'midterm' => 'Midterm',
        'finals'  => 'Final',
    ],

    // What you type in a Midterm or Final cell for an incomplete grade
    'inc' => 'INC',

    // Automatic remarks, from the average of Midterm and Final.
    // [highest average for this description, description], checked from the top.
    // Averages show one decimal, so an exact 1.25 appears as 1.3 and an exact 1.75 as 1.8.
    'remarks' => [
        [1.0, 'Excellent'],
        [1.3, 'Superior'],
        [1.5, 'Very Good'],
        [1.8, 'Good'],
        [2.0, 'Very Satisfactory'],
        [2.5, 'Satisfactory'],
        [3.0, 'Passed'],
    ],
    'failed_remark' => 'Failed',        // anything above the last limit (3.1 to 4.00)
    'inc_remark'    => 'Incomplete',

    // ONLY used by "Use exam scores as grades": exam percentage becomes a grade.
    // [minimum percentage, grade]. Edit this to match your school's transmutation table.
    'transmute' => [
        [97, 1.00], [94, 1.25], [91, 1.50], [88, 1.75], [85, 2.00],
        [82, 2.25], [79, 2.50], [76, 2.75], [75, 3.00], [65, 3.50], [0, 4.00],
    ],
];