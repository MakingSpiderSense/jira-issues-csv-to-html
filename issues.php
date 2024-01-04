<?php
// Includes
include 'helpers.php';
include 'globals.php';

// Function to convert to snake_case
function toSnakeCase($string) {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace(' ', '_', $string)));
}

// Format text
function formatText($text) {
    // Replace headings
    $text = preg_replace_callback('/^h([1-6])\.\s?(.*)$/m', function($matches) {
        return '<strong>' . str_repeat('#', (int)$matches[1]) . ' ' . $matches[2] . '</strong>';
    }, $text);

    // Convert {noformat} to <code> tags with appropriate class
    $text = preg_replace_callback('/\{noformat\}(.*?)\{noformat\}/s', function($matches) {
        $codeContent = $matches[1];
        $codeClass = (strpos($codeContent, "\n") === false) ? 'inline' : 'multiline';
        return '<code class="' . $codeClass . '">' . htmlspecialchars($codeContent) . '</code>';
    }, $text);

    // Convert {quote} to <blockquote> tags
    $text = preg_replace('/\{quote\}(.*?)\{quote\}/s', '<blockquote>$1</blockquote>', $text);

    // Bold text wrapped in asterisks (*text*), ensuring spaces or line breaks around them
    $text = preg_replace('/(\s|^)\*(\S.*?)\*(\s|$)/s', '$1<strong>$2</strong>$3', $text);

    // Italicize text wrapped in underscores (_text_), ensuring spaces or line breaks around them
    $text = preg_replace('/(\s|^)_(\S.*?)_(\s|$)/s', '$1<em>$2</em>$3', $text);

    // Convert links to <a> tags
    $text = preg_replace('/\[(.*?)\|(https?:\/\/[^\|\]]+)(\|smart-link)?\]/', '<a href="$2" target="_blank">$1</a>', $text);

    // Convert bullet points to <ul><li> elements
    $text = preg_replace_callback('/(?:^\* [^\n].+$\n?)+/m', function($matches) {
        $listItems = preg_split('/\n/', trim($matches[0]));
        $listItemsFormatted = array_map(function($item) {
            return '<li>' . substr(trim($item), 2) . '</li>'; // Remove '* ' and wrap in <li>
        }, $listItems);
        return '<ul>' . implode("\n", $listItemsFormatted) . '</ul>';
    }, $text);

    return $text;
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

    // Indices for attachment and comment columns
    $attachment_indices = $comment_indices = [];

    foreach ($snake_case_headers as $index => $header) {
        if ($header === 'attachment') {
            $attachment_indices[] = $index;
        } elseif ($header === 'comment') {
            $comment_indices[] = $index;
        }
    }

    // Loop through each line of the CSV
    while (($row = fgetcsv($file)) !== FALSE) {
        if ($row[array_search('issue_key', $snake_case_headers)] == $requested_issue_key) {
            // Assign values to variables
            foreach ($snake_case_headers as $index => $header) {
                if (!in_array($header, ['attachment', 'comment'])) {
                    $$header = $row[$index];
                }
            }

            $description = nl2br(htmlspecialchars($description));

            // Handling multiple attachments
            foreach ($attachment_indices as $index) {
                $attachment_data = explode(';', $row[$index]);
                if (count($attachment_data) >= 4) {
                    $attachments[] = ['name' => $attachment_data[2], 'link' => $attachment_data[3]];
                }
            }

            // Handling multiple comments
            foreach ($comment_indices as $index) {
                $comments[] = $row[$index];
            }
            // Reversing the order of comments
            $comments = array_reverse($comments);

            // No need to continue loop once the issue is found
            break;
        }
    }
    fclose($file);
}

// Read .env file and create lookup table
$envPath = __DIR__ . '/.env'; // Adjust the path as needed
$userLookup = [];
if (file_exists($envPath)) {
    $envContents = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envContents as $line) {
        if (strpos($line, 'USER_') === 0) {
            list($key, $name) = explode('=', $line, 2);
            $userId = str_replace('USER_', '', $key);
            $userLookup[$userId] = $name;
        }
    }
}

// Function to replace user IDs with placeholder text
function replaceUserIdsWithNames($comment, $lookupTable) {
    return preg_replace_callback('/\[~accountid:([^\]]+)\]/', function ($matches) use ($lookupTable) {
        $name = isset($lookupTable[$matches[1]]) ? $lookupTable[$matches[1]] : $matches[1];
        // Use a unique placeholder
        return "%%TAGGED_USER_" . htmlspecialchars($name) . "%%";
    }, $comment);
}

// Processing comments
foreach ($comments as &$comment) {
    $commentParts = explode(';', $comment);
    if (count($commentParts) >= 3) {
        $timestamp = $commentParts[0];
        $userId = $commentParts[1];
        $commentText = $commentParts[2];
        // Replace user IDs with names and placeholders
        $commentText = replaceUserIdsWithNames($commentText, $userLookup);
        // Escape the entire comment and then convert line breaks
        $escapedComment = nl2br(htmlspecialchars($commentText));
        // Replace placeholders with HTML <span>
        $escapedComment = preg_replace('/%%TAGGED_USER_(.+?)%%/', '<span class="tagged">$1</span>', $escapedComment);
        $user = isset($userLookup[$userId]) ? $userLookup[$userId] : $userId;
        $comment = ['timestamp' => $timestamp, 'user' => $user, 'text' => $escapedComment];
    }
}
unset($comment); // Unset reference to the last element

// Apply formatText to the description
$description = formatText($description);

// Process comments and apply formatText to each
foreach ($comments as &$comment) {
    if (isset($comment['text'])) {
        $comment['text'] = formatText($comment['text']);
    }
}
unset($comment);
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?=$requested_issue_key?> - <?= htmlspecialchars($projectName) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="style1.css" rel="stylesheet">
</head>

<body class="page-issues">

<!-- Spacing -->
<div class="mb-50"></div>

<div class="container pw-20">

<header>
    <h2><?=htmlspecialchars($issue_key)?> - <?=htmlspecialchars($summary)?></h2>
    <div style="min-width: 65px;"><a href="/">← Back</a></div>
</header>

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
        <?=$description?>
    </div>
</section>

<section class="attachments">
    <h3>Attachments</h3>
    <ul>
        <?php foreach ($attachments as $attachment): ?>
            <li><a href="<?=htmlspecialchars($attachment['link'])?>"><?=htmlspecialchars($attachment['name'])?></a></li>
        <?php endforeach; ?>
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
        <?php foreach ($comments as $comment): ?>
            <?php if (!empty($comment['text'])): ?>
                <li><strong><?= htmlspecialchars($comment['timestamp']) ?> - <?= htmlspecialchars($comment['user']) ?></strong>: <?= $comment['text'] ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</section>


</div>

<script src="js/app.js"></script>

</body>
</html>