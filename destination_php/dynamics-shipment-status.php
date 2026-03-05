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
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}

// ---------------- GET HANDLER ----------------
function handleGet($pdo) {
    $sql = "SELECT * FROM SevkiyatPlanlama_App_01";
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

// ---------------- POST HANDLER ----------------
function handlePost($pdo) {
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid JSON payload"]);
        return;
    }

    $required = ["SALESID", "REMAININGQTY", "SALESNAME", "REMAINSALESPHYSICAL", "SHIPPINGDATEREQUESTED", "SHIPPINGDATECONFIRMED", "DELIVERYCITY", "KUTU_SAYISI"];

    try {
        $pdo->beginTransaction();

        // 1. Clear existing data
        $pdo->exec("TRUNCATE TABLE SevkiyatPlanlama_App_01");

        // 2. Prepare Insert
        $sql = "INSERT INTO SevkiyatPlanlama_App_01 
                (SALESID, REMAININGQTY, SALESNAME, REMAINSALESPHYSICAL, SHIPPINGDATEREQUESTED, SHIPPINGDATECONFIRMED, DELIVERYCITY, KUTU_SAYISI) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        $successCount = 0;
        foreach ($payload as $row) {
            $values = [];
            foreach ($required as $field) {
                if (!isset($row[$field])) throw new Exception("Missing field: $field");
                $values[] = $row[$field];
            }
            
            $stmt->execute($values);
            $successCount++;
        }

        $pdo->commit();
        echo json_encode(["success" => true, "inserted" => $successCount]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}