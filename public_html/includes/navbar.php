<?php
// NOTE: Session is expected to be started by the including file (e.g., edit.php, index.php)
// BEFORE this navbar file is included.
?>
<header class="bg-light py-3 shadow-sm"> 
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <a href="/index.php" class="navbar-brand text-decoration-none">
                    <img src="/images/logo.png" alt="Scam Report Hub Logo" class="d-inline-block align-text-top">
                    <span class="text-logo h4 mb-0 d-inline">Scam Report Hub</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarNav"> 
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0"> 
                        <li class="nav-item"><a href="/index.php" class="nav-link">Home</a></li>
                        <li class="nav-item"><a href="/report.php" class="nav-link">Report a Scam</a></li>
                        <li class="nav-item"><a href="/browse.php" class="nav-link">Browse Scams</a></li>
                        <li class="nav-item"><a href="https://scamreporthub.com/community/blog/" class="nav-link">Blog</a></li> 
                        <li class="nav-item"><a href="https://scamreporthub.com/community/contact-us/" class="nav-link">Contact Us</a></li>
                    </ul>
                
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0"> 
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin_moderate.php">
                                    <i class="bi bi-shield-lock-fill"></i> Admin Panel 
                                </a>
                            </li>
                            <li class="nav-item">
                                 <a class="nav-link" href="/admin_logout.php">
                                     <i class="bi bi-box-arrow-right"></i> Logout
                                 </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                </div></div></nav>
    </div></header>