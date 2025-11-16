<?php
require_once 'config.php';

// First, get all distinct aging_2 categories from the database
$categories_sql = "SELECT DISTINCT aging_2 FROM recordrecovery WHERE balance > 0 ORDER BY 
                   CASE 
                     WHEN aging_2 = 'Not Due' THEN 0
                     WHEN aging_2 = '0-30 Days' THEN 1
                     WHEN aging_2 = '31-60 Days' THEN 2
                     WHEN aging_2 = '61-90 Days' THEN 3
                     WHEN aging_2 = '91-120 Days' THEN 4
                     WHEN aging_2 = '121-180 Days' THEN 5
                     WHEN aging_2 = '181+ Days' THEN 6
                     ELSE 7
                   END";
$categories_result = $conn->query($categories_sql);

$aging_columns = [];
$case_statements = "";
$total_case_statements = "";

if ($categories_result && $categories_result->num_rows > 0) {
    while ($category_row = $categories_result->fetch_assoc()) {
        $category = $category_row['aging_2'];
        $column_name = strtolower(str_replace([' ', '+', '-'], ['_', 'plus', '_'], $category));
        $aging_columns[$category] = $column_name;

        $case_statements .= "SUM(CASE WHEN aging_2 = '$category' THEN balance ELSE 0 END) AS $column_name, ";
        $total_case_statements .= "SUM(CASE WHEN aging_2 = '$category' THEN balance ELSE 0 END) as $column_name, ";
    }
}

// Build the dynamic SQL query
$sql = "
    SELECT 
        customer,
        " . rtrim($case_statements, ', ') . ",
        SUM(balance) AS grand_total
    FROM recordrecovery
    WHERE balance > 0
    GROUP BY customer
    ORDER BY grand_total DESC
";

$result = $conn->query($sql);

// Get totals for footer
$totals_sql = "
    SELECT 
        " . rtrim($total_case_statements, ', ') . ",
        SUM(balance) as grand_total
    FROM recordrecovery
    WHERE balance > 0
";
$totals_result = $conn->query($totals_sql);
$totals_row = $totals_result ? $totals_result->fetch_assoc() : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aging Report Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
        }

        .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #2563eb;
            color: white;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">General Client Summary (Guarding)</h2>

                <div class="table-container border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="sticky-header bg-blue-600 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Client</th>
                                <?php foreach ($aging_columns as $display_name => $column_name): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase"><?= htmlspecialchars($display_name) ?></th>
                                <?php endforeach; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Grand Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($row['customer']) ?></td>
                                        <?php foreach ($aging_columns as $display_name => $column_name): ?>
                                            <td class="px-6 py-4 text-gray-500"><?= number_format($row[$column_name], 0) ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-6 py-4 font-semibold text-gray-900"><?= number_format($row['grand_total'], 0) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= count($aging_columns) + 2 ?>" class="px-6 py-4 text-center text-gray-500">No records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($totals_row)): ?>
                            <tfoot class="bg-gray-50 font-semibold">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Total</td>
                                    <?php foreach ($aging_columns as $display_name => $column_name): ?>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= number_format($totals_row[$column_name] ?? 0, 0) ?></td>
                                    <?php endforeach; ?>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= number_format($totals_row['grand_total'] ?? 0, 0) ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<?php
$conn->close();
?>