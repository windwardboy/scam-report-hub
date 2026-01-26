<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Scam Reporting Resources | Scam Report Hub</title>
    <meta name="description" content="Direct links to official government agencies worldwide to report scams">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #4e73df;
            --hover-color: #2e59d9;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f8f9fa;
        }
    
    /* Logo handling - matches homepage exactly */
        .navbar-brand .text-logo {
        display: none !important;
        }
        
        .navbar-brand img {
        height: 55px;
        width: auto;
        }
        
        .agency-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        
        .country-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 0.5rem rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .country-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15);
        }
        
        .flag-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .resource-link {
            color: #495057;
            border-left: 3px solid var(--primary-color);
            transition: all 0.2s;
        }
        
        .resource-link:hover {
            background-color: #f1f5ff;
            border-left-color: var(--hover-color);
            text-decoration: none;
        }
        
        .agency-logo {
            max-height: 50px;
            max-width: 100%;
            filter: grayscale(100%) opacity(0.8);
            transition: filter 0.3s;
        }
        
        .agency-logo:hover {
            filter: none;
        }
        
        @media (max-width: 767.98px) {
            .hero-heading-mobile-adjust {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="py-5" style="background-color: var(--primary-color);">
        <div class="container py-4">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="display-5 fw-bold mb-3 hero-heading-mobile-adjust">Official Scam Reporting Resources</h1>
                    <p class="lead mb-0">Direct links to government agencies worldwide</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Global Agencies -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="agency-header p-3 mb-4">
                    <h2 class="h4 mb-0 text-white"><i class="bi bi-globe me-2"></i>Global Reporting Agencies</h2>
                </div>
                
                <div class="row g-4">
                    <!-- IC3 Card -->
                    <div class="col-md-4">
                        <div class="country-card h-100 bg-white">
                            <div class="card-body text-center p-4">
                                <img src="https://www.ic3.gov/img/ic3_logo.webp" alt="IC3 Logo" class="agency-logo mb-3">
                                <h3 class="h5 mb-3">IC3 (FBI Internet Crime)</h3>
                                <p class="text-muted small mb-4">Report cybercrime to the FBI's dedicated unit</p>
                                <a href="https://www.ic3.gov" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                                    Report Here <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FTC Card -->
                    <div class="col-md-4">
                        <div class="country-card h-100 bg-white">
                            <div class="card-body text-center p-4">
                                <img src="https://reportfraud.ftc.gov/assets/images/RF-Logo-Seal-NEW-01.svg" alt="FTC Logo" class="agency-logo mb-3">
                                <h3 class="h5 mb-3">FTC Report Fraud</h3>
                                <p class="text-muted small mb-4">U.S. Federal Trade Commission's scam portal</p>
                                <a href="https://reportfraud.ftc.gov" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                                    Report Here <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- eConsumer Card -->
                    <div class="col-md-4">
                        <div class="country-card h-100 bg-white">
                            <div class="card-body text-center p-4">
                                <img src="https://www.econsumer.gov/images/econsumer_logo.svg" alt="eConsumer Logo" class="agency-logo mb-3">
                                <h3 class="h5 mb-3">eConsumer.gov</h3>
                                <p class="text-muted small mb-4">International online purchase complaints</p>
                                <a href="https://www.econsumer.gov" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                                    Report Here <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Country Sections -->
        <div class="row">
            <!-- United States -->
            <div class="col-lg-6 mb-4">
                <div class="country-card bg-white h-100">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0"><img src="https://flagcdn.com/w20/us.png" class="flag-icon" alt="US flag"> United States</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="https://reportfraud.ftc.gov" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                FTC Complaint Assistant
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                            <a href="https://consumercomplaints.fcc.gov" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                FCC (Robocalls & Spam Texts)
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                            <a href="https://www.bbb.org/scamtracker" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                BBB Scam Tracker
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                            <a href="https://www.uspis.gov/report" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                USPS Mail Fraud Reporting
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- United Kingdom -->
            <div class="col-lg-6 mb-4">
                <div class="country-card bg-white h-100">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0"><img src="https://flagcdn.com/w20/gb.png" class="flag-icon" alt="UK flag"> United Kingdom</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="https://www.actionfraud.police.uk" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                Action Fraud (UK National Reporting)
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                            <a href="https://www.ofcom.org.uk" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                Ofcom (Spam Calls & Texts)
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Canada -->
            <div class="col-lg-6 mb-4">
                <div class="country-card bg-white h-100">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0"><img src="https://flagcdn.com/w20/ca.png" class="flag-icon" alt="Canada flag"> Canada</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="https://www.antifraudcentre-centreantifraude.ca" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                Canadian Anti-Fraud Centre
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                            <a href="https://fightspam.gc.ca" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                CRTC (Spam Reporting)
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Australia & NZ -->
            <div class="col-lg-6 mb-4">
                <div class="country-card bg-white h-100">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0"><img src="https://flagcdn.com/w20/au.png" class="flag-icon" alt="Australia flag"> Australia & NZ</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="https://www.scamwatch.gov.au" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                Scamwatch (ACCC)
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                            <a href="https://www.netsafe.org.nz" target="_blank" class="list-group-item list-group-item-action resource-link d-flex justify-content-between align-items-center py-3">
                                NetSafe (New Zealand)
                                <i class="bi bi-box-arrow-up-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5 class="alert-heading">Don't See Your Country?</h5>
                    <p>We're constantly expanding our list of official resources. If you know of an official scam reporting agency in your country that we should include, <a href="/community/contact-us" class="alert-link">please let us know</a>.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>