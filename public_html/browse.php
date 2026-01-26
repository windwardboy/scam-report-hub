<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Browse & Search Scam Reports | Scam Report Hub</title>
    <meta name="description" content="Browse, search, and filter user-submitted scam reports on Scam Report Hub. Check recent reports to stay informed about current scams and protect yourself.">
    <meta name="keywords" content="browse scams, search scams, check scam, scam database, scam list, reported scams, fraud awareness, scam prevention, scam check uk">
    <meta name="author" content="Scam Report Hub">
    
    <link rel="canonical" href="https://www.scamreporthub.com/browse.php">
    
    <meta property="og:title" content="Browse & Search Scam Reports | Scam Report Hub">
    <meta property="og:description" content="Browse, search, and filter user-submitted scam reports on Scam Report Hub. Check recent reports to stay informed about current scams and protect yourself.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.scamreporthub.com/browse.php">
    <meta property="og:image" content="https://www.scamreporthub.com/images/og-image.jpg"> <meta property="og:site_name" content="Scam Report Hub">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Browse & Search Scam Reports | Scam Report Hub">
    <meta name="twitter:description" content="Browse, search, and filter user-submitted scam reports on Scam Report Hub. Check recent reports to stay informed about current scams and protect yourself.">
    <meta name="twitter:image" content="https://www.scamreporthub.com/images/og-image-browse.jpg"> 
    
    <link rel="icon" type="image/png" href="https://scamreporthub.com/favicon.png?v=2">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> 
    
    <style>
        @media (max-width: 767.98px) {
            body { font-size: 1.1rem; }
            h1 { font-size: 2rem; }
            .btn { padding: 0.5rem 1rem; font-size: 1rem; }
            .filter-form .col-md-auto { width: 100%; margin-bottom: 0.5rem;} 
            .filter-form .btn { width: 100%; }
        }
         @media (min-width: 768px) { 
             .filter-form .col-md-3 { flex: 0 0 auto; width: 25%; }
             .filter-form .col-md-2 { flex: 0 0 auto; width: 16.66667%; }
        }
        .navbar-brand img { height: 55px; }
        .navbar-brand .text-logo { display: none !important; }
        .card-text { 
             min-height: 4.5em; /* Adjusted to match index.php */
        } 
        /* Added spacing for scam category */
        .card-body .scam-category { 
             margin-bottom: 0.75rem; 
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Browse Scams</h1>

        <form method="GET" action="" class="mb-4 filter-form">
             <div class="row g-2 align-items-end"> 
                  <div class="col-md mb-3"> <label for="search" class="form-label visually-hidden">Search</label>
                       <input type="text" id="search" name="search" class="form-control" placeholder="Search keywords, emails, URLs..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                  </div>
                  <div class="col-md-3 mb-3"> <label for="filter" class="form-label visually-hidden">Filter by Type</label>
                       <select name="filter" id="filter" class="form-select">
                            <option value="">All Scam Types</option>
                            <?php 
                            $scam_types_options = [
                                'phishing'=>'Phishing', 'fake_online_store'=>'Fake Online Store', 'investment_scam'=>'Investment Scam',
                                'tech_support_scam'=>'Tech Support Scam', 'loan_grant_scam'=>'Loan or Grant Scam', 'romance_scam'=>'Romance Scam',
                                'job_scam'=>'Job Scam', 'fake_legal_threat'=>'Fake Legal Threat', 'delivery_shipping_scam'=>'Delivery/Shipping Scam',
                                'crypto_scam'=>'Crypto Scam', 'other'=>'Other'
                            ];
                            $current_filter = $_GET['filter'] ?? '';
                            foreach ($scam_types_options as $value => $label) {
                                $selected = ($current_filter === $value) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($value)."\" $selected>".htmlspecialchars($label)."</option>";
                            }
                            ?>
                       </select>
                  </div>
                  <div class="col-md-3 mb-3"> <label for="date_filter" class="form-label visually-hidden">Filter by Date</label>
                       <select name="date_filter" id="date_filter" class="form-select">
                            <option value="">Any Time</option>
                            <?php 
                            $date_filter_options = [
                                'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days', 'last_90_days' => 'Last 90 Days'
                            ];
                            $current_date_filter = $_GET['date_filter'] ?? '';
                            foreach ($date_filter_options as $value => $label) {
                                $selected = ($current_date_filter === $value) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($value)."\" $selected>".htmlspecialchars($label)."</option>";
                            }
                            ?>
                       </select>
                  </div>
                  <div class="col-md-2 mb-3"> <button type="submit" class="btn btn-primary w-100">Filter / Search</button>
                  </div>
             </div>
        </form>


        <div class="row">
    <?php
    include 'includes/db.php'; 

    $icons = [ /* Icon definitions */ 
        'phishing' => 'envelope-exclamation', 'fake_online_store' => 'bag-x', 'investment_scam' => 'graph-down', 'tech_support_scam' => 'cpu', 'loan_grant_scam' => 'cash-stack', 'romance_scam' => 'heartbreak', 'job_scam' => 'person-workspace', 'fake_legal_threat' => 'file-earmark-excel', 'delivery_shipping_scam' => 'truck-front', 'crypto_scam' => 'currency-bitcoin', 'other' => 'question-circle'
    ];

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : ''; 
    $results_per_page = 9; 
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
    $page = max(1, $page); 
    $offset = ($page - 1) * $results_per_page; 

    // --- Calculate Date Threshold ---
    $date_threshold_sql = null; 
    $current_time = time(); 
    switch ($date_filter) {
        case 'last_7_days': $date_threshold_sql = date('Y-m-d H:i:s', strtotime('-7 days', $current_time)); break;
        case 'last_30_days': $date_threshold_sql = date('Y-m-d H:i:s', strtotime('-30 days', $current_time)); break;
        case 'last_90_days': $date_threshold_sql = date('Y-m-d H:i:s', strtotime('-90 days', $current_time)); break;
    }

    // --- Main Query to fetch reports (Positional Placeholders) ---
    try {
        $params_execute = []; 
        
        // ** UPDATED Query: Select specific columns including 'title' **
        $query = "SELECT id, slug, scam_type, title, description, created_at, date_reported 
                  FROM scams 
                  WHERE status = ?"; 
        $params_execute[] = 'approved'; 

        $query .= " AND (scam_type LIKE ? OR description LIKE ? OR contact_method_type LIKE ? OR contact_method_details LIKE ?)";
        $search_like_val = "%$search%";
        $params_execute[] = $search_like_val; 
        $params_execute[] = $search_like_val; 
        $params_execute[] = $search_like_val; 
        $params_execute[] = $search_like_val; 
        
        if (!empty($filter)) {
            $query .= " AND scam_type = ?"; 
            $params_execute[] = $filter; 
        }

        if ($date_threshold_sql !== null) {
            $query .= " AND created_at >= ?"; 
            $params_execute[] = $date_threshold_sql; 
        }

        $query .= " ORDER BY created_at DESC";

        $int_limit = (int)$results_per_page;
        $int_offset = (int)$offset;
        $query .= " LIMIT " . $int_limit . " OFFSET " . $int_offset; 

        $stmt = $pdo->prepare($query);
        $stmt->execute($params_execute); 

        $results_found = false; 
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results_found = true; 
            
            // --- Prepare variables for standardized card ---
            $slug = htmlspecialchars($row['slug']);
            $type_raw = strtolower($row['scam_type']);
            $type_formatted = htmlspecialchars(ucfirst(str_replace('_', ' ', $type_raw))); 
            $title = $row['title']; // Get the title
            $description = htmlspecialchars(substr($row['description'], 0, 100)) . '...'; // Standardized excerpt length
            $date = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : htmlspecialchars($row['date_reported']); 
            $link = "/scam/$slug";
            $icon = $icons[$type_raw] ?? 'shield-exclamation'; 
            $title_display = !empty($title) ? htmlspecialchars($title) : $type_formatted; // Use title or fallback to type

            // --- Generate Standardized Card HTML ---
            echo "
            <div class='col-12 col-md-4 d-flex mb-4'> 
                <div class='card flex-fill shadow-sm'> 
                    <div class='card-body d-flex flex-column'> 
                        <h5 class='card-title'>
                            <i class='bi bi-$icon me-2'></i>" . $title_display . " 
                        </h5>"; 
            // Echo the category below the title
            echo "<p class=\"fw-bold small scam-category\">Category: " . $type_formatted . "</p>";
            // Continue with rest of the card content
            echo "
                        <p class='card-text flex-grow-1'>$description</p> 
                        <p class='text-muted small mb-2'>Reported on $date</p>
                        <a href='$link' class='btn btn-sm btn-outline-primary mt-auto'>View Report</a> 
                    </div>
                </div>
            </div>";
        }

        if (!$results_found) {
             echo '<div class="col-12"><div class="alert alert-info" role="alert">No approved scam reports found matching your criteria.</div></div>';
        }

    } catch (PDOException $e) {
        error_log("Error fetching browse results: " . $e->getMessage()); 
        echo '<div class="col-12"><div class="alert alert-danger" role="alert">Could not retrieve scam reports at this time. Please try again later.</div></div>';
    }
    ?>
        </div><?php 
        // --- Count Query for Pagination (No changes needed here as it doesn't display cards) ---
        try {
            $count_params_execute = []; 
            $count_query = "SELECT COUNT(*) FROM scams WHERE status = ?"; 
            $count_params_execute[] = 'approved'; 
            $count_query .= " AND (scam_type LIKE ? OR description LIKE ? OR contact_method_type LIKE ? OR contact_method_details LIKE ?)";
            $search_like_val_count = "%$search%";
            $count_params_execute[] = $search_like_val_count; 
            $count_params_execute[] = $search_like_val_count; 
            $count_params_execute[] = $search_like_val_count; 
            $count_params_execute[] = $search_like_val_count; 
            if (!empty($filter)) {
                $count_query .= " AND scam_type = ?"; 
                $count_params_execute[] = $filter; 
            }
            if ($date_threshold_sql !== null) {
                 $count_query .= " AND created_at >= ?"; 
                 $count_params_execute[] = $date_threshold_sql; 
            }
            $count_stmt = $pdo->prepare($count_query);
            $count_stmt->execute($count_params_execute); 
            $total_results = (int) $count_stmt->fetchColumn(); 

            if ($total_results > 0) {
                 $total_pages = ceil($total_results / $results_per_page);
                 if ($total_pages > 1) {
                     $base_url_params = "?search=" . urlencode($search) . "&filter=" . urlencode($filter) . "&date_filter=" . urlencode($date_filter); 
                     echo "<nav aria-label='Page navigation'><ul class='pagination justify-content-center mt-4'>"; 
                     if ($page > 1) { echo "<li class='page-item'><a class='page-link' href='" . $base_url_params . "&page=" . ($page - 1) . "'>Previous</a></li>"; } else { echo "<li class='page-item disabled'><span class='page-link'>Previous</span></li>"; }
                     for ($i = 1; $i <= $total_pages; $i++) { echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'><a class='page-link' href='" . $base_url_params . "&page=$i'>$i</a></li>"; }
                     if ($page < $total_pages) { echo "<li class='page-item'><a class='page-link' href='" . $base_url_params . "&page=" . ($page + 1) . "'>Next</a></li>"; } else { echo "<li class='page-item disabled'><span class='page-link'>Next</span></li>"; }
                     echo "</ul></nav>";
                 }
            } 
        } catch (PDOException $e) {
             error_log("Error counting browse results (Positional test): " . $e->getMessage());
         }
        ?>
    </div> <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>