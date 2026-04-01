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

// Columns specific to the InventTable view
$columns = [
    "ITEMID",
    "ITEMNAME",
    "NAMEALIAS",
    "ATHITEMGROUPIDA",
    "ATHITEMGROUPIDB",
    "ATHITEMGROUPIDC",
    "ATHITEMGROUPIDD",
    "ATHITEMGROUPIDE",
    "ATHITEMGROUPIDG",
    "PRIMARYVENDORID",
    "VENDNAME",
    "LISTPRICE",
    "RETAILPRICE",
    "SuperMarketPRICE",
    "PurchPRICE",
    "TaxItemGroupId",
    "LOWESTQTY",
    "MULTIPLEQTY",
    "NETWEIGHT",
    "UNITVOLUME",
    "vatMult",
    "RRP",
    "ITEMBUYERGROUPID",
    "MarginPct"
];

switch ($method) {
    case 'GET':
        handleGetItems($pdo, $columns);
        break;
    case 'POST':
        // NOTE: Inserting into a View may require INSTEAD OF triggers on the DB side
        handlePostItems($pdo, $columns);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}

/**
 * GET: Fetches the TOP 10 items from the view.
 */
function handleGetItems($pdo, $columns)
{
    $columnNames = implode(',', $columns);

    // Using TOP 10 as requested
    $sql = "SELECT  $columnNames FROM zzz_vw_InventTableForExcel";

    $conditions = [];
    $params = [];

    // Allow filtering (e.g., ?ITEMID=1001)
    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            $upperKey = strtoupper($key);
            if (in_array($upperKey, $columns)) {
                $conditions[] = "$upperKey = ?";
                $params[] = $value;
            }
        }
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "count" => count($data),
            "data" => $data
        ], JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}

/**
 * POST: Sync items. 
 * Warning: Standard SQL Views are often read-only. 
 * This script assumes the DB is configured to handle inserts into this view.
 */
function handlePostItems($pdo, $columns)
{
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid JSON payload."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // BE CAREFUL: This deletes data from the source of the view!
        $pdo->exec("DELETE FROM zzz_vw_InventTableForExcel");

        $columnNames = implode(',', $columns);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO zzz_vw_InventTableForExcel ($columnNames) VALUES ($placeholders)";

        $stmt = $pdo->prepare($sql);
        $count = 0;

        foreach ($payload as $row) {
            $values = [];
            foreach ($columns as $field) {
                // Handle nulls and missing keys
                $values[] = array_key_exists($field, $row) ? $row[$field] : null;
            }
            $stmt->execute($values);
            $count++;
        }

        $pdo->commit();
        echo json_encode(["success" => true, "inserted" => $count]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Sync failed: " . $e->getMessage()]);
    }
}
