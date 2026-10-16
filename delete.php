<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


// Only allow POST requests

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");

    exit;
}


// Get member ID

$id = filter_input(
    INPUT_POST,
    "id",
    FILTER_VALIDATE_INT
);


if (!$id) {

    header(
        "Location: index.php?error=" .
        urlencode("Invalid member ID.")
    );

    exit;
}


// Delete member

try {

    $stmt = $pdo->prepare("
        DELETE FROM members
        WHERE id = ?
    ");

    $stmt->execute([$id]);


    if ($stmt->rowCount() > 0) {

        header(
            "Location: index.php?success=" .
            urlencode("Member deleted successfully.")
        );

    } else {

        header(
            "Location: index.php?error=" .
            urlencode("Member not found.")
        );

    }

    exit;

} catch (PDOException $e) {

    header(
        "Location: index.php?error=" .
        urlencode("Unable to delete member.")
    );

    exit;
}