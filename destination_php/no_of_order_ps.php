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

// Configuration for the Counts Table
$tableName = "ATHSALESORDERCOUNTS01";
$allowedColumns = [
    'DESCRIPTION', 
    'FROMDATE', 
    'TODATE', 
    'NOOFORDER', 
    'NOOFCPS', 
    'NOOFCIJ'
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
            // Case-insensitive check to see if the filter key is allowed
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
        echo json_encode(["success" => false, "error" => "Invalid JSON. Array of objects required."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Wipe old count data
        $pdo->exec("DELETE FROM $tableName");

        // 2. Prepare dynamic insert
        $colString = implode(", ", $allowedColumns);
        $placeholders = implode(", ", array_fill(0, count($allowedColumns), "?"));
        $sql = "INSERT INTO $tableName ($colString) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        foreach ($payload as $row) {
            $values = [];
            foreach ($allowedColumns as $col) {
                // Find the value even if the JSON key casing is different
                $value = null;
                foreach ($row as $key => $val) {
                    if (strcasecmp($key, $col) === 0) {
                        $value = $val;
                        break;
                    }
                }
                $values[] = $value;
            }
            $stmt->execute($values);
        }

        $pdo->commit();
        echo json_encode(["success" => true, "inserted" => count($payload)]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}