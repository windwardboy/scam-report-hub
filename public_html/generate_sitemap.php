<?php
// Increase execution time limit for potentially large sitemaps
set_time_limit(300); // 5 minutes, adjust if needed

// Set the correct timezone - IMPORTANT for date functions
date_default_timezone_set('Europe/London'); // Set to your server's/site's timezone

// --- Configuration ---
// Ensure you have config included for DB access (via db.php)
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

$base_url = 'https://www.scamreporthub.com'; // Your website's base URL
$sitemap_filename = 'sitemap-core.xml'; // Output filename for this sitemap part

// --- Static Pages ---
// Add URLs of your main static/browse pages here
// Format: 'path' => ['changefreq' => '...', 'priority' => '...']
// Paths should be relative to the base_url (e.g., '/', '/browse.php')
$static_pages = [
    '/' => ['changefreq' => 'daily', 'priority' => '1.0'],
    '/browse.php' => ['changefreq' => 'daily', 'priority' => '0.8'],
    '/report.php' => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/privacy-policy.php' => ['changefreq' => 'yearly', 'priority' => '0.3'],
    '/terms-of-service.php' => ['changefreq' => 'yearly', 'priority' => '0.3'],
    // Add other static public pages if any (NOT admin/edit pages)
];

// --- Start XML Output ---
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// --- Add Static Pages ---
echo "Processing static pages...<br>";
foreach ($static_pages as $path => $options) {
    $url = rtrim($base_url, '/') . '/' . ltrim($path, '/');
    // Use file modification time if possible, otherwise today's date
    $filepath = __DIR__ . '/' . ltrim($path, '/');
    // Handle index file specifically
    if ($path === '/') {
        $filepath = __DIR__ . '/index.php'; 
    } elseif (!strpos($filepath, '.php')) {
         // Skip if not a .php file and not root (adjust if you have non-php static files)
         // Or try adding .php if that's your convention
          if (file_exists($filepath . '.php')) {
              $filepath .= '.php';
          } else {
              $filepath = null; // Cannot determine file path reliably
          }
    }
    
    $lastmod = $filepath && file_exists($filepath) ? date('c', filemtime($filepath)) : date('c'); // W3C Datetime format

    $xml .= "\t<url>\n";
    $xml .= "\t\t<loc>" . htmlspecialchars($url) . "</loc>\n";
    $xml .= "\t\t<lastmod>" . $lastmod . "</lastmod>\n";
    $xml .= "\t\t<changefreq>" . htmlspecialchars($options['changefreq']) . "</changefreq>\n";
    $xml .= "\t\t<priority>" . htmlspecialchars($options['priority']) . "</priority>\n";
    $xml .= "\t</url>\n";
}
echo "Static pages added.<br>";

// --- Add Dynamic Scam Report Pages ---
echo "Fetching approved scam reports...<br>";
try {
    $sql = "SELECT slug, updated_at FROM scams WHERE status = 'approved' ORDER BY updated_at DESC";
    $stmt = $pdo->query($sql); // No user input needed here
    $scam_count = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'];
        $updated_at = $row['updated_at'];
        if ($slug && $updated_at) {
            $url = rtrim($base_url, '/') . '/scam/' . urlencode($slug); // Use urlencode just in case
            $lastmod = date('c', strtotime($updated_at)); // Format DB timestamp to W3C Datetime

            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "\t\t<changefreq>weekly</changefreq>\n"; // Or 'monthly', 'yearly', 'never'
            $xml .= "\t\t<priority>0.6</priority>\n"; // Lower than static pages maybe
            $xml .= "\t</url>\n";
            $scam_count++;
        }
    }
    echo "Added " . $scam_count . " approved scam reports.<br>";

} catch (PDOException $e) {
    echo "<strong style='color:red;'>Error fetching scam reports: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
    // Continue to try and write the sitemap with static pages if possible
}

// --- Close XML ---
$xml .= '</urlset>';

// --- Save to File ---
echo "Attempting to save sitemap to " . htmlspecialchars($sitemap_filename) . "...<br>";
$filepath_output = __DIR__ . '/' . $sitemap_filename;

// Use @ to suppress errors if file_put_contents fails due to permissions
if (@file_put_contents($filepath_output, $xml)) {
    echo "<strong style='color:green;'>Successfully generated " . htmlspecialchars($sitemap_filename) . "!</strong><br>";
    echo "File size: " . number_format(filesize($filepath_output) / 1024, 2) . " KB<br>";
    echo "You can now create/update your main sitemap index file (sitemap.xml) to include this file.";
} else {
    $error_details = error_get_last();
    echo "<strong style='color:red;'>ERROR: Failed to write sitemap file!</strong><br>";
    echo "Please check write permissions for the web server user in the directory: " . htmlspecialchars(__DIR__) . "<br>";
    if ($error_details) {
         echo "PHP Error: " . htmlspecialchars($error_details['message']) . "<br>";
    }
    // Optionally output the XML directly for debugging if save fails
    // header('Content-Type: application/xml'); echo $xml; exit(); 
}

?>