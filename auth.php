<?php

session_start();


// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {

    header("Location: /onyx-gym/login.php");
    exit;

}

?>