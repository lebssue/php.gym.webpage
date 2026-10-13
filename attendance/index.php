<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$pageTitle = "Attendance";

$message = "";
$messageType = "";


/*
|--------------------------------------------------------------------------
| CHECK-IN FUNCTION
|--------------------------------------------------------------------------
*/

function checkInMember($pdo, $memberId)
{
    $memberId = trim($memberId);

    /*
    | Allow QR codes such as:
    | 25
    | ONYX-MEMBER-25
    */

    if (preg_match('/^ONYX-MEMBER-(\d+)$/i', $memberId, $matches)) {
        $memberId = $matches[1];
    }

    if (!ctype_digit($memberId) || (int)$memberId <= 0) {
        return [
            "success" => false,
            "message" => "Invalid member ID."
        ];
    }

    $memberId = (int)$memberId;


    /*
    |--------------------------------------------------------------------------
    | FIND MEMBER
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            membership_plan,
            expiration_date
        FROM members
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$memberId]);

    $member = $stmt->fetch();

    if (!$member) {
        return [
            "success" => false,
            "message" => "Member ID #{$memberId} was not found."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK MEMBERSHIP STATUS
    |--------------------------------------------------------------------------
    */

    if ($member["expiration_date"] < date("Y-m-d")) {

        return [
            "success" => false,
            "message" =>
                htmlspecialchars($member["full_name"]) .
                " has an expired membership."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE ATTENDANCE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM attendance
        WHERE member_id = ?
        AND attendance_date = CURDATE()
        LIMIT 1
    ");

    $stmt->execute([$memberId]);

    if ($stmt->fetch()) {

        return [
            "success" => false,
            "message" =>
                htmlspecialchars($member["full_name"]) .
                " is already checked in today."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RECORD ATTENDANCE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO attendance
        (
            member_id,
            attendance_date,
            attendance_time
        )
        VALUES
        (
            ?,
            CURDATE(),
            CURTIME()
        )
    ");

    $stmt->execute([$memberId]);


    return [
        "success" => true,
        "message" =>
            htmlspecialchars($member["full_name"]) .
            " successfully checked in!",
        "member" => $member
    ];
}


/*
|--------------------------------------------------------------------------
| QUICK CHECK-IN / QR SCANNER REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "checkin") {

        $memberId = $_POST["member_id"] ?? "";

        $result = checkInMember(
            $pdo,
            $memberId
        );


        /*
        | QR / JavaScript request
        */

        if (
            isset($_POST["ajax"]) &&
            $_POST["ajax"] === "1"
        ) {

            header("Content-Type: application/json");

            echo json_encode($result);

            exit;
        }


        /*
        | Normal form request
        */

        $message = $result["message"];

        $messageType =
            $result["success"]
                ? "success"
                : "error";
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH MEMBER
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");

$searchResults = [];

if ($search !== "") {

    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            membership_plan,
            expiration_date
        FROM members
        WHERE
            full_name LIKE ?
            OR id LIKE ?
            OR contact LIKE ?
        ORDER BY full_name ASC
        LIMIT 20
    ");

    $searchTerm = "%" . $search . "%";

    $stmt->execute([
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);

    $searchResults = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| ATTENDANCE HISTORY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        attendance.id,
        attendance.member_id,
        attendance.attendance_date,
        attendance.attendance_time,
        members.full_name,
        members.membership_plan
    FROM attendance
    INNER JOIN members
        ON attendance.member_id = members.id
    ORDER BY
        attendance.attendance_date DESC,
        attendance.attendance_time DESC,
        attendance.id DESC
    LIMIT 50
");

$attendanceHistory = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Attendance | ONYX GYM
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<?php require_once "../includes/sidebar.php"; ?>


<main class="main-content">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Attendance
            </h1>

            <p>
                Search members and record gym check-ins.
            </p>

        </div>

        <div class="page-header-brand">
            ONYX GYM
        </div>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div
            class="attendance-message <?= $messageType ?>"
        >
            <?= $message ?>
        </div>

    <?php endif; ?>


    <!-- SEARCH MEMBER -->

    <section class="dashboard-section attendance-search-section">

        <div class="section-header">

            <div>

                <h2>
                    Search Member
                </h2>

            </div>

            <p>
                Search by member ID, name, or contact number.
            </p>

        </div>


        <form
            method="GET"
            class="attendance-search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Enter member name, ID, or contact..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <button
                type="submit"
                class="attendance-search-button"
            >
                Search
            </button>

        </form>


        <?php if ($search !== ""): ?>

            <div class="attendance-search-results">

                <?php if (empty($searchResults)): ?>

                    <p class="attendance-empty">
                        No members found.
                    </p>

                <?php else: ?>

                    <?php foreach ($searchResults as $member): ?>

                        <?php
                        $isActive =
                            $member["expiration_date"] >= date("Y-m-d");
                        ?>

                        <div class="member-checkin-card">

                            <div>

                                <strong>
                                    <?= htmlspecialchars($member["full_name"]) ?>
                                </strong>

                                <span>
                                    ID #<?= htmlspecialchars($member["id"]) ?>
                                </span>

                                <span>
                                    <?= htmlspecialchars($member["membership_plan"]) ?>
                                </span>

                            </div>


                            <div class="member-checkin-right">

                                <span
                                    class="attendance-status
                                    <?= $isActive ? "active" : "expired" ?>"
                                >
                                    <?= $isActive ? "ACTIVE" : "EXPIRED" ?>
                                </span>


                                <?php if ($isActive): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="checkin"
                                        >

                                        <input
                                            type="hidden"
                                            name="member_id"
                                            value="<?= htmlspecialchars($member["id"]) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="checkin-button"
                                        >
                                            CHECK IN
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>


    <!-- QUICK CHECK-IN -->

    <section class="dashboard-section quick-checkin-section">

        <div class="section-header">

            <div>

                <h2>
                    Quick Check-In
                </h2>

                <p>
                    Enter a gym goer's ID number to record attendance.
                </p>

            </div>


            <!-- SCAN BUTTON -->

            <button
                type="button"
                id="openScanner"
                class="scan-id-button"
            >
                <span>▣</span>
                SCAN MEMBER ID
            </button>

        </div>


        <form
            id="quickCheckinForm"
            class="quick-checkin-form"
        >

            <input
                type="text"
                id="memberIdInput"
                name="member_id"
                placeholder="Enter Gym Goer ID"
                autocomplete="off"
                inputmode="numeric"
            >

            <button
                type="submit"
                class="checkin-button"
            >
                CHECK IN
            </button>

        </form>


        <p class="quick-checkin-note">
            You can type the member ID and press Enter,
            or scan the member's QR code.
        </p>


        <div
            id="quickCheckinMessage"
            class="scanner-message"
        ></div>

    </section>


    <!-- ATTENDANCE HISTORY -->

    <section class="dashboard-section">

        <div class="section-header">

            <div>

                <h2>
                    Attendance History
                </h2>

            </div>

            <p>
                Recent member check-ins.
            </p>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>MEMBER</th>

                        <th>MEMBERSHIP</th>

                        <th>DATE</th>

                        <th>TIME</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (empty($attendanceHistory)): ?>

                    <tr>

                        <td
                            colspan="5"
                            style="text-align:center;"
                        >
                            No attendance records yet.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($attendanceHistory as $record): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($record["id"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record["full_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record["membership_plan"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record["attendance_date"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record["attendance_time"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


</main>


<!-- =====================================================
     QR SCANNER MODAL
===================================================== -->

<div
    id="scannerModal"
    class="scanner-modal"
>

    <div class="scanner-box">

        <div class="scanner-header">

            <div>

                <h2>
                    Scan Member ID
                </h2>

                <p>
                    Point the camera at the member's QR code.
                </p>

            </div>


            <button
                type="button"
                id="closeScanner"
                class="scanner-close"
            >
                ×
            </button>

        </div>


        <div
            id="qr-reader"
            class="qr-reader"
        ></div>


        <div
            id="scannerResult"
            class="scanner-message"
        ></div>

    </div>

</div>


<!-- =====================================================
     HTML5 QR CODE LIBRARY
===================================================== -->

<script src="https://unpkg.com/html5-qrcode"></script>


<script>

/*
|--------------------------------------------------------------------------
| ELEMENTS
|--------------------------------------------------------------------------
*/

const quickCheckinForm =
    document.getElementById("quickCheckinForm");

const memberIdInput =
    document.getElementById("memberIdInput");

const quickCheckinMessage =
    document.getElementById("quickCheckinMessage");

const scannerModal =
    document.getElementById("scannerModal");

const openScanner =
    document.getElementById("openScanner");

const closeScanner =
    document.getElementById("closeScanner");

const scannerResult =
    document.getElementById("scannerResult");

let qrScanner = null;
let scannerRunning = false;


/*
|--------------------------------------------------------------------------
| CHECK IN USING AJAX
|--------------------------------------------------------------------------
*/

function checkInMember(memberId, messageElement) {

    memberId = memberId.trim();

    if (memberId === "") {

        messageElement.className =
            "scanner-message error";

        messageElement.textContent =
            "Please enter a member ID.";

        return;
    }


    const formData = new FormData();

    formData.append(
        "action",
        "checkin"
    );

    formData.append(
        "member_id",
        memberId
    );

    formData.append(
        "ajax",
        "1"
    );


    fetch("index.php", {

        method: "POST",

        body: formData

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            messageElement.className =
                "scanner-message success";

            messageElement.textContent =
                data.message;

            memberIdInput.value = "";

            /*
            | Refresh attendance table
            */

            setTimeout(() => {

                window.location.reload();

            }, 1000);

        } else {

            messageElement.className =
                "scanner-message error";

            messageElement.textContent =
                data.message;

        }

    })

    .catch(error => {

        console.error(error);

        messageElement.className =
            "scanner-message error";

        messageElement.textContent =
            "Unable to process check-in.";

    });
}


/*
|--------------------------------------------------------------------------
| MANUAL ID CHECK-IN
|--------------------------------------------------------------------------
*/

quickCheckinForm.addEventListener(
    "submit",
    function(event) {

        event.preventDefault();

        checkInMember(
            memberIdInput.value,
            quickCheckinMessage
        );

    }
);


/*
|--------------------------------------------------------------------------
| OPEN QR SCANNER
|--------------------------------------------------------------------------
*/

openScanner.addEventListener(
    "click",
    function() {

        scannerModal.classList.add("show");

        scannerResult.textContent = "";

        startScanner();

    }
);


/*
|--------------------------------------------------------------------------
| CLOSE QR SCANNER
|--------------------------------------------------------------------------
*/

closeScanner.addEventListener(
    "click",
    function() {

        stopScanner();

        scannerModal.classList.remove("show");

    }
);


/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE MODAL
|--------------------------------------------------------------------------
*/

scannerModal.addEventListener(
    "click",
    function(event) {

        if (event.target === scannerModal) {

            stopScanner();

            scannerModal.classList.remove("show");

        }

    }
);


/*
|--------------------------------------------------------------------------
| START SCANNER
|--------------------------------------------------------------------------
*/

function startScanner() {

    if (scannerRunning) {
        return;
    }

    qrScanner = new Html5Qrcode(
        "qr-reader"
    );


    qrScanner.start(

        {
            facingMode: "environment"
        },

        {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            }
        },


        function(decodedText) {

            console.log(
                "QR Code:",
                decodedText
            );


            /*
            | Stop scanner immediately
            | after successful scan.
            */

            stopScanner();


            /*
            | Automatically check member in.
            */

            checkInMember(
                decodedText,
                scannerResult
            );

        },


        function(errorMessage) {

            /*
            | Scanner continuously reports
            | when it doesn't see a QR code.
            |
            | We intentionally don't display
            | those messages.
            */

        }

    )

    .then(function() {

        scannerRunning = true;

    })

    .catch(function(error) {

        scannerResult.className =
            "scanner-message error";

        scannerResult.textContent =
            "Unable to access the camera. Please allow camera permission.";

        console.error(error);

    });

}


/*
|--------------------------------------------------------------------------
| STOP SCANNER
|--------------------------------------------------------------------------
*/

function stopScanner() {

    if (
        qrScanner &&
        scannerRunning
    ) {

        qrScanner.stop()

            .then(function() {

                qrScanner.clear();

                scannerRunning = false;

            })

            .catch(function(error) {

                console.error(error);

                scannerRunning = false;

            });

    }

}

</script>

</body>
</html>