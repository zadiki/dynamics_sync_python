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

// Defined columns from your SELECT statement
$columns = [
    "SALESID",
    "SALESNAME",
    "TRANSFERWHAREHOUSE",
    "ATHSALESTYPE",
    "NUMBERSEQUENCEGROUP",
    "SALESSTATUS",
    "SALESTYPE",
    "ATHDNSTATUS",
    "INVOICEACCOUNT",
    "CURRENCYCODE",
    "PURCHORDERFORMNUM",
    "CUSTOMERREF",
    "CUSTACCOUNT",
    "TAXGROUP",
    "SHIPPINGDATEREQUESTED",
    "SHIPPINGDATECONFIRMED",
    "CREATEDDATETIME"
];

switch ($method) {
    case 'GET':
        handleGetSales($pdo, $columns);
        break;
    case 'POST':
        handlePostSales($pdo, $columns);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
        break;
}

/**
 * GET: Fetches sales data. 
 * Defaults to CREATEDDATETIME > '2020-01-01' unless overridden by parameters.
 */
function handleGetSales($pdo, $columns)
{
    $columnNames = implode(',', $columns);
    $sql = "SELECT $columnNames FROM SALESTABLE";

    // Default base condition as per your request
    $conditions = ["CREATEDDATETIME > ?"];
    $params = ["2020-01-01"];

    // Allow additional filtering via URL (e.g., ?SALESID=SO123)
    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            $upperKey = strtoupper($key);
            if (in_array($upperKey, $columns) && $upperKey !== "CREATEDDATETIME") {
                $conditions[] = "$upperKey = ?";
                $params[] = $value;
            }
        }
    }

    $sql .= " WHERE " . implode(" AND ", $conditions);

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
 * POST: Bulk sync (Delete all and re-insert new sales data)
 */
function handlePostSales($pdo, $columns)
{
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid JSON payload."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Warning: This clears the entire table before sync
        $pdo->exec("DELETE FROM SALESTABLE");

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $columnNames = implode(',', $columns);
        $sql = "INSERT INTO SALESTABLE ($columnNames) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $count = 0;
        foreach ($payload as $row) {
            $values = [];
            foreach ($columns as $field) {
                // Map fields; use null if data is missing
                $values[] = isset($row[$field]) ? (string)$row[$field] : null;
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
