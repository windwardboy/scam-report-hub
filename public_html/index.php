<?php // START OF index.php
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Scam Report Hub | Report, Browse & Check Scams</title>
    <meta name="description" content="Report scams & browse user-submitted reports on Scam Report Hub. Check phone numbers, emails & websites to help prevent fraud and protect the community.">
    <meta name="keywords" content="scam report, report scam, check scam, scam database, fraud prevention, scam awareness, browse scams, scam check uk"> 
    <meta name="author" content="Scam Report Hub">
    
    <link rel="canonical" href="https://www.scamreporthub.com/index.php">
    
    <meta property="og:title" content="Scam Report Hub | Report, Browse & Check Scams">
    <meta property="og:description" content="Report scams & browse user-submitted reports on Scam Report Hub. Check phone numbers, emails & websites to help prevent fraud and protect the community.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.scamreporthub.com/index.php">
    <meta property="og:image" content="https://www.scamreporthub.com/images/og-image.jpg"> <meta property="og:site_name" content="Scam Report Hub">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Scam Report Hub | Report, Browse & Check Scams">
    <meta name="twitter:description" content="Report scams & browse user-submitted reports on Scam Report Hub. Check phone numbers, emails & websites to help prevent fraud and protect the community.">
    <meta name="twitter:image" content="https://www.scamreporthub.com/images/og-image.jpg"> <link rel="icon" type="image/png" href="https://scamreporthub.com/favicon.png?v=2">
    
    <link rel="icon" type="image/png" href="https://scamreporthub.com/favicon.png?v=2">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/klaro@0.7.22/dist/klaro.min.css" />
    
    <style> /* Styles */ @media (max-width: 767.98px) { body{font-size:1.1rem;} .hero-heading-mobile-adjust{font-size:2rem;} .btn{padding:.5rem 1rem;font-size:1rem;} } .navbar-brand img{height:55px;} .navbar-brand .text-logo{display:none !important;} .card-text{min-height:4.5em;} .card-body .scam-category{margin-bottom:.75rem;} /* Ensure blog images have consistent height */ .blog-card-img { height: 200px; object-fit: cover; } </style>

