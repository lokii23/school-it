<?php

return [
    // 1.0 is the highest grade. 4.0 is the lowest and the last grade.
    'best'    => 1.00,
    'worst'   => 4.00,

    // 3.0 is the lowest passing grade. 3.1 to 4.0 is failed.
    'passing' => 3.00,

    // Decimals in a grade (Midterm, Final) and in an average
    'grade_decimals' => 1,
    'decimals'       => 1,

    // How many year levels the program has (4 = 1st to 4th year)
    'year_levels' => 4,

    // The grading periods, in the order they appear
    'periods' => [
        'midterm' => 'Midterm',
        'finals'  => 'Final',
    ],

    // What you type in a Midterm or Final cell for an incomplete grade
    'inc' => 'INC',

    // Automatic remarks, from the average of Midterm and Final.
    // [highest average for this description, description], checked from the top.
    'remarks' => [
        [1.0, 'Excellent'],
        [1.3, 'Superior'],
        [1.5, 'Very Good'],
        [1.8, 'Good'],
        [2.0, 'Very Satisfactory'],
        [2.5, 'Satisfactory'],
        [3.0, 'Pass'],
    ],
    'failed_remark' => 'Failed',        // anything above the last limit (3.1 to 4.0)
    'inc_remark'    => 'Incomplete',

    // ONLY used by "Use exam scores as grades": exam percentage becomes a grade.
    // [minimum percentage, grade]. Edit this to match your school's transmutation table.
    'transmute' => [
        [97, 1.0], [94, 1.3], [91, 1.5], [88, 1.8], [85, 2.0],
        [82, 2.3], [79, 2.5], [76, 2.8], [75, 3.0], [65, 3.5], [0, 4.0],
    ],
];