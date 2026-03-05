<?php
require_once 'db.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetJobline($pdo);
        break;
    case 'POST':
        handlePostJobline($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}


function handleGetJobline($pdo) {
    $sql = "SELECT * FROM ATHCOUNTINGJOBLINE";
    $params = [];

    if (!empty($_GET)) {
        $conditions = [];
        foreach ($_GET as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

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


function handlePostJobline($pdo) {
    // Read the incoming JSON array list
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid JSON payload. Expected an array of objects."]);
        return;
    }

    // Define the column list based on your requirements
    $columns = [
        "ATHCOUNTINGJOBID", 
        "SALESUNIT", 
        "ORDERED", 
        "REMAIN", 
        "QTYAX", 
        "QTYDIFFERENCE", 
        "MODIFIEDDATETIME", 
        "MODIFIEDBY", 
        "MODIFIEDTRANSACTIONID", 
        "CREATEDBY", 
        "CREATEDTRANSACTIONID", 
        "DATAAREAID", 
        "RECVERSION", 
        "RECID"
    ];

    try {
        $pdo->beginTransaction();

        // 1. Clear the table before the new import
        $pdo->exec("TRUNCATE TABLE ATHCOUNTINGJOBLINE");

        // 2. Prepare the Insert statement (14 placeholders for your 14 columns)
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $columnNames = implode(',', $columns);
        
        $sql = "INSERT INTO ATHCOUNTINGJOBLINE ($columnNames) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $successCount = 0;
        foreach ($payload as $index => $row) {
            $values = [];
            foreach ($columns as $field) {
                // If a field is missing in a specific row, we default to an empty string
                // to satisfy the "all columns are strings" requirement.
                $values[] = isset($row[$field]) ? (string)$row[$field] : "";
            }
            
            $stmt->execute($values);
            $successCount++;
        }

        $pdo->commit();
        echo json_encode([
            "success" => true, 
            "message" => "Data synced successfully",
            "inserted" => $successCount
        ]);

    } catch (Exception $e) {
        // Rollback ensures that if one row fails, the TRUNCATE is also effectively undone 
        // (on InnoDB tables) or at least no partial data remains.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}