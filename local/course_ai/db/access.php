<?php



// $capabilities = [
//     // Capability to view sections
//     'local/course_ai:viewcourse' => [
//         'captype' => 'read', // Type of capability: read/write
//         'contextlevel' => CONTEXT_SYSTEM, // Context level: system-wide
//         'archetypes' => [
//             'manager' => CAP_ALLOW, // Default role with access
//             'student' => CAP_ALLOW,
//         ],
//     ],

// ];

$capabilities = array(
    'local/course_ai:viewcourse' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,  // Managers can always manage questions
            'teacher' => CAP_ALLOW,  // Teachers can manage questions in their courses
            'student' => CAP_PREVENT,  // Students cannot manage questions
        ),
    ),
);

