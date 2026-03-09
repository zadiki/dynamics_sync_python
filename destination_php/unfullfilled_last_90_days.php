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

// Table Configuration
$tableName = "ATHSALESORDERSUMMARY01G";

/**
 * We map the DB columns here. 
 * Note: For the GET request, we use the aliases you provided.
 */
$columnMapping = [
    'ITEMNAME'                => 'ITEMNAME',
    'MOSTRECENTINVOICEDATE'   => 'LatInvoiceDate', // Aliased as requested
    'LastOrderDate'           => 'LastOrderDate',
    'MOSTRECENTPRODDATE'      => 'MOSTRECENTPRODDATE',
    'UNFULFILLEDAMOUNT90DAYS' => 'UNFULFILLEDAMOUNT90DAYS',
    'DAYLASTSALES'            => 'DAYLASTSALES'
];

switch ($method) {
    case 'GET':
        handleGet($pdo, $tableName, $columnMapping);
        break;
    case 'POST':
        handlePost($pdo, $tableName, $columnMapping);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}

// ---------------- GET HANDLER ----------------
function handleGet($pdo, $tableName, $mapping) {
    // Build SELECT clause with aliases: "COLUMN as Alias"
    $selectParts = [];
    foreach ($mapping as $dbCol => $alias) {
        $selectParts[] = ($dbCol === $alias) ? $dbCol : "$dbCol AS '$alias'";
    }
    
    $sql = "SELECT " . implode(", ", $selectParts) . " FROM $tableName";
    $params = [];

    if (!empty($_GET)) {
        $conditions = [];
        foreach ($_GET as $key => $value) {
            // Check against aliases and actual column names
            foreach ($mapping as $dbCol => $alias) {
                if (strcasecmp($key, $dbCol) === 0 || strcasecmp($key, $alias) === 0) {
                    $conditions[] = "$dbCol = ?";
                    $params[] = $value;
                    break;
                }
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
function handlePost($pdo, $tableName, $mapping) {
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Array of objects required"]);
        return;
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM $tableName");

        $dbColumns = array_keys($mapping);
        $colString = implode(", ", $dbColumns);
        $placeholders = implode(", ", array_fill(0, count($dbColumns), "?"));
        
        $sql = "INSERT INTO $tableName ($colString) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        foreach ($payload as $row) {
            $values = [];
            foreach ($mapping as $dbCol => $alias) {
                // Try to find the data in the JSON using either the DB name or the Alias
                $val = null;
                if (isset($row[$alias])) $val = $row[$alias];
                elseif (isset($row[$dbCol])) $val = $row[$dbCol];
                
                $values[] = $val;
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