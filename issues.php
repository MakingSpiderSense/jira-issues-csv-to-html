<?php
// Function to convert to snake_case
function toSnakeCase($string) {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace(' ', '_', $string)));
}

// Fetch the issue key from the query string
$requested_issue_key = isset($_GET['issue_key']) ? $_GET['issue_key'] : null;

// Variables to hold issue details
$issue_key = $summary = $issue_type = $status = $priority = $environment = $description = $creator = $created = $labels = $parent_summary = '';
$attachments = $comments = [];

if ($requested_issue_key) {
    $file = fopen('jira.csv', 'r');
    $headers = fgetcsv($file);
    $snake_case_headers = array_map('toSnakeCase', $headers);

    // Loop through each line of the CSV
    while (($row = fgetcsv($file)) !== FALSE) {
        if ($row[array_search('issue_key', $snake_case_headers)] == $requested_issue_key) {
            // Assign values to variables
            foreach ($snake_case_headers as $index => $header) {
                $$header = $row[$index];
            }

            // Handling multiple attachments and comments
            if (isset($attachments[$row[array_search('attachment_url', $snake_case_headers)]])) {
                $attachments[] = $row;
            }

            if (isset($comments[$row[array_search('comment', $snake_case_headers)]])) {
                $comments[] = $row[array_search('comment', $snake_case_headers)];
            }

            // No need to continue loop once the issue is found
            break;
        }
    }
    fclose($file);
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?=$requested_issue_key?> - Paycove Jira</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="style1.css" rel="stylesheet">
</head>

<body>

<!-- Spacing -->
<div class="mb-50"></div>

<div class="container pw-20">


<h2><?=htmlspecialchars($issue_key)?> - <?=htmlspecialchars($summary)?></h2>

<section class="metadata">
    <h3>Metadata</h3>
    <ul>
        <li>Issue Type: <?=htmlspecialchars($issue_type)?></li>
        <li>Status: <?=htmlspecialchars($status)?></li>
        <li>Priority: <?=htmlspecialchars($priority)?></li>
        <li>Environment: <?=htmlspecialchars($environment)?></li>
    </ul>
</section>

<section class="description">
    <h3>Description</h3>
    <div>
        <?=htmlspecialchars($description)?>
    </div>
</section>

<section class="attachments">
    <h3>Attachments</h3>
    <ul>
        <?php foreach ($attachments as $attachment):?>
            <li><a href="<?=htmlspecialchars($attachment[2])?>"><?=htmlspecialchars($attachment[3])?></a></li>
        <?php endforeach;?>
    </ul>
</section>

<section class="more-info">
    <h3>More Info</h3>
    <ul>
        <li>Creator: <?=htmlspecialchars($creator)?></li>
        <li>Created: <?=htmlspecialchars($created)?></li>
        <li>Labels: <?=htmlspecialchars($labels)?></li>
        <li>Parent: <?=htmlspecialchars($parent_summary)?></li>
    </ul>
</section>

<section class="comments">
    <h3>Comments</h3>
    <ul>
        <?php foreach ($comments as $comment):?>
            <li><?=htmlspecialchars($comment)?></li>
        <?php endforeach;?>
    </ul>
</section>


</div>

<script src="js/app.js"></script>

</body>
</html>