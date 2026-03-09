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

// Configuration for Table E
$tableName = "ATHSALESORDERSUMMARY01E";
$allowedColumns = [
    'Description', 
    'Unfullfillmentamount', 
    'FullfillmentPercent'
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
    $sql = "SELECT " . implode(", ", $allowedColumns) . " FROM $tableName";
    $params = [];

    if (!empty($_GET)) {
        $conditions = [];
        foreach ($_GET as $key => $value) {
            // Case-insensitive security check for columns
            $match = array_filter($allowedColumns, function($col) use ($key) {
                return strcasecmp($col, $key) === 0;
            });

            if (!empty($match)) {
                $actualCol = reset($match);
                $conditions[] = "$actualCol = ?";
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
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Payload must be a JSON array of objects."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Wipe the table for fresh data
        $pdo->exec("DELETE FROM $tableName");

        // 2. Prepare the insert
        $colString = implode(", ", $allowedColumns);
        $placeholders = implode(", ", array_fill(0, count($allowedColumns), "?"));
        $sql = "INSERT INTO $tableName ($colString) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $count = 0;
        foreach ($payload as $row) {
            $values = [];
            foreach ($allowedColumns as $col) {
                // Check for exact key, then case-insensitive key
                if (isset($row[$col])) {
                    $values[] = $row[$col];
                } else {
                    // Fallback for different casing in JSON keys
                    $found = false;
                    foreach ($row as $key => $val) {
                        if (strcasecmp($key, $col) === 0) {
                            $values[] = $val;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) $values[] = null;
                }
            }
            $stmt->execute($values);
            $count++;
        }

        $pdo->commit();
        echo json_encode(["success" => true, "inserted" => $count]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}