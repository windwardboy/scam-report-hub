<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Scams - Scam Report Hub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Mobile-Friendly CSS -->
    <style>
        /* Increase font sizes for mobile devices */
        @media (max-width: 767.98px) {
            body {
                font-size: 1.1rem; /* Increase base font size */
            }
            h1 {
                font-size: 2rem; /* Increase heading size */
            }
            .btn {
                padding: 0.5rem 1rem; /* Adjust button padding */
                font-size: 1rem; /* Increase button font size */
            }
            .form-control, .form-select {
                font-size: 1rem; /* Increase form input font size */
            }
            .table {
                font-size: 0.9rem; /* Adjust table font size for mobile */
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-light py-3">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <!-- Brand/Logo -->
                <a href="index.html" class="navbar-brand text-decoration-none">
                    <h1 class="h4 mb-0">Scam Report Hub</h1>
                </a>

                <!-- Navbar Toggler Button (for mobile) -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Menu -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a href="index.html" class="nav-link">Home</a>
                        </li>
                        <li class="nav-item">
                            <a href="report.php" class="nav-link">Report a Scam</a>
                        </li>
                        <li class="nav-item">
                            <a href="browse.php" class="nav-link">Browse Scams</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mt-5">
        <h1>Browse Scams</h1>

        <!-- Search bar -->
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search scams..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
        <!-- End of search bar -->

        <!-- Filter dropdown -->
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <select name="filter" class="form-control">
                    <option value="">All Scam Types</option>
                    <option value="phishing">Phishing</option>
                    <option value="fake_online_store">Fake Online Store</option>
                    <option value="investment_scam">Investment Scam</option>
                    <option value="tech_support_scam">Tech Support Scam</option>
                    <option value="loan_grant_scam">Loan or Grant Scam</option>
                    <option value="romance_scam">Romance Scam</option>
                    <option value="job_scam">Job Scam</option>
                    <option value="fake_legal_threat">Fake Legal Threat</option>
                    <option value="delivery_shipping_scam">Delivery/Shipping Scam</option>
                    <option value="crypto_scam">Crypto Scam</option>
                    <option value="other">Other</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
        <!-- End of filter dropdown -->

        <!-- Scams Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Scam Type</th>
                        <th>Description</th>
                        <th>Contact Method</th>
                        <th>Date Reported</th>
                        <th>Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Include the database connection script
                    include 'includes/db.php';

                    // Get the search keyword and filter from the URL
                    $search = isset($_GET['search']) ? $_GET['search'] : '';
                    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';

                    // Pagination settings
                    $results_per_page = 10; // Number of results per page
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
                    $offset = ($page - 1) * $results_per_page; // Offset for the query

                    // Prepare the SQL query to search for scams
                    $query = "SELECT * FROM scams WHERE 
                              (scam_type LIKE :search OR 
                              description LIKE :search OR 
                              contact_method_type LIKE :search OR 
                              contact_method_details LIKE :search)";
                    if (!empty($filter)) {
                        $query .= " AND scam_type = :filter";
                    }
                    $query .= " LIMIT :limit OFFSET :offset";

                    // Prepare and execute the query
                    $stmt = $pdo->prepare($query);
                    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
                    if (!empty($filter)) {
                        $stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
                    }
                    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();

                    // Loop through the results and display them in the table
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['scam_type']}</td>";
                        echo "<td>{$row['description']}</td>";
                        echo "<td>{$row['contact_method_type']}: {$row['contact_method_details']}</td>";
                        echo "<td>{$row['date_reported']}</td>";
                        echo "<td><a href='{$row['evidence_path']}' target='_blank'>View Evidence</a></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination links -->
        <?php
        // Count total number of scams
        $count_query = "SELECT COUNT(*) FROM scams WHERE 
                        (scam_type LIKE :search OR 
                        description LIKE :search OR 
                        contact_method_type LIKE :search OR 
                        contact_method_details LIKE :search)";
        if (!empty($filter)) {
            $count_query .= " AND scam_type = :filter";
        }
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        if (!empty($filter)) {
            $count_stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_results = $count_stmt->fetchColumn();
        $total_pages = ceil($total_results / $results_per_page);

        // Display pagination links
        echo "<nav aria-label='Page navigation'>";
        echo "<ul class='pagination'>";
        for ($i = 1; $i <= $total_pages; $i++) {
            echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'>";
            echo "<a class='page-link' href='?search=$search&filter=$filter&page=$i'>$i</a>";
            echo "</li>";
        }
        echo "</ul>";
        echo "</nav>";
        ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="h6">Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="privacy-policy.html" class="text-decoration-none">Privacy Policy</a></li>
                        <li><a href="terms-of-service.html" class="text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2023 Scam Report Hub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS (Required for Navbar Toggler) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>