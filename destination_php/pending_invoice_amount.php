<?php
require_once 'db.php';
require_once 'config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

validateApiKey();
$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Table and Column Configuration
$tableName = "ATHSALESORDERSUMMARY01B";
$allowedColumns = [
    'Description', 
    'PendingInvoiceAmount', 
    'SUMOFSALESAMOUNT', 
    'AVGOFPERCENTUNFULFILLED'
];

switch ($method) {
    case 'GET':
        handleGet($pdo, $tableName, $allowedColumns);
        break;
    case 'POST':
        handlePost($pdo, $tableName, $allowedColumns);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}

// ---------------- GET HANDLER ----------------
function handleGet($pdo, $tableName, $allowedColumns) {
    // We explicitly select only our allowed columns
    $sql = "SELECT " . implode(", ", $allowedColumns) . " FROM $tableName";
    $params = [];

    if (!empty($_GET)) {
        $conditions = [];
        foreach ($_GET as $key => $value) {
            // Only allow filtering on keys that exist in our column list
            if (in_array($key, $allowedColumns)) {
                $conditions[] = "$key = ?";
                $params[] = $value;
            }
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "count" => count($data),
            "data" => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}

// ---------------- POST HANDLER ----------------
function handlePost($pdo, $tableName, $allowedColumns) {
    $input = file_get_contents("php://input");
    $payload = json_decode($input, true);

    // Basic validation of payload structure
    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid JSON. Expected an array of objects."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Clear existing data in Summary B
        $pdo->exec("DELETE FROM $tableName");

        // 2. Build Dynamic Insert Query
        $colString = implode(", ", $allowedColumns);
        $placeholders = implode(", ", array_fill(0, count($allowedColumns), "?"));
        $sql = "INSERT INTO $tableName ($colString) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $inserted = 0;
        foreach ($payload as $index => $row) {
            $values = [];
            foreach ($allowedColumns as $col) {
                if (!isset($row[$col])) {
                    throw new Exception("Row $index is missing column: $col");
                }
                $values[] = $row[$col];
            }
            $stmt->execute($values);
            $inserted++;
        }

        $pdo->commit();
        echo json_encode(["success" => true, "inserted" => $inserted]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}