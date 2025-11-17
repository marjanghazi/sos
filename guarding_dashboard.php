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

    'recent' => "
        SELECT 
            d.DETAIL_ID, d.MASTER_ID, 
            d.X_CODE, d.X_REVENUE_CODE_DESCRIPTION,
            d.X_GUARDS, d.X_GROSS_VALUE, d.X_NET_RECEIVABLE, 
            d.ADD_DATE,
            m.X_CUSTOMER, m.X_NAME, m.LOCATION_NAME, m.X_REVENUE_AUTHORITY
        FROM detail_rr d
        LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
        $where_clause
        ORDER BY d.ADD_DATE DESC 
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
$recent_result = $results['recent'];
$monthly_result = $results['monthly'];

// Process monthly data
$monthly_data = $monthly_result->num_rows > 0 ? array_reverse($monthly_result->fetch_all(MYSQLI_ASSOC)) : [];
$months = $gross_values = $net_values = [];
foreach ($monthly_data as $row) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $gross_values[] = (float)$row['monthly_revenue'];
    $net_values[] = (float)$row['monthly_net'];
}

// Process pie chart data
$pie_chart_labels = $pie_chart_values = $pie_chart_colors = [];
$color_palette = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8AC926', '#1982C4', '#6A4C93', '#FF595E'];

if ($service_result->num_rows) {
    $service_result->data_seek(0);
    $counter = 0;
    while ($service = $service_result->fetch_assoc()) {
        $pie_chart_labels[] = htmlspecialchars($service['X_REVENUE_CODE_DESCRIPTION']);
        $pie_chart_values[] = (float)$service['total_value'];
        $pie_chart_colors[] = $color_palette[$counter++ % count($color_palette)];
    }
    $service_result->data_seek(0);
}

// Get available months for filter dropdown (cached if possible)
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
    <title>Security Guarding Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f8f9fa;
        }

        .card {
            border-radius: 15px;
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .table thead {
            background: #0d6efd;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background: rgba(13, 110, 253, 0.05);
        }

        .dashboard-header {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .service-card {
            border-left: 4px solid #0d6efd;
        }

        .service-card .card-body {
            padding: 1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .active-filters {
            background: #e7f3ff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="dashboard-header text-center">
            <h1 class="fw-bold mb-2"><i class="fas fa-shield-alt me-2"></i>Security Guarding Dashboard</h1>
            <p class="text-light mb-0">Comprehensive Overview of Guarding Services and Revenue</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-filter me-2 text-primary"></i>Filter Data</h5>
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Month</label>
                    <select class="form-select" name="month">
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
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2">
                        <i class="fas fa-search me-1"></i> Apply Filters
                    </button>
                    <a href="?" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
                </div>
            </form>

            <?php if (!empty($start_date) || !empty($end_date) || !empty($selected_month)): ?>
                <div class="active-filters mt-3">
                    <h6 class="fw-bold mb-2">Active Filters:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($start_date)): ?>
                            <span class="badge bg-primary">From: <?= htmlspecialchars($start_date) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($end_date)): ?>
                            <span class="badge bg-primary">To: <?= htmlspecialchars($end_date) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($selected_month)): ?>
                            <span class="badge bg-success">Month: <?= date('F Y', strtotime($selected_month . '-01')) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                ['color' => 'primary', 'icon' => 'fa-wallet', 'value' => number_format($summary['total_net'] ?? 0, 2), 'label' => 'Total Net Receivable'],
                ['color' => 'success', 'icon' => 'fa-user-shield', 'value' => number_format($summary['total_guards'] ?? 0), 'label' => 'Total Guards'],
                ['color' => 'info', 'icon' => 'fa-chart-bar', 'value' => number_format($summary['total_gross'] ?? 0, 2), 'label' => 'Total Gross Revenue'],
                ['color' => 'danger', 'icon' => 'fa-users', 'value' => number_format($summary['unique_clients'] ?? 0), 'label' => 'Unique Clients']
            ];
            foreach ($cards as $c): ?>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="text-<?= $c['color'] ?> mb-2"><i class="fas <?= $c['icon'] ?> fa-2x"></i></div>
                            <h4 class="fw-bold mb-1"><?= $c['value'] ?></h4>
                            <small class="text-muted text-uppercase"><?= $c['label'] ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Service Breakdown Cards -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-cubes me-2 text-warning"></i>Service Breakdown</span>
                        <span class="badge bg-primary"><?= $service_result->num_rows ?> Services</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if ($service_result->num_rows):
                                $service_result->data_seek(0);
                                while ($srv = $service_result->fetch_assoc()): ?>
                                    <div class="col-md-4 col-lg-3">
                                        <div class="card service-card shadow-sm h-100">
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($srv['X_REVENUE_CODE_DESCRIPTION']) ?></h6>
                                                <p class="text-muted small mb-2">Code: <?= htmlspecialchars($srv['X_CODE']) ?></p>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-muted small">Guards:</span>
                                                    <span class="fw-bold text-primary"><?= (int)$srv['total_guards'] ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-muted small">Gross Value:</span>
                                                    <span class="fw-bold text-success"><?= number_format($srv['total_value'], 2) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small">Net Receivable:</span>
                                                    <span class="fw-bold text-info"><?= number_format($srv['total_net'], 2) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <div class="col-12 text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No service data available for selected filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables -->
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Pie Chart -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>Service Revenue Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="servicePieChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-history me-2 text-secondary"></i>Recent Transactions</div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Guards</th>
                                    <th class="text-end">Net</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_result->num_rows):
                                    while ($r = $recent_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($r['X_NAME']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['LOCATION_NAME']) ?></small></td>
                                            <td><?= htmlspecialchars($r['X_REVENUE_CODE_DESCRIPTION']) ?><br><small class="text-muted"><?= htmlspecialchars($r['X_CODE']) ?></small></td>
                                            <td><?= (int)$r['X_GUARDS'] ?></td>
                                            <td class="text-end fw-semibold text-success"><?= number_format($r['X_NET_RECEIVABLE'], 2) ?></td>
                                            <td><?= date('d M Y', strtotime($r['ADD_DATE'])) ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent data available for selected filters</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-chart-line me-2 text-success"></i>Quick Stats</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="fs-3 fw-bold text-success"><?= number_format($summary['unique_services'] ?? 0) ?></div>
                                <div class="small text-muted">Service Types</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="fs-3 fw-bold text-primary"><?= number_format($summary['avg_monthly_rate'] ?? 0, 2) ?></div>
                                <div class="small text-muted">Avg Monthly Rate</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-3 fw-bold text-info"><?= number_format($summary['unique_masters'] ?? 0) ?></div>
                                <div class="small text-muted">Master Records</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-3 fw-bold text-warning"><?= number_format($summary['total_records'] ?? 0) ?></div>
                                <div class="small text-muted">Total Records</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Summary -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-list me-2 text-info"></i>Service Summary</div>
                    <div class="list-group list-group-flush">
                        <?php if ($service_result->num_rows):
                            $service_result->data_seek(0);
                            while ($srv = $service_result->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($srv['X_REVENUE_CODE_DESCRIPTION']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($srv['X_CODE']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success small"><?= number_format($srv['total_value'], 2) ?></div>
                                        <small class="text-muted"><?= (int)$srv['total_guards'] ?> guards</small>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="p-3 text-muted text-center">No service data for selected filters</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (!empty($pie_chart_values)): ?>
            new Chart(document.getElementById('servicePieChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($pie_chart_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($pie_chart_values) ?>,
                        backgroundColor: <?= json_encode($pie_chart_colors) ?>,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>
<?php $conn->close(); ?>