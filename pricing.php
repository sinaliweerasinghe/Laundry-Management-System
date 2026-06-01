<?php
session_start();
require_once 'config/database.php';
$db = new Database();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user_name'] ?? 'Account';

// Get all services from database
$services_query = "SELECT * FROM services ORDER BY display_order ASC";
$services_result = $db->query($services_query);
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

// Get pricing summary
$summary_query = "SELECT * FROM pricing_summary ORDER BY display_order ASC";
$summary_result = $db->query($summary_query);
$pricing_summary = [];
while ($row = $summary_result->fetch_assoc()) {
    $pricing_summary[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Perfect Laundry</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="dull.css">
    <style>
        /* Your Sky Blue Theme Colors */
        :root {
            --sky-blue-1: #E1F3FE;
            --sky-blue-2: #B8E2F2;
            --sky-blue-3: #7FC9E6;
            --sky-blue-4: #4AA3D1;
            --sky-blue-5: #2C7AA0;
            --ocean-blue: #1E5A7A;
            --luminous-green: #6FCF97;
            --luminous-green-dark: #27AE60;
            --white: #FFFFFF;
            --pure-white: #FFFFFF;
            --light-gray: #F8FAFC;
            --soft-gray: #E2E8F0;
            --text-dark: #1A2F3F;
            --text-soft: #2C3E50;
            
            --gradient-green: linear-gradient(135deg, #6FCF97, #27AE60);
            --shadow-md: 0 10px 15px -3px rgba(44, 122, 160, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(145deg, #E1F3FE 0%, #C5E6F7 50%, #B0DCF2 100%);
            color: var(--text-dark);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .services-title {
            text-align: center;
            margin-bottom: 40px;
            margin-top: 100px;
        }
        
        .services-title h1 {
            font-size: 3rem;
            font-weight: 700;
            color: var(--text-dark);
            position: relative;
            display: inline-block;
        }
        
        .services-title h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--gradient-green);
            border-radius: 2px;
        }

        /* Cards Row - Alternating Layout */
        .cards-row {
            display: flex;
            flex-direction: column;
            gap: 60px;
            margin-bottom: 60px;
        }

        /* Individual Card - Alternating Layout */
        .service-card {
            display: flex;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 30px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(44, 122, 160, 0.2);
        }

        /* Left Side - Image Carousel */
        .card-image {
            flex: 1;
            padding: 25px;
        }

        /* Right Side - Content */
        .card-content-side {
            flex: 1;
            padding: 35px 35px 35px 25px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Alternating layout - Second card swaps sides */
        .service-card.reverse {
            flex-direction: row-reverse;
        }

        .service-card.reverse .card-content-side {
            padding: 35px 25px 35px 35px;
        }

        .card-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--ocean-blue);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--luminous-green-dark);
            font-size: 2.2rem;
        }

        .image-carousel {
            width: 100%;
            position: relative;
        }

        .carousel-container {
            position: relative;
            width: 100%;
            margin: 0 auto;
        }

        .carousel-track {
            position: relative;
            width: 100%;
            height: 320px;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(44, 122, 160, 0.25);
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .carousel-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(39, 174, 96, 0.1), rgba(255, 255, 255, 0.1));
            pointer-events: none;
            z-index: 2;
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 2px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            color: var(--sky-blue-5);
            font-size: 0.9rem;
        }

        .carousel-arrow:hover {
            background: var(--gradient-green);
            color: white;
            transform: translateY(-50%) scale(1.1);
            border-color: var(--luminous-green-dark);
        }

        .carousel-arrow.left {
            left: 12px;
        }

        .carousel-arrow.right {
            right: 12px;
        }

        .carousel-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 12px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid var(--sky-blue-4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: var(--gradient-green);
            border-color: var(--luminous-green-dark);
            transform: scale(1.2);
        }

        .text-box {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            padding: 18px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 5px 15px rgba(44, 122, 160, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .text-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 122, 160, 0.15);
            background: rgba(255, 255, 255, 0.8);
        }

        h2 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--sky-blue-5);
            margin: 0 0 8px 0;
        }

        p {
            font-size: 0.95rem;
            color: var(--text-soft);
            line-height: 1.6;
            margin-bottom: 0;
        }

        .pricing-bold {
            font-weight: 700;
            color: var(--sky-blue-5);
            font-size: 1rem;
            display: block;
            margin: 0 0 6px 0;
        }

        .price-tag {
            background: var(--gradient-green);
            color: white;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-block;
            margin-top: 8px;
            box-shadow: 0 8px 18px rgba(111, 207, 151, 0.3);
        }

        .price-tag i {
            margin-right: 6px;
        }

        .divider {
            margin: 40px 0;
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--sky-blue-4), transparent);
        }

        .pricing-section {
            margin: 60px 0 40px;
        }

        .pricing-section h2 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 30px;
            position: relative;
        }

        .pricing-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--gradient-green);
            border-radius: 2px;
        }

        .pricing-box {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 40px;
            border: 2px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px rgba(44, 122, 160, 0.2);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .pricing-item {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 25px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            transition: transform 0.3s ease;
        }

        .pricing-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.8);
        }

        .pricing-item h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--sky-blue-5);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-item h3 i {
            color: var(--luminous-green-dark);
            font-size: 1.5rem;
        }

        .pricing-detail {
            margin: 12px 0;
            padding-left: 10px;
            border-left: 3px solid var(--luminous-green-dark);
        }

        .pricing-detail .price {
            font-weight: 700;
            color: var(--luminous-green-dark);
            font-size: 1.2rem;
        }

        .pricing-note {
            margin-top: 30px;
            padding: 20px;
            background: var(--gradient-green);
            border-radius: 30px;
            color: white;
            text-align: center;
            font-weight: 500;
        }

        /* Smart Price Calculator Styles */
        .calculator-section {
            margin: 60px 0 40px;
        }

        .calculator-section h2 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 30px;
            position: relative;
        }

        .calculator-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--gradient-green);
            border-radius: 2px;
        }

        .smart-calculator {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 40px;
            border: 2px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px rgba(44, 122, 160, 0.2);
        }

        .calculator-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .calculator-header h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--sky-blue-5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .calculator-header h3 i {
            color: var(--luminous-green-dark);
            font-size: 2rem;
        }

        .calculator-header p {
            color: var(--text-soft);
            margin-top: 10px;
        }

        .calculator-input {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .weight-control {
            text-align: center;
        }

        .weight-control label {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--sky-blue-5);
            display: block;
            margin-bottom: 15px;
        }

        .weight-slider {
            width: 100%;
            height: 8px;
            -webkit-appearance: none;
            background: var(--sky-blue-2);
            border-radius: 10px;
            outline: none;
            margin: 20px 0;
        }

        .weight-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 25px;
            height: 25px;
            background: var(--gradient-green);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            border: 2px solid white;
        }

        .weight-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--luminous-green-dark);
            margin-top: 10px;
        }

        .weight-value span {
            font-size: 1rem;
            color: var(--text-soft);
        }

        .price-result {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .result-card {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .result-card:hover {
            transform: translateY(-5px);
        }

        .result-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .result-card .our-price-icon {
            color: var(--luminous-green-dark);
        }

        .result-card .traditional-icon {
            color: var(--sky-blue-4);
        }

        .result-card .savings-icon {
            color: #F59E0B;
        }

        .result-card h4 {
            font-size: 1rem;
            color: var(--text-soft);
            margin-bottom: 10px;
        }

        .result-card .price-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .result-card .savings-percent {
            font-size: 1.2rem;
            font-weight: 600;
            color: #F59E0B;
            margin-top: 5px;
        }

        .weight-tiers {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .tier-badge {
            background: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--sky-blue-5);
            border: 1px solid var(--sky-blue-3);
            transition: all 0.3s ease;
        }

        .tier-badge:hover {
            background: var(--gradient-green);
            color: white;
            transform: scale(1.05);
        }

        .calculator-btn {
            text-align: center;
        }

        .btn-book-now {
            background: var(--gradient-green);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 10px 25px rgba(111, 207, 151, 0.3);
        }

        .btn-book-now:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(39, 174, 96, 0.4);
        }

        @media (max-width: 968px) {
            .service-card {
                flex-direction: column;
            }
            
            .service-card.reverse {
                flex-direction: column;
            }
            
            .service-card.reverse .card-content-side {
                padding: 25px;
            }
            
            .card-image {
                padding: 20px;
            }
            
            .card-content-side {
                padding: 20px;
            }
            
            .carousel-track {
                height: 280px;
            }
            
            .services-title {
                margin-top: 120px;
            }

            .price-result {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .weight-tiers {
                gap: 10px;
            }

            .tier-badge {
                padding: 8px 15px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="home page images/logo.png" alt="Perfect Laundry Logo" class="logo-img">
                    <span class="logo-text">Perfect<span class="logo-highlight">Laundry</span></span>
                </div>
                
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item"><a href="home.php" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="pricing.php" class="nav-link active">Services</a></li>
                    <li class="nav-item"><a href="BookOnline.html" class="nav-link">Book Online</a></li>
                    <li class="nav-item"><a href="about.html" class="nav-link">About Us</a></li>
                    <li class="nav-item"><a href="contact.html" class="nav-link">Contact Us</a></li>
                </ul>
                
                <div class="nav-buttons">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-profile">
                            <button class="profile-btn" id="profileBtn">
                                <i class="fas fa-user-circle"></i>
                                <span class="profile-name"><?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="profile-dropdown" id="profileDropdown">
                                <div class="dropdown-header">
                                    <div class="dropdown-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="dropdown-info">
                                        <strong><?php echo htmlspecialchars($userName); ?></strong>
                                        <span><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="order-summary.html" class="dropdown-item">
                                    <i class="fas fa-history"></i>
                                    <span>My Orders</span>
                                </a>
                                <a href="account.php" class="dropdown-item">
                                    <i class="fas fa-user-edit"></i>
                                    <span>Account Settings</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="#" onclick="logout(); return false;" class="dropdown-item logout-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.html" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Log In
                        </a>
                        <a href="signup.html" class="btn-signup">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    <?php endif; ?>
                    <div class="hamburger" id="hamburger">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </div>
                </div>
            </div>
        </nav>

        <div class="services-title">
            <h1>Our Services</h1>
        </div>

        <!-- Dynamic Services Cards - Alternating Layout -->
        <?php 
        $service_count = count($services);
        for ($i = 0; $i < $service_count; $i++): 
            $service = $services[$i];
            $card_num = $i + 1;
            $is_reverse = ($i % 2 == 1); // Alternate: even index normal, odd index reverse
            
            // Get image paths from database
            $image1 = !empty($service['image_1']) ? $service['image_1'] : 'images/default-placeholder.jpg';
            $image2 = !empty($service['image_2']) ? $service['image_2'] : 'images/default-placeholder.jpg';
        ?>
        
        <div class="cards-row">
            <div class="service-card <?php echo $is_reverse ? 'reverse' : ''; ?>">
                <!-- Image Side -->
                <div class="card-image">
                    <div class="image-carousel">
                        <div class="carousel-container">
                            <div class="carousel-track" id="track<?php echo $card_num; ?>">
                                <div class="carousel-slide slide-1 active" style="background-image: url('<?php echo $image1; ?>');"></div>
                                <div class="carousel-slide slide-2" style="background-image: url('<?php echo $image2; ?>');"></div>
                                <div class="carousel-overlay"></div>
                            </div>
                            
                            <div class="carousel-arrow left" onclick="prevSlide('track<?php echo $card_num; ?>')">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="carousel-arrow right" onclick="nextSlide('track<?php echo $card_num; ?>')">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                            
                            <div class="carousel-dots" id="dots<?php echo $card_num; ?>">
                                <div class="dot active" onclick="showSlide('track<?php echo $card_num; ?>', 0)"></div>
                                <div class="dot" onclick="showSlide('track<?php echo $card_num; ?>', 1)"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Side -->
                <div class="card-content-side">
                    <div class="card-title">
                        <i class="<?php echo $service['icon']; ?>"></i> <?php echo htmlspecialchars($service['name']); ?>
                    </div>
                    
                    <div class="text-box">
                        <h2><?php echo htmlspecialchars($service['name']); ?></h2>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                    </div>
                    
                    <div class="text-box">
                        <span class="pricing-bold">Best Value Pricing</span>
                        <p>Pay by weight, not per piece. The smartest choice for your laundry needs.</p>
                    </div>
                    
                    <?php if ($service['pricing_type'] == 'press'): ?>
                    <div class="price-tag">
                        <i class="fas fa-iron"></i> <?php echo number_format($service['base_price'], 0); ?> LKR
                    </div>
                    <?php elseif ($service['pricing_type'] == 'shoe'): ?>
                    <div class="price-tag">
                        <i class="fas fa-shoe-prints"></i> <?php echo number_format($service['base_price'], 0); ?> LKR/pair
                    </div>
                    <?php else: ?>
                    <div class="price-tag">
                        <i class="fas fa-weight-hanging"></i> <?php echo number_format($service['base_price'], 0); ?> LKR/kg
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php endfor; ?>

        <!-- Pricing Details Section -->
        <div class="pricing-section">
            <h2>Pricing Details</h2>
            
            <div class="pricing-box">
                <div class="pricing-grid">
                    <?php foreach ($pricing_summary as $item): ?>
                    <div class="pricing-item">
                        <h3>
                            <i class="<?php echo htmlspecialchars($item['icon_class'] ?? ($item['category'] == 'shoe' ? 'fas fa-shoe-prints' : ($item['category'] == 'press' ? 'fas fa-iron' : 'fas fa-tag'))); ?>"></i> 
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        <div class="pricing-detail">
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <p class="price"><?php echo number_format($item['price'], 0); ?> LKR</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="pricing-note">
                    <i class="fas fa-info-circle"></i> 
                    All prices are in Sri Lankan Rupees (LKR). Base price is 1000 LKR per 1kg for most items. Express service and additional services have extra charges as mentioned above.
                </div>
            </div>
        </div>

        <!-- Smart Price Calculator Section - NEW FEATURE (NO CRUD) -->
        <div class="calculator-section">
            <h2>Smart Price Calculator</h2>
            
            <div class="smart-calculator">
                <div class="calculator-header">
                    <h3>
                        <i class="fas fa-calculator"></i> 
                        Calculate Your Laundry Cost
                    </h3>
                    <p>See how much you save with our weight-based pricing!</p>
                </div>

                <div class="calculator-input">
                    <div class="weight-control">
                        <label><i class="fas fa-weight-hanging"></i> Enter Laundry Weight (kg)</label>
                        <input type="range" id="weightSlider" class="weight-slider" min="0" max="20" step="0.5" value="5">
                        <div class="weight-value">
                            <span id="weightValue">5.0</span> kg
                        </div>
                    </div>
                </div>

                <div class="price-result">
                    <div class="result-card">
                        <i class="fas fa-star our-price-icon"></i>
                        <h4>Our Price (Weight-Based)</h4>
                        <div class="price-amount" id="ourPrice">5000 LKR</div>
                    </div>
                    <div class="result-card">
                        <i class="fas fa-store traditional-icon"></i>
                        <h4>Traditional Per-Piece</h4>
                        <div class="price-amount" id="traditionalPrice">~8250 LKR</div>
                    </div>
                    <div class="result-card">
                        <i class="fas fa-chart-line savings-icon"></i>
                        <h4>You Save</h4>
                        <div class="price-amount" id="savingsAmount">3250 LKR</div>
                        <div class="savings-percent" id="savingsPercent">(39%)</div>
                    </div>
                </div>

                <div class="weight-tiers">
                    <div class="tier-badge" onclick="setWeight(2)">📦 Small (1-3kg)</div>
                    <div class="tier-badge" onclick="setWeight(5)">🛍️ Medium (4-7kg)</div>
                    <div class="tier-badge" onclick="setWeight(10)">🧺 Large (8-12kg)</div>
                    <div class="tier-badge" onclick="setWeight(15)">🏭 Bulk (13-20kg)</div>
                </div>
            </div>
        </div>

        <hr class="divider">
    </div>

    <script>
        // Carousel functionality for multiple tracks
        function getTrackElements(trackId) {
            const track = document.getElementById(trackId);
            const container = track.closest('.carousel-container');
            const slides = track.querySelectorAll('.carousel-slide');
            const dots = container.querySelectorAll('.dot');
            return { track, slides, dots };
        }

        function showSlide(trackId, index) {
            const { slides, dots } = getTrackElements(trackId);
            
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function nextSlide(trackId) {
            const { slides } = getTrackElements(trackId);
            
            let currentIndex = 0;
            slides.forEach((slide, index) => {
                if (slide.classList.contains('active')) {
                    currentIndex = index;
                }
            });
            
            let newIndex = currentIndex + 1;
            if (newIndex >= slides.length) {
                newIndex = 0;
            }
            
            showSlide(trackId, newIndex);
        }

        function prevSlide(trackId) {
            const { slides } = getTrackElements(trackId);
            
            let currentIndex = 0;
            slides.forEach((slide, index) => {
                if (slide.classList.contains('active')) {
                    currentIndex = index;
                }
            });
            
            let newIndex = currentIndex - 1;
            if (newIndex < 0) {
                newIndex = slides.length - 1;
            }
            
            showSlide(trackId, newIndex);
        }

        // Initialize all slides
        window.onload = function() {
            <?php for ($i = 1; $i <= count($services); $i++): ?>
            showSlide('track<?php echo $i; ?>', 0);
            <?php endfor; ?>
        };

        // Smart Price Calculator Functions
        const slider = document.getElementById('weightSlider');
        const weightValue = document.getElementById('weightValue');
        const ourPrice = document.getElementById('ourPrice');
        const traditionalPrice = document.getElementById('traditionalPrice');
        const savingsAmount = document.getElementById('savingsAmount');
        const savingsPercent = document.getElementById('savingsPercent');
        
        // Average per-piece price per kg (approximate)
        const PER_PIECE_RATE = 1650;
        const OUR_RATE = 1000;
        
        function updateCalculator() {
            let kg = parseFloat(slider.value);
            weightValue.textContent = kg.toFixed(1);
            
            let ourPriceValue = kg * OUR_RATE;
            let traditionalPriceValue = kg * PER_PIECE_RATE;
            let saved = traditionalPriceValue - ourPriceValue;
            let percent = Math.round((saved / traditionalPriceValue) * 100);
            
            ourPrice.textContent = ourPriceValue.toLocaleString() + ' LKR';
            traditionalPrice.textContent = '~' + traditionalPriceValue.toLocaleString() + ' LKR';
            savingsAmount.textContent = saved.toLocaleString() + ' LKR';
            savingsPercent.textContent = '(' + percent + '%)';
        }
        
        function setWeight(kg) {
            slider.value = kg;
            updateCalculator();
        }
        
        function bookNow() {
            let kg = slider.value;
            window.location.href = 'BookOnline.html?weight=' + kg;
        }
        
        slider.addEventListener('input', updateCalculator);
        
        // Initialize calculator
        updateCalculator();

        // Hamburger menu toggle
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        
        if (hamburger) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }

        // Profile dropdown toggle
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        
        if (profileBtn) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
                profileBtn.classList.toggle('active');
            });
            
            document.addEventListener('click', (e) => {
                if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.remove('show');
                    profileBtn.classList.remove('active');
                }
            });
        }

        // Logout function
        async function logout() {
            try {
                localStorage.removeItem('perfect_laundry_user');
                localStorage.removeItem('perfect_laundry_session_token');
                
                const response = await fetch('logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                window.location.href = 'home.php';
            } catch (error) {
                window.location.href = 'home.php';
            }
        }
    </script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });
        
        particlesJS('particles-js', {
            particles: {
                number: { value: 40, density: { enable: true, value_area: 800 } },
                color: { value: '#7FC9E6' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: '#B8E2F2', opacity: 0.2, width: 1 },
                move: { enable: true, speed: 1.5, direction: 'none', random: true, straight: false }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' } }
            }
        });
    </script>
</body>
</html>