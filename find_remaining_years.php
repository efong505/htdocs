<?php
$conn = new mysqli('localhost', 'root', 'Hawaiian2012!', 'nextlevel_web');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8');

// Find the revenue generation page
$r = $conn->query("SELECT ID, post_title, post_name, post_status, post_type FROM nextl_posts WHERE post_name LIKE '%revenue%' OR post_name LIKE '%generating-machine%'");
echo "=== Revenue generation pages ===\n";
while ($row = $r->fetch_assoc()) {
    echo "ID:{$row['ID']} | {$row['post_type']} | {$row['post_status']} | {$row['post_title']} | /{$row['post_name']}/\n";
}

// Check the specific page
$r = $conn->query("SELECT ID, post_title, post_content FROM nextl_posts WHERE post_name = 'revenue-generation' OR post_name = 'generating-machine-2017' OR post_name = 'the-6-keys-to-transforming-your-website-into-a-revenue-generating-machine-in-2017'");
while ($row = $r->fetch_assoc()) {
    echo "\n=== ID:{$row['ID']} - {$row['post_title']} ===\n";
    // Find year references
    foreach (['2017', '2018'] as $year) {
        $pos = 0;
        while (($pos = strpos($row['post_content'], $year, $pos)) !== false) {
            $start = max(0, $pos - 80);
            $end = min(strlen($row['post_content']), $pos + 80);
            $snippet = strip_tags(substr($row['post_content'], $start, $end - $start));
            $snippet = preg_replace('/\s+/', ' ', $snippet);
            echo "  [{$year}] ...{$snippet}...\n";
            $pos += 4;
        }
    }
}

// Also find ALL published pages/posts that still have 2017 or 2018 in visible text (not URLs)
echo "\n=== All remaining pages with 2017/2018 in content (excluding URLs) ===\n";
$r = $conn->query("
    SELECT ID, post_title, post_name, post_type 
    FROM nextl_posts 
    WHERE post_status = 'publish' 
    AND post_type IN ('page','post')
    AND (post_content REGEXP '(^|[^/0-9-])2017([^/0-9-]|$)' OR post_content REGEXP '(^|[^/0-9-])2018([^/0-9-]|$)')
    ORDER BY post_type, post_title
");
while ($row = $r->fetch_assoc()) {
    echo "{$row['post_type']} | ID:{$row['ID']} | {$row['post_title']} | /{$row['post_name']}/\n";
}

// Also check cornerstone data
echo "\n=== Cornerstone data with 2017/2018 (non-URL) ===\n";
$r = $conn->query("
    SELECT pm.post_id, p.post_title 
    FROM nextl_postmeta pm 
    JOIN nextl_posts p ON pm.post_id = p.ID 
    WHERE pm.meta_key = '_cornerstone_data' 
    AND p.post_status = 'publish'
    AND (pm.meta_value LIKE '%2017%' OR pm.meta_value LIKE '%2018%')
");
while ($row = $r->fetch_assoc()) {
    // Check if it's just in URLs or actual text
    $stmt = $conn->prepare("SELECT meta_value FROM nextl_postmeta WHERE post_id = ? AND meta_key = '_cornerstone_data'");
    $stmt->bind_param('i', $row['post_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc()['meta_value'];
    
    $has_text_year = false;
    if (preg_match('/(?<![\/\-\d\.])201[78](?![\/\-\d\.])/', $data)) {
        $has_text_year = true;
    }
    if ($has_text_year) {
        echo "ID:{$row['post_id']} | {$row['post_title']}\n";
    }
}

$conn->close();
