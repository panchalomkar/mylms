<?php
$addons = array(
    "mod_videofile" => array( // Plugin identifier
        'handlers' => array( // Different places where the plugin will display content.
            'coursevideofile' => array( // Handler unique name (alphanumeric).
                'displaydata' => array(
                        'icon' => $CFG->wwwroot . '/mod/videofile/pix/icon.gif',
                        'class' => 'videofile'
                ),

                'delegate' => 'CoreCourseModuleDelegate', // Delegate (where to display the link to the plugin)
                'method' => 'mobile_video_view', // Main function in \mod_videofile\output\mobile
                'isresource' => true,
                'styles' => [
                        'url' => $CFG->wwwroot . '/mod/videofile/mobile.css',
                        'version' => 3
                    ]
            )
        ),
        'lang' => array( // Language strings that are used in all the handlers.
                    array('pluginname', 'certificate'),
                    array('summaryofattempts', 'certificate'),
                    array('getcertificate', 'certificate'),
                    array('requiredtimenotmet', 'certificate'),
                    array('viewcertificateviews', 'certificate')
        ),
    )
);
