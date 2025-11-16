<?php
// DEBUG script — run only locally, remove after debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<h2>Debug info — do not use in production</h2>";

// DB connection info quick-check
if (!isset($conn)) {
    echo "<p style='color:red'><strong>\$conn is not set — check config.php</strong></p>";
    exit;
}

echo "<p><strong>MySQL host info:</strong> " . htmlspecialchars(mysqli_get_host_info($conn)) . "</p>";
$res = $conn->query("SELECT DATABASE() AS db");
$row = $res->fetch_assoc();
echo "<p><strong>Connected database:</strong> " . htmlspecialchars($row['db']) . "</p>";

// Query the admin row
$username = 'admin';
$stmt = $conn->prepare("SELECT id_pk, username, password, name, LENGTH(password) as plen, HEX(password) as phex FROM login WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "<p style='color:red'>No `admin` row returned. Maybe the table is empty or different DB.</p>";
    // dump first 5 rows for sanity
    $dump = $conn->query("SELECT id_pk,username,LEFT(password,40) as pw_sample FROM login LIMIT 5");
    if ($dump) {
        echo "<h3>First few rows in `login` table</h3><pre>";
        while ($r = $dump->fetch_assoc()) {
            echo htmlspecialchars(json_encode($r)) . "\n";
        }
        echo "</pre>";
    }
    exit;
}

$user = $result->fetch_assoc();

echo "<h3>Row fetched for username='admin'</h3>";
echo "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>";

echo "<p><strong>Stored password (raw):</strong> '" . htmlspecialchars($user['password']) . "'</p>";
echo "<p><strong>Stored password length (LENGTH):</strong> " . intval($user['plen']) . "</p>";
echo "<p><strong>Stored password HEX (HEX(password)):</strong> " . htmlspecialchars($user['phex']) . "</p>";

// If you POSTed a password, show its md5 and comparison
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = isset($_POST['password']) ? $_POST['password'] : '';
    echo "<h3>Submitted password analysis</h3>";
    echo "<p>Raw submitted: '" . htmlspecialchars($input) . "'</p>";
    echo "<p>md5(submitted): " . md5($input) . "</p>";
    echo "<p>md5(trim(submitted)): " . md5(trim($input)) . "</p>";
    echo "<p>md5(strtolower(trim(submitted))): " . md5(strtolower(trim($input))) . "</p>";
    // double md5
    echo "<p>md5(md5(submitted)): " . md5(md5($input)) . "</p>";

    // Compare
    $stored = $user['password'];
    echo "<p><strong>Comparison:</strong></p>";
    echo "<ul>";
    echo "<li>md5(submitted) === stored ? " . (md5($input) === $stored ? "<span style='color:green'>TRUE</span>" : "<span style='color:red'>FALSE</span>") . "</li>";
    echo "<li>md5(trim(submitted)) === stored ? " . (md5(trim($input)) === $stored ? "<span style='color:green'>TRUE</span>" : "<span style='color:red'>FALSE</span>") . "</li>";
    echo "<li>md5(md5(submitted)) === stored ? " . (md5(md5($input)) === $stored ? "<span style='color:green'>TRUE</span>" : "<span style='color:red'>FALSE</span>") . "</li>";
    echo "</ul>";
}

// small form to try passwords
?>
<form method="POST">
    <label>Try password for admin: <input name="password" type="text" value=""></label>
    <button type="submit">Test</button>
</form>
<?php
$stmt->close();
$conn->close();
?>
