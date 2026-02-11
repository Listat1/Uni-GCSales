<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle ?? 'Grimsby and Clee Sells'; ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css?v=1.2">
    <script>
        // Set Dark / Light theme
        const applyTheme = (theme) => {
            document.documentElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
        };

        // Toggle Button Logic
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        }

        // Ensure display mode for new page matches previous page to
        // Prevent flashing when rendered.
        const savedTheme = localStorage.getItem('theme') || 'dark';
        applyTheme(savedTheme);
    </script>
</head>

<body data-page-id="<?php echo $pageID ?? 'default'; ?>">
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">Grimsby & Clee Sells</a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0" id="navList">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($pageID == 'home') ? 'active fw-bold' : ''; ?>"
                                href="index.php">Home</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($pageID == 'dashboard') ? 'active fw-bold' : ''; ?>"
                                href="dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($pageID == 'basket') ? 'active fw-bold' : ''; ?>"
                                href="checkout.php">Basket</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="logoutBtn" href="#">Logout</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <button class="btn btn-sm btn-outline-secondary ms-lg-3" onclick="toggleTheme()">
                        🌓 Mode
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-5">
