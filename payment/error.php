<?php
include('../includes/header.php');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card border-danger border-opacity-25 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="display-1 text-danger mb-4">
                            <i class="bi bi-exclamation-circle-fill"></i>
                        </div>
                        
                        <h1 class="display-6 fw-bold text-danger mb-3">Payment Failed</h1>
                        <p class="text-muted mb-4">
                            Sorry, your payment could not be processed at this time. Please try again or contact support if the problem persists.
                        </p>
                        
                            <a href="../student/inventory.php" class="btn btn-danger btn-lg px-5">
                                <i class="bi bi-arrow-repeat me-2"></i>Try Again
                            </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>