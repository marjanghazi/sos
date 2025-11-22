<?php
if (!isset($_SESSION['period'])) {
    $_SESSION['period'] = date("M Y");
}
if (isset($_GET['period'])) {
    $_SESSION['period'] = $_GET["period"];
    header("location:index.php");
    die();
}

include 'assets/include/dbconnect.php';

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
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>GUARDING DASHBOARD </title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js" integrity="sha256-+C0A5Ilqmu4QcSPxrlGpaZxJ04VjsRjKu+G82kl5UJk=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />

    <!-- Load Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">
    <style>
        .grad-nvb {
            background-image: linear-gradient(180deg, rgba(1, 47, 95, 1) -0.4%, rgba(56, 141, 217, 1) 106.1%);
            color: white;
        }

        /* Custom styles for your dashboard cards */
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
    </style>
    <script>
        function perchnage(argument) {
            window.open(window.location.href + "?period=" + argument, "_self");
        }
    </script>
</head>

<body id="page-top sidebar-toggled" style="background-color:#F6F6F9!important;">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar included here-->
        <?php
        include 'assets/include/sidebar.php';
        ?>
        <!-- End of Sidebar -->
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include 'assets/include/topbar.php'; ?>
                <!-- End of Topbar -->
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Guarding Revenue Dashboard</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            Send Reminder Message
                        </button>
                    </div>

                    <!-- Active Filters Display -->
                    <?php if (!empty($start_date) || !empty($end_date) || !empty($selected_month)): ?>
                        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
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

                    <!-- Content Row -->
                    <div class="row">

                        <!-- GUARDS STRENGTH Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 py-2 card-gradient-1">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1 text-white">
                                                GUARDS STRENGTH</div>
                                            <div class="h5 mb-0 font-weight-bold text-white">
                                                <?= number_format($summary['total_guards'] ?? 0) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CLIENTS Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 py-2 card-gradient-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1 text-white">
                                                CLIENTS</div>
                                            <div class="h5 mb-0 font-weight-bold text-white">
                                                <?= number_format($summary['unique_clients'] ?? 0) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-building fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOTAL REVENUE Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 py-2 card-gradient-3">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1 text-white">
                                                TOTAL REVENUE</div>
                                            <div class="h5 mb-0 font-weight-bold text-white">
                                                <?= number_format($summary['total_net'] ?? 0) ?> PKR
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- REVENUE PER GUARD Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 py-2 card-gradient-4">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1 text-white">
                                                REVENUE PER GUARD</div>
                                            <div class="h5 mb-0 font-weight-bold text-white">
                                                <?= number_format(($summary['total_net'] ?? 0) / max(1, ($summary['total_guards'] ?? 1)), 2) ?> PKR
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Charts Section -->
                    <div class="row">
                        <!-- Bar Chart: Top 10 Highest Strength per Client -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Highest Strength per Client</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar">
                                        <canvas id="barChart" style="height: 300px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Doughnut Chart: Invoice Type Guard Strength -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Invoice Type Guard Strength</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="doughnutChart" style="height: 300px;"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-danger"></i> Normal Invoice - <?= number_format($normal_invoice_guards) ?> Guards
                                        </span>
                                        <br>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Additional Guards - <?= number_format($additional_guards) ?> Guards
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php
    include 'assets/include/logoutmodal.php';
    ?>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>