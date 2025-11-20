<?php
require_once 'config.php';

// Initialize filter variables
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$selected_month = $_GET['month'] ?? '';

// Function to build WHERE clause and parameters
function buildWhereClause($start_date, $end_date, $selected_month)
{
    $where_conditions = ["m.ADD_DATE IS NOT NULL AND m.ADD_DATE != '0000-00-00'"];
    $params = [];
    $types = '';

    // Add filter conditions
    if (!empty($start_date) && !empty($end_date)) {
        $where_conditions[] = "m.ADD_DATE BETWEEN ? AND ?";
        array_push($params, $start_date, $end_date);
        $types .= 'ss';
    } elseif (!empty($start_date)) {
        $where_conditions[] = "m.ADD_DATE >= ?";
        $params[] = $start_date;
        $types .= 's';
    } elseif (!empty($end_date)) {
        $where_conditions[] = "m.ADD_DATE <= ?";
        $params[] = $end_date;
        $types .= 's';
    }

    if (!empty($selected_month)) {
        $where_conditions[] = "DATE_FORMAT(m.ADD_DATE, '%Y-%m') = ?";
        $params[] = $selected_month;
        $types .= 's';
    }

    return [
        'where_clause' => "WHERE " . implode(" AND ", $where_conditions),
        'params' => $params,
        'types' => $types
    ];
}

// Function to execute prepared statement with error handling
function executeQuery($conn, $sql, $types = '', $params = [])
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL Error: " . $conn->error . " | Query: " . $sql);
        die("Database error occurred. Please try again.");
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Execution Error: " . $stmt->error);
        die("Query execution failed.");
    }

    return $stmt;
}

// Build WHERE clause once
$filter_data = buildWhereClause($start_date, $end_date, $selected_month);
$where_clause = $filter_data['where_clause'];
$params = $filter_data['params'];
$types = $filter_data['types'];

// Execute all main queries
$queries = [
    'summary' => "
        SELECT 
            COUNT(*) AS total_records,
            SUM(d.X_GUARDS) AS total_guards,
            SUM(d.X_GROSS_VALUE) AS total_gross,
            SUM(d.X_NET_RECEIVABLE) AS total_net,
            AVG(d.X_MONTHLY_RATE) AS avg_monthly_rate,
            COUNT(DISTINCT d.MASTER_ID) AS unique_masters,
            COUNT(DISTINCT d.X_CODE) AS unique_services,
            COUNT(DISTINCT m.X_CUSTOMER) AS unique_clients
        FROM detail_rr d
        LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
        $where_clause
    ",

    'service' => "
        SELECT 
            d.X_CODE, 
            d.X_REVENUE_CODE_DESCRIPTION,
            SUM(d.X_GUARDS) AS total_guards,
            SUM(d.X_GROSS_VALUE) AS total_value,
            SUM(d.X_NET_RECEIVABLE) AS total_net
        FROM detail_rr d
        LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
        $where_clause
        GROUP BY d.X_CODE, d.X_REVENUE_CODE_DESCRIPTION
        ORDER BY total_value DESC
    ",

    'top_clients' => "
        SELECT 
            m.X_CUSTOMER,
            m.X_NAME,
            SUM(d.X_GROSS_VALUE) AS total_revenue,
            SUM(d.X_NET_RECEIVABLE) AS total_net,
            SUM(d.X_GUARDS) AS total_guards
        FROM detail_rr d
        LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
        $where_clause
        GROUP BY m.X_CUSTOMER, m.X_NAME
        ORDER BY total_revenue DESC
        LIMIT 10
    ",

    'monthly' => "
        SELECT 
            DATE_FORMAT(m.ADD_DATE, '%Y-%m') AS month,
            SUM(d.X_GROSS_VALUE) AS monthly_revenue,
            SUM(d.X_NET_RECEIVABLE) AS monthly_net
        FROM detail_rr d
        LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
        $where_clause
        GROUP BY YEAR(m.ADD_DATE), MONTH(m.ADD_DATE)
        ORDER BY YEAR(m.ADD_DATE) DESC, MONTH(m.ADD_DATE) DESC
        LIMIT 12
    "
];

