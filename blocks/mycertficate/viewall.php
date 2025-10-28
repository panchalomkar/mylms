<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$PAGE->set_url(new moodle_url('/blocks/mycertificate/viewall.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title("My Certificates");

function time_ago_local($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 86400) return "Issued Today";
    $days = floor($diff / 86400);
    return $days == 1 ? "1 day ago" : "$days days ago";
}

$certificates = [];

// --- Fetch Custom Certificates ---
$customcerts = $DB->get_records('customcert_issues', ['userid' => $USER->id]);
foreach ($customcerts as $issue) {
    $cert = $DB->get_record('customcert', ['id' => $issue->customcertid]);
    if (!$cert) continue;
    $cm = get_coursemodule_from_instance('customcert', $cert->id, $cert->course, false, MUST_EXIST);
    $url = new moodle_url('/mod/customcert/view.php', ['id' => $cm->id]);
    $certificates[] = [
        'name' => $cert->name,
        'issued' => date('d M Y', $issue->timecreated),
        'ago' => time_ago_local($issue->timecreated),
        'url' => $url->out()
    ];
}

// --- Fetch Iomad Certificates ---
if ($DB->get_manager()->table_exists('iomadcertificate_issues')) {
    $iomadcerts = $DB->get_records('iomadcertificate_issues', ['userid' => $USER->id]);
    foreach ($iomadcerts as $issue) {
        $cert = $DB->get_record('iomadcertificate', ['id' => $issue->iomadcertificateid]);
        if (!$cert) continue;
        $cm = get_coursemodule_from_instance('iomadcertificate', $cert->id, $cert->course, false, MUST_EXIST);
        $url = new moodle_url('/mod/iomadcertificate/view.php', ['id' => $cm->id]);
        $certificates[] = [
            'name' => $cert->name,
            'issued' => date('d M Y', $issue->timecreated),
            'ago' => time_ago_local($issue->timecreated),
            'url' => $url->out()
        ];
    }
}

echo $OUTPUT->header();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                primary: "#4F46E5",
                "background-light": "#F8F9FA",
                "background-dark": "#121212",
                "card-light": "#FFFFFF",
                "card-dark": "#1E1E1E",
                "text-light": "#333333",
                "text-dark": "#E0E0E0",
                "subtext-light": "#6c757d",
                "subtext-dark": "#9ca3af",
            },
            fontFamily: {
                display: ["Poppins", "sans-serif"],
            },
            borderRadius: {
                DEFAULT: "12px",
                lg: "16px",
            },
        },
    },
};
</script>
<style>
body { font-family: 'Poppins', sans-serif;  }
</style>
</head>

<body class="bg-background-light dark:bg-background-dark">
<div class="">
<main class="">
<div class="container mx-auto">
<header class="mb-12">
<h1 class="text-4xl font-bold text-text-light dark:text-text-dark">My Certificates</h1>
<p class="text-lg text-subtext-light dark:text-subtext-dark mt-2">Explore your learning journey and view your earned certificates.</p>
</header>

<?php if (empty($certificates)) { ?>
    <p class="text-center text-subtext-light dark:text-subtext-dark">No certificates issued yet.</p>
<?php } else { ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($certificates as $cert) { ?>
        <div class="group bg-card-light dark:bg-card-dark rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 flex flex-col" style="max-height: 269px;">
            <div class="p-3 flex-grow">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900/50">
                            <span class="material-icons-outlined text-primary text-2xl">school</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-text-light dark:text-text-dark">
                                <?= format_string($cert['name']); ?>
                            </h3>
                            <p class="text-sm text-subtext-light dark:text-subtext-dark">Issued: <?= $cert['issued']; ?></p>
                        </div>
                    </div>
                    <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-xs font-medium px-2.5 py-1 rounded-full whitespace-nowrap">
                        <?= $cert['ago']; ?>
                    </span>
                </div>
                <p class="text-sm text-subtext-light dark:text-subtext-dark mb-6">
                    Congratulations on earning this certificate!
                </p>
            </div>
            <div class="p-5 pt-0 mt-auto">
                <a href="<?= $cert['url']; ?>" target="_blank" 
                   class="w-full flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:text-white transition-colors duration-300">
                    <span class="material-icons-outlined text-base mr-2">download</span>
                    View / Download
                </a>
            </div>
        </div>
    <?php } ?>
</div>
<?php } ?>
</div>
</main>
</div>
</body>
</html>

<?php
echo $OUTPUT->footer();
