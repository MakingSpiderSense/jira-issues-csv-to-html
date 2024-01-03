<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Issues - Paycove Jira</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="style1.css" rel="stylesheet">
</head>

<body>

<!-- Spacing -->
<div class="mb-50"></div>

<div class="container pw-20">


<?php
function toSnakeCase($string) {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace(' ', '_', $string)));
}

$file = fopen('jira.csv', 'r');
$headers = fgetcsv($file);

// Convert headers to snake_case
$snake_case_headers = array_map('toSnakeCase', $headers);

// Find the indices for issue_key and summary
$issue_key_index = array_search('issue_key', $snake_case_headers);
$summary_index = array_search('summary', $snake_case_headers);

if ($issue_key_index === false || $summary_index === false) {
    die('Required columns not found in CSV');
}

// Array to hold all issues
$issues = [];

// Loop through each line of the CSV
while (($row = fgetcsv($file)) !== FALSE) {
    $issue_key = $row[$issue_key_index];
    $summary = $row[$summary_index];

    // Store issue data in an array
    $issues[] = ['key' => $issue_key, 'summary' => $summary];
}
fclose($file);

// Custom sort function
function sortIssues($a, $b) {
    $numA = intval(substr($a['key'], 5)); // Extracting number part from "PAYC-xxx"
    $numB = intval(substr($b['key'], 5));

    return $numB - $numA; // Descending order
}

usort($issues, 'sortIssues');
?>

<section class="issues">
    <h3>Issues</h3>
    <ul>
        <?php
        // Loop through sorted issues and output them
        foreach ($issues as $issue) {
            echo "<li><a href=\"/issues.php?issue_key=" . htmlspecialchars($issue['key']) . "\">" . htmlspecialchars($issue['key']) . " - " . htmlspecialchars($issue['summary']) . "</a></li>";
        }
        ?>
    </ul>
</section>


</div>

<script src="js/app.js"></script>

</body>
</html>