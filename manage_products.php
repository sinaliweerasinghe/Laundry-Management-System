<?php
require_once 'config/database.php';
$db = new Database();

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

// Get category statistics for reports
$category_stats_query = "SELECT 
                            pricing_type,
                            COUNT(*) as service_count,
                            AVG(base_price) as avg_price,
                            MIN(base_price) as min_price,
                            MAX(base_price) as max_price,
                            SUM(CASE WHEN additional_express_price IS NOT NULL THEN 1 ELSE 0 END) as has_express,
                            SUM(CASE WHEN additional_patches_price IS NOT NULL THEN 1 ELSE 0 END) as has_patches
                         FROM services 
                         GROUP BY pricing_type";
$category_stats_result = $db->query($category_stats_query);
$category_stats = [];
while ($row = $category_stats_result->fetch_assoc()) {
    $category_stats[] = $row;
}

// Pricing type labels
$pricing_type_labels = [
    'per_kg' => 'Per Kilogram',
    'press' => 'Press Service',
    'shoe' => 'Shoe Cleaning'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Perfect Laundry Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- html2pdf library for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        /* Your existing CSS from manage_products.html - keeping the same */
        /* Variables for Perfect Laundry theme colors */
        :root {
            --bg-dark: #0A1929;           /* Dark blue background */
            --bg-dark-light: #102a41;      /* Lighter dark blue for cards */
            --sky-blue-1: #E1F3FE;
            --sky-blue-2: #B8E2F2;
            --sky-blue-3: #7FC9E6;
            --sky-blue-4: #4AA3D1;
            --sky-blue-5: #2C7AA0;
            --luminous-green: #6FCF97;
            --luminous-green-dark: #27AE60;
            --text-light: #ffffff;
            --text-muted: #94A3B8;
            --border-color: #1E3A5F;
            
            --gradient-green: linear-gradient(135deg, #6FCF97, #27AE60);
            --gradient-blue: linear-gradient(135deg, #4AA3D1, #2C7AA0);
        }

        /* General Body and Base Styles */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(145deg, #0A1929 0%, #102a41 50%, #1a3f5c 100%);
            color: var(--text-light);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--text-light);
            margin-top: 0;
            margin-bottom: 15px;
        }

        /* Utility Class for Content Cards */
        .content-card {
            background: rgba(16, 42, 65, 0.7);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Dashboard Container Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styles - FIXED */
        .sidebar {
            width: 280px;
            background: rgba(10, 38, 71, 0.95);
            backdrop-filter: blur(15px);
            padding: 30px 0;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.3);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--luminous-green);
            border-radius: 10px;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 25px;
        }

        .sidebar-header h2 {
            font-size: 26px;
            background: var(--gradient-green);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .sidebar-nav ul li {
            margin-bottom: 5px;
        }

        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: var(--text-light);
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 500;
            margin: 0 10px;
            border-radius: 12px;
        }

        .sidebar-nav ul li a .icon {
            margin-right: 15px;
            width: 22px;
            text-align: center;
            color: var(--luminous-green);
        }

        .sidebar-nav ul li a:hover,
        .sidebar-nav ul li a.active {
            background: var(--gradient-green);
            color: white;
            box-shadow: 0 10px 20px rgba(111, 207, 151, 0.3);
        }

        .sidebar-nav ul li a:hover .icon,
        .sidebar-nav ul li a.active .icon {
            color: white;
        }

        /* Main Content Area Styles - WITH LEFT MARGIN for fixed sidebar */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            background: transparent;
            margin-left: 280px; /* Same as sidebar width */
            width: calc(100% - 280px);
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .main-header h1 {
            font-size: 32px;
            background: var(--gradient-green);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
        }

        /* Logo Styles */
        .logo-container {
            display: flex;
            align-items: center;
        }

        .dashboard-logo {
            font-size: 20px;
            font-weight: 600;
            color: white;
            padding: 10px 25px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .dashboard-logo i {
            color: var(--luminous-green);
            margin-right: 10px;
        }

        /* Page Title */
        .page-title {
            font-size: 24px;
            color: white;
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient-green);
            border-radius: 2px;
        }

        /* Tab Navigation - NEW */
        .tabs-container {
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 0;
        }
        
        .tab-btn {
            background: transparent;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            position: relative;
        }
        
        .tab-btn:hover {
            color: var(--luminous-green);
        }
        
        .tab-btn.active {
            color: var(--luminous-green);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-green);
            border-radius: 3px 3px 0 0;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Report Filters - NEW */
        .report-filters {
            background: rgba(16, 42, 65, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
        }
        
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: var(--luminous-green);
        }
        
        .btn-generate, .btn-export-csv {
            padding: 10px 25px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-generate {
            background: var(--gradient-green);
            color: white;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 207, 151, 0.3);
        }
        
        .btn-export-csv {
            background: rgba(74, 163, 209, 0.8);
            color: white;
        }
        
        .btn-export-csv:hover {
            background: var(--sky-blue-4);
            transform: translateY(-2px);
        }

        /* Stats Cards - NEW */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(16, 42, 65, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(16, 42, 65, 0.8);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .stat-header i {
            font-size: 2rem;
            color: var(--luminous-green);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Charts Container - NEW */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: rgba(16, 42, 65, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chart-card h3 {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        canvas {
            max-height: 300px;
        }

        /* Report Tables - NEW */
        .report-table-container {
            background: rgba(16, 42, 65, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
            margin-bottom: 30px;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-table th,
        .report-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .report-table th {
            color: var(--luminous-green);
            font-weight: 600;
            font-size: 14px;
        }
        
        .report-table td {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .report-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 5px 5px 5px 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 300px;
        }

        .search-box input {
            background: transparent;
            border: none;
            color: white;
            padding: 10px 0;
            width: 100%;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .search-box input::placeholder {
            color: var(--text-muted);
        }

        .search-box button {
            background: var(--gradient-green);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-box button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(111, 207, 151, 0.3);
        }

        .btn-add {
            background: var(--gradient-green);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(111, 207, 151, 0.4);
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .category-card {
            background: rgba(16, 42, 65, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-5px);
            background: rgba(16, 42, 65, 0.8);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .category-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-green);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .category-header h3 {
            font-size: 1.4rem;
            margin-bottom: 0;
            color: var(--luminous-green);
        }

        .price-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .price-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .price-label {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .price-value {
            color: var(--luminous-green);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .category-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-edit, .btn-delete {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-edit {
            background: rgba(74, 163, 209, 0.2);
            color: var(--sky-blue-4);
            border: 1px solid rgba(74, 163, 209, 0.3);
        }

        .btn-edit:hover {
            background: var(--sky-blue-4);
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: #EF4444;
            color: white;
            transform: translateY(-2px);
        }

        /* Pricing Summary Card */
        .pricing-summary {
            background: rgba(16, 42, 65, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 30px;
        }

        .pricing-summary h3 {
            font-size: 1.4rem;
            margin-bottom: 20px;
            color: var(--luminous-green);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-summary h3 i {
            color: var(--luminous-green-dark);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
        }

        .summary-item h4 {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .summary-price {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--gradient-green);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .summary-note {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(16, 42, 65, 0.98) 0%, rgba(10, 38, 71, 0.98) 100%);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            padding: 30px 35px;
            width: 100%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(111, 207, 151, 0.3);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--luminous-green);
            border-radius: 10px;
        }

        .modal-content h2 {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: var(--luminous-green);
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid rgba(111, 207, 151, 0.3);
            padding-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-group label i {
            margin-right: 8px;
            color: var(--luminous-green);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--luminous-green);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: 0 0 0 2px rgba(111, 207, 151, 0.2);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-group select option {
            background: #0A1929;
            color: white;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-save, .btn-cancel {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-save {
            background: var(--gradient-green);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(111, 207, 151, 0.3);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .helper-text {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 5px;
            display: block;
        }

        .image-preview {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .delete-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(239, 68, 68, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .current-images {
            margin: 15px 0;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
        }
        
        .current-images h4 {
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .pricing-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-add-pricing {
            background: var(--gradient-green);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-add-pricing:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 207, 151, 0.3);
        }

        .pricing-item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-pricing-edit, .btn-pricing-delete {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-pricing-edit {
            background: rgba(74, 163, 209, 0.2);
            color: var(--sky-blue-4);
            border: 1px solid rgba(74, 163, 209, 0.3);
        }

        .btn-pricing-edit:hover {
            background: var(--sky-blue-4);
            color: white;
        }

        .btn-pricing-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-pricing-delete:hover {
            background: #EF4444;
            color: white;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Perfect Laundry</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin-dashboard.html"><span class="icon"><i class="fas fa-chart-pie"></i></span> Dashboard</a></li>
                    <li><a href="manage_products.php" class="active"><span class="icon"><i class="fas fa-box"></i></span> Manage Products</a></li>
                    <li><a href="visit_admin.html"><span class="icon"><i class="fas fa-shopping-cart"></i></span> Manage Orders</a></li>
                    <li><a href="manage_customers.php"><span class="icon"><i class="fas fa-users"></i></span> Manage Customers</a></li>
                    <li><a href="DeliveryAdmin.html"><span class="icon"><i class="fas fa-clock"></i></span> Delivery Status</a></li>
                     <li><a href="manage_reviews.php"><span class="icon"><i class="fas fa-star"></i></span> Manage Reviews</a></li>
                    <li><a href="settings.html"><span class="icon"><i class="fas fa-cog"></i></span> Settings</a></li>
                    <li><a href="logout.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>Manage Products & Pricing</h1>
                <div class="logo-container">
                    <div class="dashboard-logo">
                        <i class="fas fa-tshirt"></i> Perfect Laundry
                    </div>
                </div>
            </header>

            <!-- Tab Navigation -->
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('products')">
                        <i class="fas fa-box"></i> Products & Pricing
                    </button>
                    <button class="tab-btn" onclick="switchTab('reports')">
                        <i class="fas fa-chart-line"></i> Pricing Reports
                    </button>
                </div>
            </div>

            <!-- Products Tab (YOUR ORIGINAL CONTENT - UNCHANGED) -->
            <div id="productsTab" class="tab-content active">
                <div class="action-bar">
                    <button class="btn-add" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Service
                    </button>
                </div>

                <h2 class="page-title">Laundry Services</h2>
                <div class="categories-grid" id="categoriesGrid">
                    <?php foreach ($services as $service): ?>
                    <div class="category-card" data-id="<?php echo $service['id']; ?>">
                        <div class="category-header">
                            <div class="category-icon"><i class="<?php echo $service['icon']; ?>"></i></div>
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                        </div>
                        <div class="price-info">
                            <?php if ($service['pricing_type'] == 'per_kg'): ?>
                                <div class="price-row">
                                    <span class="price-label">Base Price (per kg)</span>
                                    <span class="price-value"><?php echo number_format($service['base_price'], 0); ?> LKR</span>
                                </div>
                                <?php if ($service['additional_express_price']): ?>
                                <div class="price-row">
                                    <span class="price-label">Express 6hr (+<?php echo number_format($service['additional_express_price'], 0); ?>)</span>
                                    <span class="price-value"><?php echo number_format($service['base_price'] + $service['additional_express_price'], 0); ?> LKR/kg</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($service['additional_patches_price']): ?>
                                <div class="price-row">
                                    <span class="price-label">Patches (+<?php echo number_format($service['additional_patches_price'], 0); ?>)</span>
                                    <span class="price-value"><?php echo number_format($service['base_price'] + $service['additional_patches_price'], 0); ?> LKR/kg</span>
                                </div>
                                <?php endif; ?>
                            <?php elseif ($service['pricing_type'] == 'press'): ?>
                                <div class="price-row">
                                    <span class="price-label">Fixed Price</span>
                                    <span class="price-value"><?php echo number_format($service['base_price'], 0); ?> LKR</span>
                                </div>
                            <?php elseif ($service['pricing_type'] == 'shoe'): ?>
                                <div class="price-row">
                                    <span class="price-label">Per Pair (fixed)</span>
                                    <span class="price-value"><?php echo number_format($service['base_price'], 0); ?> LKR</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="category-actions">
                            <button class="btn-edit" onclick="openEditModal(<?php echo $service['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn-delete" onclick="deleteProduct(<?php echo $service['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="pricing-summary">
                    <div class="pricing-summary-header">
                        <h3><i class="fas fa-info-circle"></i> Pricing Overview</h3>
                        <button class="btn-add-pricing" onclick="openPricingModal()">
                            <i class="fas fa-plus"></i> Add Pricing Rule
                        </button>
                    </div>
                    <div class="summary-grid" id="pricingGrid">
                        <?php foreach ($pricing_summary as $item): ?>
                        <div class="summary-item" data-id="<?php echo $item['id']; ?>">
                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                            <div class="summary-price"><?php echo number_format($item['price'], 0); ?> LKR</div>
                            <div class="summary-note"><?php echo htmlspecialchars($item['description']); ?></div>
                            <div class="pricing-item-actions">
                                <button class="btn-pricing-edit" onclick="editPricingRule(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-pricing-delete" onclick="deletePricingRule(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Reports Tab (NEW - For Categorization & Pricing) -->
            <div id="reportsTab" class="tab-content">
                <!-- Report Filters -->
                <div class="report-filters">
                    <div class="filter-group">
                        <label><i class="fas fa-chart-bar"></i> Report Type</label>
                        <select id="reportType">
                            <option value="category_stats">Category Statistics</option>
                            <option value="service_list">All Services List</option>
                            <option value="pricing_rules">Pricing Rules Summary</option>
                            <option value="price_comparison">Price Comparison by Category</option>
                        </select>
                    </div>
                    <button class="btn-generate" onclick="generatePricingReport()">
                        <i class="fas fa-chart-line"></i> Generate Report
                    </button>
                    <button class="btn-export-csv" onclick="exportPricingReport()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid" id="reportStats">
                    <div class="stat-card">
                        <div class="stat-header">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-value" id="totalServices"><?php echo count($services); ?></div>
                        <div class="stat-label">Total Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-value" id="totalCategories"><?php echo count($category_stats); ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value" id="avgPrice"><?php 
                            $total = 0;
                            foreach($services as $s) $total += $s['base_price'];
                            echo number_format(count($services) > 0 ? $total/count($services) : 0, 0);
                        ?> LKR</div>
                        <div class="stat-label">Average Price</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value" id="totalPricingRules"><?php echo count($pricing_summary); ?></div>
                        <div class="stat-label">Pricing Rules</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-container">
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-pie"></i> Services by Category</h3>
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-bar"></i> Price Distribution by Category</h3>
                        <canvas id="priceChart"></canvas>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="report-table-container">
                    <h3><i class="fas fa-table"></i> <span id="reportTableTitle">Category Statistics</span></h3>
                    <div style="overflow-x: auto;">
                        <table class="report-table" id="reportTable">
                            <thead id="reportTableHead">
                                <!-- Dynamic headers will be inserted here -->
                            </thead>
                            <tbody id="reportTableBody">
                                <!-- Dynamic data will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal with Image Upload -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New Service</h2>
            <form id="productForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" id="serviceName" required>
                </div>
                <div class="form-group">
                    <label>Icon Class (Font Awesome)</label>
                    <input type="text" id="serviceIcon" value="fas fa-weight-scale">
                </div>
                <div class="form-group">
                    <label>Pricing Type</label>
                    <select id="pricingType">
                        <option value="per_kg">Per Kilogram (1000 LKR/kg)</option>
                        <option value="press">Press (500 LKR)</option>
                        <option value="shoe">Shoe Cleaning (1000 LKR/pair)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Base Price (LKR)</label>
                    <input type="number" id="basePrice" step="100" value="1000">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="serviceDesc" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Additional Express Price (optional)</label>
                    <input type="number" id="expressPrice" step="100" placeholder="e.g., 200">
                </div>
                <div class="form-group">
                    <label>Additional Patches Price (optional)</label>
                    <input type="number" id="patchesPrice" step="100" placeholder="e.g., 500">
                </div>
                
                <div class="form-group">
                    <label>Image 1 (Main)</label>
                    <input type="file" id="image1" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <div id="currentImage1" class="current-images" style="display:none;">
                        <h4>Current Image:</h4>
                        <div class="image-preview"></div>
                        <label><input type="checkbox" id="deleteImage1"> Delete this image</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Image 2 (Secondary)</label>
                    <input type="file" id="image2" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <div id="currentImage2" class="current-images" style="display:none;">
                        <h4>Current Image:</h4>
                        <div class="image-preview"></div>
                        <label><input type="checkbox" id="deleteImage2"> Delete this image</label>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-save" onclick="saveProduct()">Save</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pricing Summary Modal -->
    <div class="modal" id="pricingModal">
        <div class="modal-content">
            <h2 id="pricingModalTitle">Add Pricing Rule</h2>
            <form id="pricingForm">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Title</label>
                    <input type="text" id="pricingTitle" placeholder="e.g., Per Kilogram Base" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="pricingDescription" rows="2" placeholder="Describe this pricing rule..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Price (LKR)</label>
                        <input type="number" id="pricingPrice" step="100" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-icons"></i> Category</label>
                        <select id="pricingCategory">
                            <option value="per_kg">Per Kilogram</option>
                            <option value="shoe">Shoe Cleaning</option>
                            <option value="press">Press Service</option>
                            <option value="express">Express Service</option>
                            <option value="patches">Patches</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-icons"></i> Icon Class (Font Awesome)</label>
                    <input type="text" id="pricingIcon" value="fas fa-tag" placeholder="fas fa-tag">
                    <span class="helper-text">Example: fas fa-weight-scale, fas fa-clock, fas fa-plus-circle</span>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-save" onclick="savePricingRule()">Save</button>
                    <button type="button" class="btn-cancel" onclick="closePricingModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentEditId = null;
        let currentServiceData = null;
        let categoryChart = null;
        let priceChart = null;
        let currentReportData = null;
        
        const modal = document.getElementById('productModal');
        const apiUrl = 'api/products.php';

        // Pricing type labels
        const pricingTypeLabels = {
            'per_kg': 'Per Kilogram',
            'press': 'Press Service',
            'shoe': 'Shoe Cleaning'
        };

        // Validation helper functions
        function showValidationError(message) {
            alert('⚠️ Validation Error:\n' + message);
        }

        function showSuccessMessage(message) {
            alert('✅ Success:\n' + message);
        }

        function isValidIconClass(icon) {
            // Check if icon class follows Font Awesome pattern (starts with 'fas ', 'far ', 'fab ', etc.)
            const validPatterns = /^(fas|far|fal|fad|fab)\s+fa-[a-z0-9-]+$/i;
            return validPatterns.test(icon);
        }

        function isValidImageFile(file) {
            if (!file) return true;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!allowedTypes.includes(file.type)) {
                showValidationError('Invalid image type. Only JPEG, PNG, and WEBP images are allowed.');
                return false;
            }
            if (file.size > maxSize) {
                showValidationError('Image file size exceeds 5MB limit.');
                return false;
            }
            return true;
        }

        // Check if service name already exists
        function isServiceNameExists(name, excludeId = null) {
            const serviceCards = document.querySelectorAll('.category-card');
            let exists = false;
            
            serviceCards.forEach(card => {
                const serviceName = card.querySelector('h3').textContent.trim();
                const serviceId = card.getAttribute('data-id');
                
                // If editing, exclude the current service from check
                if (excludeId && serviceId == excludeId) {
                    return;
                }
                
                if (serviceName.toLowerCase() === name.toLowerCase()) {
                    exists = true;
                }
            });
            
            return exists;
        }

        // Tab Switching - FIXED (removed auto PDF generation)
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (tabName === 'products') {
                document.getElementById('productsTab').classList.add('active');
            } else {
                document.getElementById('reportsTab').classList.add('active');
                // REMOVED: generatePricingReport(); - This was causing auto PDF download
                // Instead, just update the on-screen table and charts without PDF
                updateReportTableOnLoad();
            }
        }

        // Add this new function to load data without PDF
        function updateReportTableOnLoad() {
            const reportType = document.getElementById('reportType').value;
            const services = <?php echo json_encode($services); ?>;
            const pricingSummary = <?php echo json_encode($pricing_summary); ?>;
            const categoryStats = <?php echo json_encode($category_stats); ?>;
            
            currentReportData = { services, pricingSummary, categoryStats, reportType };
            
            // Only update the on-screen table and charts - NO PDF
            updateReportTable(reportType, services, pricingSummary, categoryStats);
            updateCharts(services, categoryStats);
        }

        function generatePricingReport() {
            const reportType = document.getElementById('reportType').value;
            
            // Get data from PHP (already available)
            const services = <?php echo json_encode($services); ?>;
            const pricingSummary = <?php echo json_encode($pricing_summary); ?>;
            const categoryStats = <?php echo json_encode($category_stats); ?>;
            
            currentReportData = { services, pricingSummary, categoryStats, reportType };
            
            // Create a temporary div for PDF content
            const pdfContent = document.createElement('div');
            pdfContent.style.padding = '30px';
            pdfContent.style.backgroundColor = '#0A1929';
            pdfContent.style.color = '#ffffff';
            pdfContent.style.fontFamily = 'Poppins, sans-serif';
            pdfContent.style.width = '100%';
            
            // Add header
            pdfContent.innerHTML = `
                <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #6FCF97; padding-bottom: 20px;">
                    <h1 style="color: #6FCF97; margin-bottom: 10px;">📊 Perfect Laundry</h1>
                    <h2 style="color: #4AA3D1;">Pricing & Categorization Report</h2>
                    <p style="color: #94A3B8;">Generated: ${new Date().toLocaleString()}</p>
                    <p style="color: #94A3B8;">Report Type: ${getReportTypeLabel(reportType)}</p>
                </div>
            `;
            
            // Add statistics summary
            pdfContent.innerHTML += `
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
                    <div style="background: rgba(16, 42, 65, 0.8); padding: 15px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 24px; color: #6FCF97;">${services.length}</div>
                        <div style="font-size: 12px; color: #94A3B8;">Total Services</div>
                    </div>
                    <div style="background: rgba(16, 42, 65, 0.8); padding: 15px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 24px; color: #6FCF97;">${categoryStats.length}</div>
                        <div style="font-size: 12px; color: #94A3B8;">Categories</div>
                    </div>
                    <div style="background: rgba(16, 42, 65, 0.8); padding: 15px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 24px; color: #6FCF97;">${pricingSummary.length}</div>
                        <div style="font-size: 12px; color: #94A3B8;">Pricing Rules</div>
                    </div>
                    <div style="background: rgba(16, 42, 65, 0.8); padding: 15px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 24px; color: #6FCF97;">${Math.round(services.reduce((sum, s) => sum + Number(s.base_price), 0) / services.length)}</div>
                        <div style="font-size: 12px; color: #94A3B8;">Avg Price (LKR)</div>
                    </div>
                </div>
            `;
            
            // Add table based on report type
            if (reportType === 'category_stats') {
                pdfContent.innerHTML += `
                    <h3 style="color: #6FCF97; margin-bottom: 15px;">📈 Category Statistics</h3>
                    <table style="width: 100%; border-collapse: collapse; background: rgba(16, 42, 65, 0.6); border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: #6FCF97; color: #0A1929;">
                                <th style="padding: 12px; text-align: left;">Category</th>
                                <th style="padding: 12px; text-align: left;">Services</th>
                                <th style="padding: 12px; text-align: left;">Avg Price (LKR)</th>
                                <th style="padding: 12px; text-align: left;">Min Price</th>
                                <th style="padding: 12px; text-align: left;">Max Price</th>
                                <th style="padding: 12px; text-align: left;">Express</th>
                                <th style="padding: 12px; text-align: left;">Patches</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${categoryStats.map(stat => `
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 10px;">${pricingTypeLabels[stat.pricing_type] || stat.pricing_type}</td>
                                    <td style="padding: 10px;">${stat.service_count}</td>
                                    <td style="padding: 10px;">${Math.round(stat.avg_price).toLocaleString()}</td>
                                    <td style="padding: 10px;">${Math.round(stat.min_price).toLocaleString()}</td>
                                    <td style="padding: 10px;">${Math.round(stat.max_price).toLocaleString()}</td>
                                    <td style="padding: 10px;">${stat.has_express > 0 ? '✅ Yes' : '❌ No'}</td>
                                    <td style="padding: 10px;">${stat.has_patches > 0 ? '✅ Yes' : '❌ No'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } 
            else if (reportType === 'service_list') {
                pdfContent.innerHTML += `
                    <h3 style="color: #6FCF97; margin-bottom: 15px;">📋 All Services List</h3>
                    <table style="width: 100%; border-collapse: collapse; background: rgba(16, 42, 65, 0.6); border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: #6FCF97; color: #0A1929;">
                                <th style="padding: 12px; text-align: left;">#</th>
                                <th style="padding: 12px; text-align: left;">Service Name</th>
                                <th style="padding: 12px; text-align: left;">Category</th>
                                <th style="padding: 12px; text-align: left;">Base Price</th>
                                <th style="padding: 12px; text-align: left;">Express</th>
                                <th style="padding: 12px; text-align: left;">Patches</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${services.map((service, index) => `
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 10px;">${index + 1}</td>
                                    <td style="padding: 10px;">${escapeHtml(service.name)}</td>
                                    <td style="padding: 10px;">${pricingTypeLabels[service.pricing_type] || service.pricing_type}</td>
                                    <td style="padding: 10px;">${Number(service.base_price).toLocaleString()} LKR</td>
                                    <td style="padding: 10px;">${service.additional_express_price ? Number(service.additional_express_price).toLocaleString() + ' LKR' : '-'}</td>
                                    <td style="padding: 10px;">${service.additional_patches_price ? Number(service.additional_patches_price).toLocaleString() + ' LKR' : '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
            else if (reportType === 'pricing_rules') {
                pdfContent.innerHTML += `
                    <h3 style="color: #6FCF97; margin-bottom: 15px;">💰 Pricing Rules Summary</h3>
                    <table style="width: 100%; border-collapse: collapse; background: rgba(16, 42, 65, 0.6); border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: #6FCF97; color: #0A1929;">
                                <th style="padding: 12px; text-align: left;">#</th>
                                <th style="padding: 12px; text-align: left;">Title</th>
                                <th style="padding: 12px; text-align: left;">Description</th>
                                <th style="padding: 12px; text-align: left;">Price</th>
                                <th style="padding: 12px; text-align: left;">Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pricingSummary.map((rule, index) => `
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 10px;">${index + 1}</td>
                                    <td style="padding: 10px;">${escapeHtml(rule.title)}</td>
                                    <td style="padding: 10px;">${escapeHtml(rule.description || '-')}</td>
                                    <td style="padding: 10px;">${Number(rule.price).toLocaleString()} LKR</td>
                                    <td style="padding: 10px;">${escapeHtml(rule.category)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
            else if (reportType === 'price_comparison') {
                pdfContent.innerHTML += `
                    <h3 style="color: #6FCF97; margin-bottom: 15px;">📊 Price Comparison by Category</h3>
                    <p style="color: #94A3B8; margin-bottom: 15px; font-size: 14px;">Showing base vs express vs patches pricing comparison</p>
                    <table style="width: 100%; border-collapse: collapse; background: rgba(16, 42, 65, 0.6); border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: #6FCF97; color: #0A1929;">
                                <th style="padding: 12px; text-align: left;">Service Name</th>
                                <th style="padding: 12px; text-align: left;">Category</th>
                                <th style="padding: 12px; text-align: left;">Base Price</th>
                                <th style="padding: 12px; text-align: left;">+ Express</th>
                                <th style="padding: 12px; text-align: left;">+ Patches</th>
                                <th style="padding: 12px; text-align: left;">Express + Patches</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${services.map(service => {
                                const expressPrice = service.additional_express_price ? Number(service.base_price) + Number(service.additional_express_price) : '-';
                                const patchesPrice = service.additional_patches_price ? Number(service.base_price) + Number(service.additional_patches_price) : '-';
                                const bothPrice = (service.additional_express_price && service.additional_patches_price) ? 
                                    Number(service.base_price) + Number(service.additional_express_price) + Number(service.additional_patches_price) : '-';
                                return `
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <td style="padding: 10px;">${escapeHtml(service.name)}</td>
                                        <td style="padding: 10px;">${pricingTypeLabels[service.pricing_type] || service.pricing_type}</td>
                                        <td style="padding: 10px;">${Number(service.base_price).toLocaleString()} LKR</td>
                                        <td style="padding: 10px;">${expressPrice !== '-' ? expressPrice.toLocaleString() + ' LKR' : '-'}</td>
                                        <td style="padding: 10px;">${patchesPrice !== '-' ? patchesPrice.toLocaleString() + ' LKR' : '-'}</td>
                                        <td style="padding: 10px;">${bothPrice !== '-' ? bothPrice.toLocaleString() + ' LKR' : '-'}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                `;
            }
            
            // Add footer
            pdfContent.innerHTML += `
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); color: #94A3B8; font-size: 12px;">
                    <p>Perfect Laundry Management System - Confidential Report</p>
                    <p>© ${new Date().getFullYear()} Perfect Laundry. All rights reserved.</p>
                </div>
            `;
            
            // Generate PDF
            const opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: `pricing_report_${reportType}_${new Date().toISOString().split('T')[0]}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, backgroundColor: '#0A1929' },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            
            // Show loading message
            const btn = document.querySelector('.btn-generate');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            btn.disabled = true;
            
            // Generate and download PDF
            html2pdf().set(opt).from(pdfContent).save().then(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Error generating PDF. Please try again.');
            });
            
            // Also update the on-screen table (existing functionality)
            updateReportTable(reportType, services, pricingSummary, categoryStats);
            updateCharts(services, categoryStats);
        }

        // Helper function to get report type label
        function getReportTypeLabel(reportType) {
            const labels = {
                'category_stats': 'Category Statistics',
                'service_list': 'All Services List',
                'pricing_rules': 'Pricing Rules Summary',
                'price_comparison': 'Price Comparison'
            };
            return labels[reportType] || reportType;
        }

        function updateReportTable(reportType, services, pricingSummary, categoryStats) {
            const tableHead = document.getElementById('reportTableHead');
            const tableBody = document.getElementById('reportTableBody');
            const tableTitle = document.getElementById('reportTableTitle');
            
            if (reportType === 'category_stats') {
                tableTitle.textContent = 'Category Statistics';
                tableHead.innerHTML = `
                    <tr>
                        <th>Category</th>
                        <th>Services Count</th>
                        <th>Avg Price (LKR)</th>
                        <th>Min Price (LKR)</th>
                        <th>Max Price (LKR)</th>
                        <th>Has Express</th>
                        <th>Has Patches</th>
                    </tr>
                `;
                
                tableBody.innerHTML = categoryStats.map(stat => `
                    <tr>
                        <td>${pricingTypeLabels[stat.pricing_type] || stat.pricing_type}</td>
                        <td>${stat.service_count}</td>
                        <td>${Math.round(stat.avg_price).toLocaleString()}</td>
                        <td>${Math.round(stat.min_price).toLocaleString()}</td>
                        <td>${Math.round(stat.max_price).toLocaleString()}</td>
                        <td>${stat.has_express} ${stat.has_express > 0 ? '✅' : '❌'}</td>
                        <td>${stat.has_patches} ${stat.has_patches > 0 ? '✅' : '❌'}</td>
                    </tr>
                `).join('');
                
            } else if (reportType === 'service_list') {
                tableTitle.textContent = 'All Services List';
                tableHead.innerHTML = `
                    <tr>
                        <th>#</th>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Base Price (LKR)</th>
                        <th>Express Price</th>
                        <th>Patches Price</th>
                        <th>Icon</th>
                    </tr>
                `;
                
                tableBody.innerHTML = services.map((service, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(service.name)}</td>
                        <td>${pricingTypeLabels[service.pricing_type] || service.pricing_type}</td>
                        <td>${Number(service.base_price).toLocaleString()}</td>
                        <td>${service.additional_express_price ? Number(service.additional_express_price).toLocaleString() : '-'}</td>
                        <td>${service.additional_patches_price ? Number(service.additional_patches_price).toLocaleString() : '-'}</td>
                        <td><i class="${service.icon}"></i> ${service.icon}</td>
                    </tr>
                `).join('');
                
            } else if (reportType === 'pricing_rules') {
                tableTitle.textContent = 'Pricing Rules Summary';
                tableHead.innerHTML = `
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price (LKR)</th>
                        <th>Category</th>
                        <th>Icon</th>
                    </tr>
                `;
                
                tableBody.innerHTML = pricingSummary.map((rule, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(rule.title)}</td>
                        <td>${escapeHtml(rule.description || '-')}</td>
                        <td>${Number(rule.price).toLocaleString()}</td>
                        <td>${escapeHtml(rule.category)}</td>
                        <td><i class="${rule.icon_class}"></i> ${rule.icon_class}</td>
                    </tr>
                `).join('');
                
            } else if (reportType === 'price_comparison') {
                tableTitle.textContent = 'Price Comparison by Category';
                tableHead.innerHTML = `
                    <tr>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Base Price (LKR)</th>
                        <th>With Express (LKR)</th>
                        <th>With Patches (LKR)</th>
                        <th>Express + Patches (LKR)</th>
                    </tr>
                `;
                
                tableBody.innerHTML = services.map(service => {
                    const expressPrice = service.additional_express_price ? Number(service.base_price) + Number(service.additional_express_price) : '-';
                    const patchesPrice = service.additional_patches_price ? Number(service.base_price) + Number(service.additional_patches_price) : '-';
                    const bothPrice = (service.additional_express_price && service.additional_patches_price) ? 
                        Number(service.base_price) + Number(service.additional_express_price) + Number(service.additional_patches_price) : '-';
                    
                    return `
                        <tr>
                            <td>${escapeHtml(service.name)}</td>
                            <td>${pricingTypeLabels[service.pricing_type] || service.pricing_type}</td>
                            <td>${Number(service.base_price).toLocaleString()}</td>
                            <td>${expressPrice !== '-' ? expressPrice.toLocaleString() : '-'}</td>
                            <td>${patchesPrice !== '-' ? patchesPrice.toLocaleString() : '-'}</td>
                            <td>${bothPrice !== '-' ? bothPrice.toLocaleString() : '-'}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // Update Charts
        function updateCharts(services, categoryStats) {
            // Category Chart (Pie)
            const ctx1 = document.getElementById('categoryChart').getContext('2d');
            if (categoryChart) categoryChart.destroy();
            
            const categoryCounts = {};
            services.forEach(service => {
                const cat = pricingTypeLabels[service.pricing_type] || service.pricing_type;
                categoryCounts[cat] = (categoryCounts[cat] || 0) + 1;
            });
            
            categoryChart = new Chart(ctx1, {
                type: 'pie',
                data: {
                    labels: Object.keys(categoryCounts),
                    datasets: [{
                        data: Object.values(categoryCounts),
                        backgroundColor: ['#6FCF97', '#4AA3D1', '#27AE60', '#2C7AA0', '#7FC9E6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#ffffff' }
                        }
                    }
                }
            });
            
            // Price Chart (Bar)
            const ctx2 = document.getElementById('priceChart').getContext('2d');
            if (priceChart) priceChart.destroy();
            
            const categoryPrices = {};
            categoryStats.forEach(stat => {
                const cat = pricingTypeLabels[stat.pricing_type] || stat.pricing_type;
                categoryPrices[cat] = Math.round(stat.avg_price);
            });
            
            priceChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: Object.keys(categoryPrices),
                    datasets: [{
                        label: 'Average Price (LKR)',
                        data: Object.values(categoryPrices),
                        backgroundColor: '#6FCF97',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            labels: { color: '#ffffff' }
                        }
                    },
                    scales: {
                        y: {
                            ticks: { color: '#94A3B8' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        x: {
                            ticks: { color: '#94A3B8' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    }
                }
            });
        }

        // Export Report to CSV
        function exportPricingReport() {
            if (!currentReportData) {
                alert('Please generate a report first');
                return;
            }
            
            const reportType = document.getElementById('reportType').value;
            let csvContent = "";
            
            if (reportType === 'category_stats') {
                csvContent = "Category,Services Count,Avg Price (LKR),Min Price (LKR),Max Price (LKR),Has Express,Has Patches\n";
                currentReportData.categoryStats.forEach(stat => {
                    csvContent += `${pricingTypeLabels[stat.pricing_type] || stat.pricing_type},${stat.service_count},${Math.round(stat.avg_price)},${Math.round(stat.min_price)},${Math.round(stat.max_price)},${stat.has_express > 0 ? 'Yes' : 'No'},${stat.has_patches > 0 ? 'Yes' : 'No'}\n`;
                });
            } else if (reportType === 'service_list') {
                csvContent = "Service Name,Category,Base Price (LKR),Express Price,Patches Price,Icon\n";
                currentReportData.services.forEach(service => {
                    csvContent += `"${service.name}",${pricingTypeLabels[service.pricing_type] || service.pricing_type},${service.base_price},${service.additional_express_price || '-'},${service.additional_patches_price || '-'},${service.icon}\n`;
                });
            } else if (reportType === 'pricing_rules') {
                csvContent = "Title,Description,Price (LKR),Category,Icon\n";
                currentReportData.pricingSummary.forEach(rule => {
                    csvContent += `"${rule.title}","${rule.description || ''}",${rule.price},${rule.category},${rule.icon_class}\n`;
                });
            } else if (reportType === 'price_comparison') {
                csvContent = "Service Name,Category,Base Price (LKR),With Express (LKR),With Patches (LKR),Express + Patches (LKR)\n";
                currentReportData.services.forEach(service => {
                    const expressPrice = service.additional_express_price ? Number(service.base_price) + Number(service.additional_express_price) : '-';
                    const patchesPrice = service.additional_patches_price ? Number(service.base_price) + Number(service.additional_patches_price) : '-';
                    const bothPrice = (service.additional_express_price && service.additional_patches_price) ? 
                        Number(service.base_price) + Number(service.additional_express_price) + Number(service.additional_patches_price) : '-';
                    csvContent += `"${service.name}",${pricingTypeLabels[service.pricing_type] || service.pricing_type},${service.base_price},${expressPrice},${patchesPrice},${bothPrice}\n`;
                });
            }
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pricing_report_${reportType}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Helper Functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Product Management Functions (YOUR ORIGINAL CODE - UNCHANGED)
        function openAddModal() {
            currentEditId = null;
            currentServiceData = null;
            document.getElementById('modalTitle').textContent = 'Add New Service';
            document.getElementById('serviceName').value = '';
            document.getElementById('serviceIcon').value = 'fas fa-weight-scale';
            document.getElementById('pricingType').value = 'per_kg';
            document.getElementById('basePrice').value = '1000';
            document.getElementById('serviceDesc').value = '';
            document.getElementById('expressPrice').value = '';
            document.getElementById('patchesPrice').value = '';
            
            document.getElementById('image1').value = '';
            document.getElementById('image2').value = '';
            document.getElementById('currentImage1').style.display = 'none';
            document.getElementById('currentImage2').style.display = 'none';
            document.getElementById('deleteImage1').checked = false;
            document.getElementById('deleteImage2').checked = false;
            
            modal.classList.add('active');
        }

        function openEditModal(id) {
            currentEditId = id;
            document.getElementById('modalTitle').textContent = 'Edit Service';
            
            fetch(`${apiUrl}?id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        currentServiceData = result.data;
                        const service = result.data;
                        document.getElementById('serviceName').value = service.name;
                        document.getElementById('serviceIcon').value = service.icon;
                        document.getElementById('pricingType').value = service.pricing_type;
                        document.getElementById('basePrice').value = service.base_price;
                        document.getElementById('serviceDesc').value = service.description || '';
                        document.getElementById('expressPrice').value = service.additional_express_price || '';
                        document.getElementById('patchesPrice').value = service.additional_patches_price || '';
                        
                        if (service.image_1) {
                            document.getElementById('currentImage1').style.display = 'block';
                            document.getElementById('currentImage1').querySelector('.image-preview').innerHTML = 
                                `<div class="preview-item"><img src="${service.image_1}" alt="Current Image 1"></div>`;
                        } else {
                            document.getElementById('currentImage1').style.display = 'none';
                        }
                        
                        if (service.image_2) {
                            document.getElementById('currentImage2').style.display = 'block';
                            document.getElementById('currentImage2').querySelector('.image-preview').innerHTML = 
                                `<div class="preview-item"><img src="${service.image_2}" alt="Current Image 2"></div>`;
                        } else {
                            document.getElementById('currentImage2').style.display = 'none';
                        }
                        
                        modal.classList.add('active');
                    } else {
                        alert('Error loading service: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading service data');
                });
        }

        function closeModal() {
            modal.classList.remove('active');
            currentEditId = null;
            currentServiceData = null;
        }

        function saveProduct() {
            const serviceName = document.getElementById('serviceName').value.trim();
            const serviceIcon = document.getElementById('serviceIcon').value.trim();
            const basePrice = parseFloat(document.getElementById('basePrice').value);
            const expressPriceRaw = document.getElementById('expressPrice').value.trim();
            const patchesPriceRaw = document.getElementById('patchesPrice').value.trim();
            const expressPrice = expressPriceRaw ? parseFloat(expressPriceRaw) : null;
            const patchesPrice = patchesPriceRaw ? parseFloat(patchesPriceRaw) : null;
            const image1 = document.getElementById('image1').files[0];
            const image2 = document.getElementById('image2').files[0];
            
            // VALIDATION 1: Service Name is required and not empty
            if (!serviceName) {
                showValidationError('Service Name is required.');
                return;
            }
            
            // VALIDATION 2: Service Name minimum length
            if (serviceName.length < 3) {
                showValidationError('Service Name must be at least 3 characters long.');
                return;
            }
            
            // VALIDATION 3: Service Name maximum length
            if (serviceName.length > 100) {
                showValidationError('Service Name must not exceed 100 characters.');
                return;
            }
            
            // VALIDATION 4: Service Name must contain only valid characters (letters, numbers, spaces, and basic punctuation)
            const nameRegex = /^[a-zA-Z0-9\s\-',.&()]+$/;
            if (!nameRegex.test(serviceName)) {
                showValidationError('Service Name contains invalid characters. Use only letters, numbers, spaces, and basic punctuation.');
                return;
            }
            
            // VALIDATION 5: Check if service name already exists
            if (isServiceNameExists(serviceName, currentEditId)) {
                showValidationError('Service "' + serviceName + '" already exists! Please use a different name.');
                return;
            }
            
            // VALIDATION 6: Icon Class validation
            if (!serviceIcon) {
                showValidationError('Icon Class is required.');
                return;
            }
            
            if (!isValidIconClass(serviceIcon)) {
                showValidationError('Invalid Icon Class format. It should follow Font Awesome pattern like "fas fa-weight-scale" or "far fa-star".');
                return;
            }
            
            // VALIDATION 7: Base Price validation
            if (isNaN(basePrice) || basePrice <= 0) {
                showValidationError('Base Price must be a valid positive number greater than 0.');
                return;
            }
            
            if (basePrice > 999999) {
                showValidationError('Base Price cannot exceed 999,999 LKR.');
                return;
            }
            
            // VALIDATION 8: Express Price validation (if provided)
            if (expressPriceRaw && (isNaN(expressPrice) || expressPrice <= 0)) {
                showValidationError('Express Price must be a valid positive number if provided.');
                return;
            }
            
            if (expressPrice && expressPrice > 99999) {
                showValidationError('Express Price cannot exceed 99,999 LKR.');
                return;
            }
            
            // VALIDATION 9: Patches Price validation (if provided)
            if (patchesPriceRaw && (isNaN(patchesPrice) || patchesPrice <= 0)) {
                showValidationError('Patches Price must be a valid positive number if provided.');
                return;
            }
            
            if (patchesPrice && patchesPrice > 99999) {
                showValidationError('Patches Price cannot exceed 99,999 LKR.');
                return;
            }
            
            // VALIDATION 10: Image file validation (if uploaded)
            if (image1 && !isValidImageFile(image1)) {
                return;
            }
            if (image2 && !isValidImageFile(image2)) {
                return;
            }
            
            // VALIDATION 11: Description length (if provided)
            const description = document.getElementById('serviceDesc').value.trim();
            if (description && description.length > 500) {
                showValidationError('Description must not exceed 500 characters.');
                return;
            }
            
            // All validations passed - proceed with form submission
            const formData = new FormData();
            
            formData.append('name', serviceName);
            formData.append('icon', serviceIcon);
            formData.append('pricing_type', document.getElementById('pricingType').value);
            formData.append('base_price', basePrice);
            formData.append('description', description);
            formData.append('additional_express_price', expressPrice ? expressPrice : '');
            formData.append('additional_patches_price', patchesPrice ? patchesPrice : '');
            
            if (image1) {
                formData.append('image_1', image1);
            }
            
            if (image2) {
                formData.append('image_2', image2);
            }
            
            if (currentEditId) {
                if (document.getElementById('deleteImage1') && document.getElementById('deleteImage1').checked) {
                    formData.append('delete_image_1', 'true');
                }
                if (document.getElementById('deleteImage2') && document.getElementById('deleteImage2').checked) {
                    formData.append('delete_image_2', 'true');
                }
                formData.append('_method', 'PUT');
            }
            
            let url = apiUrl;
            if (currentEditId) {
                url = `${apiUrl}?id=${currentEditId}`;
            }
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccessMessage(result.message);
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving service');
            });
        }

        function deleteProduct(id) {
            // VALIDATION: Confirm deletion before proceeding
            if (confirm('⚠️ Are you sure you want to delete this service?\n\nThis action cannot be undone and will permanently delete:\n- All service data\n- Associated images\n- Related pricing information\n\nAre you sure you want to continue?')) {
                fetch(`${apiUrl}?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSuccessMessage(result.message);
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting service');
                });
            }
        }

        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.category-card');
            
            cards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                if (title.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === pricingModal) {
                closePricingModal();
            }
        }

        // Pricing Summary Variables
        let currentPricingId = null;
        const pricingModal = document.getElementById('pricingModal');

        function openPricingModal() {
            currentPricingId = null;
            document.getElementById('pricingModalTitle').textContent = 'Add Pricing Rule';
            document.getElementById('pricingTitle').value = '';
            document.getElementById('pricingDescription').value = '';
            document.getElementById('pricingPrice').value = '';
            document.getElementById('pricingCategory').value = 'per_kg';
            document.getElementById('pricingIcon').value = 'fas fa-tag';
            pricingModal.classList.add('active');
        }

        function closePricingModal() {
            pricingModal.classList.remove('active');
            currentPricingId = null;
        }

        function editPricingRule(id) {
            currentPricingId = id;
            document.getElementById('pricingModalTitle').textContent = 'Edit Pricing Rule';
            
            // FIXED: Use proper endpoint for getting single pricing rule
            fetch(`${apiUrl}?type=pricing_summary&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const item = result.data;
                        document.getElementById('pricingTitle').value = item.title || '';
                        document.getElementById('pricingDescription').value = item.description || '';
                        document.getElementById('pricingPrice').value = item.price || '';
                        document.getElementById('pricingCategory').value = item.category || 'per_kg';
                        document.getElementById('pricingIcon').value = item.icon_class || 'fas fa-tag';
                        pricingModal.classList.add('active');
                    } else {
                        alert('Error loading pricing rule: ' + (result.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading pricing rule. Please check the API endpoint.');
                });
        }

        function savePricingRule() {
            const title = document.getElementById('pricingTitle').value.trim();
            const description = document.getElementById('pricingDescription').value.trim();
            const price = parseFloat(document.getElementById('pricingPrice').value);
            const category = document.getElementById('pricingCategory').value;
            const iconClass = document.getElementById('pricingIcon').value.trim();
            
            // VALIDATION 1: Title is required
            if (!title) {
                showValidationError('Price Rule Title is required.');
                return;
            }
            
            // VALIDATION 2: Title length validation
            if (title.length < 3) {
                showValidationError('Title must be at least 3 characters long.');
                return;
            }
            
            if (title.length > 100) {
                showValidationError('Title must not exceed 100 characters.');
                return;
            }
            
            // VALIDATION 3: Title character validation
            const titleRegex = /^[a-zA-Z0-9\s\-',.&()]+$/;
            if (!titleRegex.test(title)) {
                showValidationError('Title contains invalid characters. Use only letters, numbers, spaces, and basic punctuation.');
                return;
            }
            
            // VALIDATION 4: Price validation
            if (isNaN(price) || price <= 0) {
                showValidationError('Price must be a valid positive number greater than 0.');
                return;
            }
            
            if (price > 999999) {
                showValidationError('Price cannot exceed 999,999 LKR.');
                return;
            }
            
            // VALIDATION 5: Icon class validation
            if (!iconClass) {
                showValidationError('Icon Class is required.');
                return;
            }
            
            if (!isValidIconClass(iconClass)) {
                showValidationError('Invalid Icon Class format. It should follow Font Awesome pattern like "fas fa-tag" or "far fa-clock".');
                return;
            }
            
            // VALIDATION 6: Description length (if provided)
            if (description && description.length > 500) {
                showValidationError('Description must not exceed 500 characters.');
                return;
            }
            
            // All validations passed
            const pricingData = {
                title: title,
                description: description,
                price: price,
                category: category,
                icon_class: iconClass
            };
            
            // FIXED: Use correct endpoint for pricing summary
            let url = `${apiUrl}?type=pricing_summary`;
            let method = 'POST';
            
            if (currentPricingId) {
                url = `${apiUrl}?type=pricing_summary&id=${currentPricingId}`;
                method = 'PUT';
            }
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(pricingData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccessMessage(result.message || 'Pricing rule saved successfully!');
                    closePricingModal();
                    // FIXED: Auto refresh the page after successful save
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to save pricing rule'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving pricing rule. Please check the API endpoint.');
            });
        }

        function deletePricingRule(id) {
            // VALIDATION: Confirm deletion with specific message for pricing rule
            if (confirm('⚠️ Are you sure you want to delete this pricing rule?\n\nThis action will only delete the pricing rule, not any services.\n\nAre you sure you want to continue?')) {
                // FIXED: Use correct endpoint for deleting pricing rule only
                fetch(`${apiUrl}?type=pricing_summary&id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSuccessMessage(result.message || 'Pricing rule deleted successfully!');
                        // FIXED: Auto refresh after deletion
                        location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'Failed to delete pricing rule'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting pricing rule. Please check the API endpoint.');
                });
            }
        }
    </script>
</body>
</html>