<?php
require_once 'config.php';

// --- SUMMARY STATS ---
$summary_sql = "
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
";
$summary = $conn->query($summary_sql)->fetch_assoc() ?? [];

// --- SERVICE TYPE BREAKDOWN ---
$service_sql = "
    SELECT 
        d.X_CODE, 
        d.X_REVENUE_CODE_DESCRIPTION,
        SUM(d.X_GUARDS) AS total_guards,
        SUM(d.X_GROSS_VALUE) AS total_value,
        SUM(d.X_NET_RECEIVABLE) AS total_net
    FROM detail_rr d
    GROUP BY d.X_CODE, d.X_REVENUE_CODE_DESCRIPTION
    ORDER BY total_value DESC
";
$service_result = $conn->query($service_sql);

// --- RECENT TRANSACTIONS ---
$recent_sql = "
    SELECT 
        d.DETAIL_ID, d.MASTER_ID, 
        d.X_CODE, d.X_REVENUE_CODE_DESCRIPTION,
        d.X_GUARDS, d.X_GROSS_VALUE, d.X_NET_RECEIVABLE, 
        d.X_DATE,
        m.X_CUSTOMER, m.X_NAME, m.LOCATION_NAME, m.X_REVENUE_AUTHORITY
    FROM detail_rr d
    LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
    ORDER BY d.X_DATE DESC 
    LIMIT 10
";
$recent_result = $conn->query($recent_sql);

// --- MONTHLY REVENUE TREND ---
$monthly_sql = "
    SELECT 
        DATE_FORMAT(m.X_DATE, '%Y-%m') AS month,
        SUM(d.X_GROSS_VALUE) AS monthly_revenue,
        SUM(d.X_NET_RECEIVABLE) AS monthly_net
    FROM detail_rr d
    LEFT JOIN master_rr m ON d.MASTER_ID = m.MASTER_ID
    WHERE m.X_DATE IS NOT NULL 
      AND m.X_DATE != '0000-00-00'
    GROUP BY YEAR(m.X_DATE), MONTH(m.X_DATE)
    ORDER BY YEAR(m.X_DATE) DESC, MONTH(m.X_DATE) DESC
    LIMIT 12
";
$monthly_result = $conn->query($monthly_sql);
$monthly_data = $monthly_result && $monthly_result->num_rows > 0
    ? array_reverse($monthly_result->fetch_all(MYSQLI_ASSOC))
    : [];

$months = $gross_values = $net_values = [];
foreach ($monthly_data as $row) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $gross_values[] = (float)$row['monthly_revenue'];
    $net_values[] = (float)$row['monthly_net'];
}
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
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="dashboard-header text-center">
            <h1 class="fw-bold mb-2"><i class="fas fa-shield-alt me-2"></i>Security Guarding Dashboard</h1>
            <p class="text-light mb-0">Comprehensive Overview of Guarding Services and Revenue</p>
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

        <!-- Charts and Tables -->
        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-lg-8">


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
                                <?php if ($recent_result && $recent_result->num_rows):
                                    while ($r = $recent_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($r['X_NAME']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($r['LOCATION_NAME']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($r['X_REVENUE_CODE_DESCRIPTION']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($r['X_CODE']) ?></small>
                                            </td>
                                            <td><?= (int)$r['X_GUARDS'] ?></td>
                                            <td class="text-end fw-semibold text-success"><?= number_format($r['X_NET_RECEIVABLE'], 2) ?></td>
                                            <td><?= date('d M Y', strtotime($r['X_DATE'])) ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-cubes me-2 text-warning"></i>Service Breakdown</div>
                    <div class="list-group list-group-flush">
                        <?php if ($service_result && $service_result->num_rows):
                            while ($srv = $service_result->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($srv['X_REVENUE_CODE_DESCRIPTION']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($srv['X_CODE']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?= number_format($srv['total_value'], 2) ?></div>
                                        <small class="text-muted"><?= (int)$srv['total_guards'] ?> guards</small>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="p-3 text-muted text-center">No service data</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Quick Stats</div>
                    <div class="card-body d-flex justify-content-around">
                        <div class="text-center">
                            <div class="fs-3 fw-bold text-success"><?= number_format($summary['unique_services'] ?? 0) ?></div>
                            <div class="small text-muted">Service Types</div>
                        </div>
                        <div class="text-center">
                            <div class="fs-3 fw-bold text-primary"><?= number_format($summary['avg_monthly_rate'] ?? 0, 2) ?></div>
                            <div class="small text-muted">Avg Monthly Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php $conn->close(); ?>