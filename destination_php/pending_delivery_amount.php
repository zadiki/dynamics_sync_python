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

// Configuration for Table C
$tableName = "ATHSALESORDERSUMMARY01C";
$allowedColumns = [
    'Description', 
    'PendingDeliveryAmount', 
    'SUMOFSALESAMOUNT', 
    'AVGOFPERCENT'
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
    // Select only the requested columns
    $sql = "SELECT " . implode(", ", $allowedColumns) . " FROM $tableName";
    $params = [];

    if (!empty($_GET)) {
        $conditions = [];
        foreach ($_GET as $key => $value) {
            // Check if the URL parameter is a valid column name
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
        echo json_encode(["success" => false, "error" => "Read Error: " . $e->getMessage()]);
    }
}

// ---------------- POST HANDLER ----------------
function handlePost($pdo, $tableName, $allowedColumns) {
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Expected a JSON array of objects."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Wipe old summary data
        $pdo->exec("DELETE FROM $tableName");

        // 2. Prepare the bulk insert
        $colString = implode(", ", $allowedColumns);
        $placeholders = implode(", ", array_fill(0, count($allowedColumns), "?"));
        $sql = "INSERT INTO $tableName ($colString) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $count = 0;
        foreach ($payload as $row) {
            $values = [];
            foreach ($allowedColumns as $col) {
                // If a field is missing in a row, we default to null
                $values[] = isset($row[$col]) ? $row[$col] : null;
            }
            $stmt->execute($values);
            $count++;
        }

        $pdo->commit();
        echo json_encode(["success" => true, "inserted" => $count]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Write Error: " . $e->getMessage()]);
    }
}