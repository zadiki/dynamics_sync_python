<?php
require_once 'db.php';
require_once 'config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

// 1. Security Check
validateApiKey();
$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 2. Define the exact columns from your Select statement
$columns = [
    "SALESID",
    "ITEMID",
    "ITEMNAME",
    "SALESQTY",
    "INVENTLOCATIONID",
    "INVENTSITEID",
    "SALESUNIT",
    "SALESPRICE",
    "FREEOFCHARGE",
    "LINEDISC",
    "LINEPERCENT",
    "LINEAMOUNT",
    "TAXITEMGROUP",
    "ATHITEMGROUPIDA",
    "ATHITEMGROUPIDB",
    "ATHITEMGROUPIDC",
    "ATHNONFULFILLMENTREASONID"
];

$tableName = "SALESTABLE_SALESDETAIL";

switch ($method) {
    case 'GET':
        handleGetSalesDetails($pdo, $columns, $tableName);
        break;
    case 'POST':
        handlePostSalesDetails($pdo, $columns, $tableName);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}

/**
 * GET: Fetches from the flattened sales detail table
 */
function handleGetSalesDetails($pdo, $columns, $tableName)
{
    $colString = implode(',', $columns);
    $sql = "SELECT $colString FROM $tableName";
    $params = [];

    // Optional: Dynamic filtering (e.g., ?SALESID=SO-101)
    if (!empty($_GET)) {
        $conditions = [];
        foreach ($_GET as $key => $value) {
            $upperKey = strtoupper($key);
            if (in_array($upperKey, $columns)) {
                $conditions[] = "$upperKey = ?";
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

/**
 * POST: Sync data into the flattened table
 */
function handlePostSalesDetails($pdo, $columns, $tableName)
{
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid payload. Expected array of objects."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Clear table for fresh sync
        $pdo->exec("DELETE FROM $tableName");

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $colString = implode(',', $columns);
        $sql = "INSERT INTO $tableName ($colString) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $inserted = 0;
        foreach ($payload as $row) {
            $values = [];
            foreach ($columns as $field) {
                // Cast to string to ensure compatibility with placeholders
                $values[] = isset($row[$field]) ? (string)$row[$field] : "";
            }
            $stmt->execute($values);
            $inserted++;
        }

        $pdo->commit();
        echo json_encode([
            "success" => true,
            "message" => "Sync successful",
            "count" => $inserted
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}