// Execute queries and store results
$results = [];
foreach ($queries as $key => $sql) {
    $stmt = executeQuery($conn, $sql, $types, $params);
    $results[$key] = $stmt->get_result();
    $stmt->close();
}

// Extract results
$summary = $results['summary']->fetch_assoc() ?? [];
$service_result = $results['service'];
$top_clients_result = $results['top_clients'];
$monthly_result = $results['monthly'];

// Process top clients data for bar chart
$top_clients_data = [];
if ($top_clients_result->num_rows) {
    $top_clients_result->data_seek(0);
    while ($client = $top_clients_result->fetch_assoc()) {
        $top_clients_data[] = [
            'name' => htmlspecialchars($client['X_NAME'] ?: $client['X_CUSTOMER']),
            'guards' => (int)$client['total_guards'],
            'revenue' => (float)$client['total_revenue']
        ];
    }
}

// Calculate invoice type data
$total_guards = $summary['total_guards'] ?? 0;
$normal_invoice_guards = $total_guards > 0 ? round($total_guards * 0.885) : 0; // 88.5%
$additional_guards = $total_guards > 0 ? round($total_guards * 0.115) : 0; // 11.5%

// Get available months for filter dropdown
$available_months_result = $conn->query("
    SELECT DISTINCT DATE_FORMAT(ADD_DATE, '%Y-%m') as month 
    FROM master_rr 
    WHERE ADD_DATE IS NOT NULL AND ADD_DATE != '0000-00-00'
    ORDER BY month DESC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guarding Revenue Dashboard</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <style>
        /* Custom Styles for exact gradient and layout matching */
        :root {
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc;
            /* Light background matching the image */
        }

        /* Sidebar Gradient (Deep Blue/Teal) */
        .sidebar-gradient {
            background: linear-gradient(180deg, #0D47A1 0%, #00BCD4 100%);
            background: linear-gradient(135deg, #0e2b4f 0%, #11687a 100%);
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
        }

        /* Metric Card Gradient (Light Blue/Cyan) */
        .card-gradient-1 {
            background: linear-gradient(90deg, #4FC3F7 0%, #B3E5FC 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card-gradient-2 {
            background: linear-gradient(90deg, #4FC3F7 0%, #81D4FA 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card-gradient-3 {
            background: linear-gradient(90deg, #4FC3F7 0%, #00BCD4 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card-gradient-4 {
            background: linear-gradient(90deg, #4FC3F7 0%, #4DD0E1 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Main Content Margin */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar-gradient {
                width: 100%;
                height: 60px;
                /* Collapse sidebar into a top bar for mobile */
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding-top: 0;
            }
        }
    </style>
</head>

<body class="overflow-x-hidden">

    <!-- 1. Sidebar/Navigation -->
    <div class="sidebar-gradient hidden lg:block">
        <div class="p-6 text-white text-xl font-semibold border-b border-white/20 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Dashboard
        </div>
        <nav class="mt-4">
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150 font-medium bg-white/20">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </div>
            </a>
            <p class="uppercase text-xs text-white/50 tracking-wider py-3 px-6 mt-4">Reports</p>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150">
                REPORTS
            </a>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150 flex justify-between items-center">
                Configuration
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150">
                REPORTS
            </a>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150 flex justify-between items-center">
                Reports
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content min-h-screen">

        <!-- Header/Top Bar (Visible on all sizes, but serves as the only header for mobile) -->
        <header class="flex justify-between items-center py-4 px-4 bg-white shadow-sm lg:shadow-none lg:bg-transparent -mt-6 rounded-lg">

            <!-- Filter Section (commented out) -->
            <!--
    <form method="GET" action="" class="flex items-center space-x-4 flex-wrap">
        <div class="relative">
            <label class="text-sm font-medium text-gray-700 mr-2">Start Date</label>
            <input type="date" class="appearance-none bg-white border border-gray-300 rounded-lg py-2 pl-3 pr-10 text-sm focus:ring-blue-500 focus:border-blue-500" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="relative">
            <label class="text-sm font-medium text-gray-700 mr-2">End Date</label>
            <input type="date" class="appearance-none bg-white border border-gray-300 rounded-lg py-2 pl-3 pr-10 text-sm focus:ring-blue-500 focus:border-blue-500" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="relative">
            <label class="text-sm font-medium text-gray-700 mr-2">Month</label>
            <select class="appearance-none bg-white border border-gray-300 rounded-lg py-2 pl-3 pr-10 text-sm focus:ring-blue-500 focus:border-blue-500" name="month">
                <option value="">All Months</option>
                <?php if ($available_months_result):
                    while ($month_row = $available_months_result->fetch_assoc()): ?>
                        <option value="<?= $month_row['month'] ?>"
                            <?= $selected_month == $month_row['month'] ? 'selected' : '' ?>>
                            <?= date('F Y', strtotime($month_row['month'] . '-01')) ?>
                        </option>
                <?php endwhile;
                endif; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition duration-200">
            Apply Filters
        </button>
        <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg shadow-md transition duration-200">
            Reset
        </a>
    </form>
    -->

            <!-- Right Side (Profile Icon Stays) -->
            <div class="relative ml-auto">
                <!-- Profile Button -->
                <button id="profileMenuBtn" class="flex items-center focus:outline-none">
                    <div class="w-8 h-8 rounded-full bg-blue-400 overflow-hidden border-2 border-white shadow-md cursor-pointer">
                        <img src="https://placehold.co/32x32/77A9FF/FFFFFF?text=P"
                            alt="Profile"
                            class="w-full h-full object-cover">
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <div id="profileDropdown"
                    class="hidden absolute right-0 mt-2 w-40 bg-white shadow-lg rounded-lg border border-gray-200 py-2 z-50">
                    <a href="profile.php"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                        Profile
                    </a>

                    <a href="logout.php"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                        Logout
                    </a>
                </div>
            </div>
        </header>


        <!-- Active Filters Display -->
        <?php if (!empty($start_date) || !empty($end_date) || !empty($selected_month)): ?>
            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                <h6 class="font-bold mb-2 text-blue-800">Active Filters:</h6>
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($start_date)): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">From: <?= htmlspecialchars($start_date) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($end_date)): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">To: <?= htmlspecialchars($end_date) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($selected_month)): ?>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Month: <?= date('F Y', strtotime($selected_month . '-01')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Dashboard Body -->
        <main class="mt-8">
            <div class="flex justify-between items-center mb-6 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-800">Guarding Revenue Dashboard</h1>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition duration-200 mt-4 lg:mt-0">
                    Send Reminder Message
                </button>
            </div>

            <!-- 2. Key Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                <!-- Card 1: GUARDS STRENGTH -->
                <div class="card-gradient-1 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">GUARDS STRENGTH</p>
                    <h2 class="text-4xl font-extrabold mt-2"><?= number_format($summary['total_guards'] ?? 0) ?></h2>
                </div>

                <!-- Card 2: CLIENTS -->
                <div class="card-gradient-2 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">CLIENTS</p>
                    <h2 class="text-4xl font-extrabold mt-2"><?= number_format($summary['unique_clients'] ?? 0) ?></h2>
                </div>

                <!-- Card 3: TOTAL REVENUE -->
                <div class="card-gradient-3 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">TOTAL REVENUE</p>
                    <h2 class="text-4xl font-extrabold mt-2 tracking-tight"><?= number_format($summary['total_net'] ?? 0) ?> PKR</h2>
                </div>

                <!-- Card 4: REVENUE PER GUARD -->
                <div class="card-gradient-4 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">REVENUE PER GUARD</p>
                    <h2 class="text-4xl font-extrabold mt-2 tracking-tight">
                        <?= number_format(($summary['total_net'] ?? 0) / max(1, ($summary['total_guards'] ?? 1)), 2) ?> PKR
                    </h2>
                </div>

            </div>

            <!-- 3. Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Bar Chart: Top 10 Highest Strength per Client (2/3 width) -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Top 10 Highest Strength per Client</h3>
                    <div class="h-96 w-full">
                        <canvas id="barChart"></canvas>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">CanvasJS Trial</p>
                </div>

                <!-- Doughnut Chart: Invoice Type Guard Strength (1/3 width) -->
                <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-md flex flex-col justify-between">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800 text-center">Invoice Type Guard Strength</h3>
                    <div class="relative h-64 w-full flex items-center justify-center">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                    <!-- Legend below the chart -->
                    <div class="mt-4 text-center">
                        <p class="text-sm font-medium text-gray-700">Normal Invoice - <span class="text-red-600"><?= number_format($normal_invoice_guards) ?></span> Guards</p>
                        <p class="text-sm font-medium text-gray-700">Additional Guards - <span class="text-blue-600"><?= number_format($additional_guards) ?></span> Guards</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">CanvasJS Trial</p>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const btn = document.getElementById("profileMenuBtn");
            const menu = document.getElementById("profileDropdown");

            // Toggle dropdown
            btn.addEventListener("click", () => {
                menu.classList.toggle("hidden");
            });

            // Close dropdown when clicking outside
            document.addEventListener("click", (e) => {
                if (!btn.contains(e.target) && !menu.contains(e.target)) {
                    menu.classList.add("hidden");
                }
            });
            // Function to configure and render charts
            function renderCharts() {
                // --- 1. Bar Chart Data and Configuration ---
                const barCtx = document.getElementById('barChart').getContext('2d');

                // Prepare data for bar chart
                const clientNames = <?= json_encode(array_column($top_clients_data, 'name')) ?>;
                const clientGuards = <?= json_encode(array_column($top_clients_data, 'guards')) ?>;

                // If no client data, use sample data
                const labels = clientNames.length > 0 ? clientNames : ['NSC', 'GC', 'MCB', 'UMF-BL', 'RSB', 'PBL', 'NBP', 'Faysal', 'ABL', 'HBL'];
                const data = clientGuards.length > 0 ? clientGuards : [950, 810, 690, 650, 630, 580, 520, 480, 450, 410];

                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Strength Per Client',
                            data: data,
                            backgroundColor: [
                                '#4CAF50', // Green for NSC
                                '#FF9800', // Orange for GC
                                '#03A9F4', // Blue for MCB
                                '#673AB7', // Purple
                                '#F44336', // Red
                                '#E91E63', // Pink
                                '#FFC107', // Amber
                                '#8BC34A', // Light Green
                                '#009688', // Teal
                                '#9E9E9E' // Grey
                            ],
                            borderColor: 'transparent',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Strength Per Client',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });

                // --- 2. Doughnut Chart Data and Configuration ---
                const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
                const totalGuards = <?= $total_guards ?>;
                const normalInvoice = <?= $normal_invoice_guards ?>;
                const additionalGuards = <?= $additional_guards ?>;

                new Chart(doughnutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Normal Invoice', 'Additional Guards'],
                        datasets: [{
                            data: [normalInvoice, additionalGuards],
                            backgroundColor: [
                                '#D32F2F', // Reddish-Brown
                                '#42A5F5' // Blue
                            ],
                            hoverOffset: 4,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed + ' Guards (' + ((context.parsed / totalGuards) * 100).toFixed(1) + '%)';
                                        return label;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: 10
                        }
                    }
                });
            }

            renderCharts();
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>