</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section class="mt-5 py-3 py-lg-5 py-xl-8"> 
        <div class="container overflow-hidden"> <div class="row gy-5 gy-lg-0 align-items-lg-center justify-content-lg-between"> <div class="col-12 col-lg-6 order-1 order-lg-0"> <h2 class="display-3 fw-bold mb-3 hero-heading-mobile-adjust">Scam Alert: Report, Search, Fight Back</h2> <p class="fs-4 mb-5">Anonymously share details of scams you've encountered. Search our growing database of reports to stay informed and avoid becoming a victim.</p> <div class="d-grid gap-2 d-sm-flex"> <a href="report.php" class="btn btn-primary btn-lg rounded-pill">Report a Scam</a> <a href="browse.php" class="btn btn-outline-secondary btn-lg rounded-pill">Browse Reports</a> </div> </div> <div class="col-12 col-lg-5 text-center"> <img class="img-fluid" loading="lazy" src="https://www.scamreporthub.com/images/hero-image.png" alt="Illustration representing scam reporting and protection"> </div> </div> </div>
    </section>
    <div class="container"> 
       <?php
       // Check for user feedback messages from session first
       if (isset($_SESSION['user_feedback']) && is_array($_SESSION['user_feedback'])) {
           $feedback = $_SESSION['user_feedback'];
           $message_type = htmlspecialchars($feedback['type'] ?? 'info'); 
           $message_text = htmlspecialchars($feedback['message'] ?? 'Action completed.');
           
           echo "<div class=\"alert alert-{$message_type} alert-dismissible fade show mt-3\" role=\"alert\">";
           echo $message_text; 
           echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
           echo "</div>";
           unset($_SESSION['user_feedback']); 
       } 
       // Then check for GET parameters
       elseif (isset($_GET['submitted']) && $_GET['submitted'] == '1') {
            echo "<div class=\"alert alert-success alert-dismissible fade show mt-3\" role=\"alert\">";
            echo "<h4 class=\"alert-heading\">Submission Received!</h4>";
            echo "<p>Thank you for your report. It has been successfully submitted and is now pending review by our moderators before being published.</p>";
            echo "<hr>";
            echo "<p class=\"mb-0\">If you provided an email address, you should receive an email containing a private link to edit your submission.</p>";
            echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
            echo "</div>";
       } 
       elseif (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
            echo "<div class=\"alert alert-success alert-dismissible fade show mt-3\" role=\"alert\">";
            echo "Your report has been successfully deleted.";
            echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
            echo "</div>";
       }
       // Add other checks like ?logged_out=1 if needed later
       ?>
    </div>
    <div class="container mt-5">
        <h2 class="h4 mb-4">Latest Scam Reports</h2>
        <div class="row">
            <?php 
            include_once 'includes/db.php'; // Use include_once for db
            $icons = [ 'phishing'=>'envelope-exclamation', 'fake_online_store'=>'bag-x', 'investment_scam'=>'graph-down', 'tech_support_scam'=>'cpu', 'loan_grant_scam'=>'cash-stack', 'romance_scam'=>'heartbreak', 'job_scam'=>'person-workspace', 'fake_legal_threat'=>'file-earmark-excel', 'delivery_shipping_scam'=>'truck-front', 'crypto_scam'=>'currency-bitcoin', 'other'=>'question-circle' ]; 
            try {
                $sql = "SELECT slug, scam_type, title, description, created_at, date_reported FROM scams WHERE status = :status ORDER BY created_at DESC LIMIT 3"; 
                $stmt = $pdo->prepare($sql); 
                $stmt->execute(['status' => 'approved']);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
                    $slug = htmlspecialchars($row['slug']); 
                    $type_raw = strtolower($row['scam_type']); 
                    $type_formatted = htmlspecialchars(ucfirst(str_replace('_', ' ', $type_raw))); 
                    $title = $row['title']; 
                    $description = htmlspecialchars(substr($row['description'], 0, 100)) . '...'; 
                    $date = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : htmlspecialchars($row['date_reported']); 
                    $link = "/scam/$slug"; 
                    $icon = $icons[$type_raw] ?? 'shield-exclamation'; 
                    $title_display = !empty($title) ? htmlspecialchars($title) : $type_formatted; 
                    echo "<div class='col-12 col-md-4 d-flex mb-4'><div class='card flex-fill shadow-sm'><div class='card-body d-flex flex-column'><h5 class='card-title'><i class='bi bi-$icon me-2'></i>".$title_display."</h5>"; 
                    echo "<p class=\"fw-bold small scam-category\">Category: ".$type_formatted."</p>"; 
                    echo "<p class='card-text flex-grow-1'>$description</p><p class='text-muted small mb-2'>Reported on $date</p><a href='$link' class='btn btn-sm btn-outline-primary mt-auto'>View Report</a></div></div></div>"; 
                }
            } catch (PDOException $e) {
                error_log("Error fetching latest reports for homepage: " . $e->getMessage());
                // Optionally show a subtle error message here, but maybe just log it
            }
            ?>
        </div>
    </div>

    <div class="container mt-5">
        <h2 class="h4 mb-4">Latest News</h2>
        <div class="row" id="latest-news-posts"> 
             <div class="col"><p>Loading news...</p></div> </div>
    </div>
    <div class="container mt-5">
        <h2 class="h4 mb-4">Understanding Scams</h2>
        <div class="row" id="education-posts">
             <div class="col"><p>Loading resources...</p></div> </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        
        function fetchAndDisplayPosts(categoryId, containerId, sectionName) {
            const apiUrl = `https://scamreporthub.com/community/wp-json/wp/v2/posts?per_page=3&categories=${categoryId}&_embed`;
            const container = document.getElementById(containerId);
            if (!container) { console.error(`Container ID "${containerId}" not found.`); return; }

            fetch(apiUrl)
                .then(res => {
                    if (!res.ok) { throw new Error(`HTTP error! Status: ${res.status} fetching ${sectionName}`); }
                    return res.json();
                })
                .then(posts => {
                    container.innerHTML = ''; // Clear loading indicator
                    if (!Array.isArray(posts) || posts.length === 0) {
                         container.innerHTML = `<div class="col"><p>No recent posts found in the ${sectionName} category.</p></div>`; 
                         return;
                    }
                    posts.forEach(post => {
                        const title = post.title?.rendered ?? 'Untitled'; 
                        const excerpt = (post.excerpt?.rendered ?? '').replace(/(<([^>]+)>)/gi, "").substring(0, 100) + "...";
                        const link = post.link ?? '#'; 
                        let imageUrl = 'https://www.scamreporthub.com/images/placeholder.jpg'; 
                        try { if (post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0] && post._embedded['wp:featuredmedia'][0].source_url) { imageUrl = post._embedded['wp:featuredmedia'][0].source_url; } } catch (e) { console.error(`Error getting featured image for ${sectionName}:`, e); }
                        const col = document.createElement("div");
                        col.className = "col-md-4 d-flex mb-4"; 
                        col.innerHTML = `<div class="card flex-fill shadow-sm"><img src="${imageUrl}" class="card-img-top blog-card-img" alt="Featured Image for ${title}"><div class="card-body d-flex flex-column"><h5 class="card-title">${title}</h5><p class="card-text flex-grow-1">${excerpt}</p><a href="${link}" class="btn btn-sm btn-outline-primary mt-auto" target="_blank" rel="noopener noreferrer">Read More</a></div></div>`; 
                        container.appendChild(col);
                    });
                })
                .catch(err => {
                    console.error(`Failed to fetch ${sectionName} posts`, err);
                    if (container) { container.innerHTML = `<div class="col"><p class="text-danger">Could not load ${sectionName} posts at this time.</p></div>`; }
                });
        }

        // Fetch "Latest News" (Category ID 1) 
        fetchAndDisplayPosts(1, 'latest-news-posts', 'Latest News');

        // Fetch "Understanding Scams" (Category ID 11) 
        fetchAndDisplayPosts(11, 'education-posts', 'Understanding Scams');

    });
    </script>
    <?php include 'includes/footer.php'; // Includes Klaro JS and Config ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </body>
</html> <?php // END OF index.php ?>