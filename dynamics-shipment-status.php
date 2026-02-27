<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// ---- DB CONFIG ----
$host = "localhost";
$db   = "bekirycl_central_app";
$user = "bekirycl_system";
$pass = "MEqo)!FC4&JM";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    handleGet($conn);
} elseif ($method === 'POST') {
    handlePost($conn);
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Only GET and POST allowed"]);
}

$conn->close();
exit;

// ---------------- GET HANDLER ----------------
function handleGet($conn)
{
    $where = "";
    $params = [];
    $types = "";
    $values = [];

    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            $params[] = "$key = ?";
            $types .= "s";
            $values[] = $value;
        }
        $where = " WHERE " . implode(" AND ", $params);
    }

    $sql = "SELECT * FROM SevkiyatPlanlama_App_01" . $where;
    $stmt = $conn->prepare($sql);

    if (!empty($values)) {
        $stmt->bind_param($types, ...$values);
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $stmt->error]);
        return;
    }

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "success" => true,
        "count" => count($data),
        "data" => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $stmt->close();
}

// ---------------- POST HANDLER (TRUNCATE + BATCH INSERT) ----------------
function handlePost($conn)
{
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !is_array($payload)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Expected JSON array of objects"
        ]);
        return;
    }

    if (count($payload) === 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Empty payload"
        ]);
        return;
    }

    $required = [
        "SALESID",
        "REMAININGQTY",
        "SALESNAME",
        "REMAINSALESPHYSICAL",
        "SHIPPINGDATEREQUESTED",
        "SHIPPINGDATECONFIRMED",
        "DELIVERYCITY",
        "KUTU_SAYISI"
    ];

    $insertSQL = "INSERT INTO SevkiyatPlanlama_App_01
        (SALESID, REMAININGQTY, SALESNAME, REMAINSALESPHYSICAL,
         SHIPPINGDATEREQUESTED, SHIPPINGDATECONFIRMED, DELIVERYCITY,KUTU_SAYISI)
        VALUES (?, ?, ?, ?, ?, ?, ?,?)";

    $stmt = $conn->prepare($insertSQL);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $conn->error]);
        return;
    }

    $successCount = 0;
    $errors = [];

    // Disable autocommit for batch safety
    $conn->autocommit(false);

    // TRUNCATE FIRST (FAST FULL REFRESH)
    if (!$conn->query("TRUNCATE TABLE SevkiyatPlanlama_App_01")) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Failed to truncate table: " . $conn->error
        ]);
        return;
    }

    foreach ($payload as $index => $row) {

        foreach ($required as $field) {
            if (!isset($row[$field])) {
                $errors[] = [
                    "index" => $index,
                    "error" => "Missing field: $field"
                ];
                continue 2;
            }
        }

        $stmt->bind_param(
            "sdssssis",
            $row['SALESID'],
            $row['REMAININGQTY'],
            $row['SALESNAME'],
            $row['REMAINSALESPHYSICAL'],
            $row['SHIPPINGDATEREQUESTED'],
            $row['SHIPPINGDATECONFIRMED'],
            $row['DELIVERYCITY'],
            $row['KUTU_SAYISI']
        );

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = [
                "index" => $index,
                "error" => $stmt->error
            ];
        }
    }

    if ($successCount === count($payload)) {
        $conn->commit();
    } else {
        $conn->rollback();
    }

    $conn->autocommit(true);

    echo json_encode([
        "success" => $successCount === count($payload),
        "truncated" => true,
        "inserted" => $successCount,
        "total_received" => count($payload),
        "failed" => count($errors),
        "errors" => $errors
    ], JSON_PRETTY_PRINT);

    $stmt->close();
}