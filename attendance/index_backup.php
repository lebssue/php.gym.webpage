<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$pageTitle = "Attendance";

$search = trim($_GET["search"] ?? "");

$member = null;
$attendanceHistory = [];
$message = "";
$messageType = "";


// =====================================================
// SEARCH MEMBER
// =====================================================

if ($search !== "") {

    $stmt = $pdo->prepare("
        SELECT *
        FROM members
        WHERE id = ?
           OR full_name LIKE ?
           OR contact LIKE ?
        ORDER BY full_name ASC
        LIMIT 1
    ");

    $stmt->execute([
        is_numeric($search) ? $search : 0,
        "%" . $search . "%",
        "%" . $search . "%"
    ]);

    $member = $stmt->fetch();

}


// =====================================================
// CHECK IN
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $memberId = filter_input(
        INPUT_POST,
        "member_id",
        FILTER_VALIDATE_INT
    );


    if (!$memberId) {

        $message = "Invalid member.";
        $messageType = "error";

    } else {

        // ---------------------------------------------
        // GET MEMBER
        // ---------------------------------------------

        $stmt = $pdo->prepare("
            SELECT *
            FROM members
            WHERE id = ?
        ");

        $stmt->execute([$memberId]);

        $member = $stmt->fetch();


        if (!$member) {

            $message = "Member does not exist.";
            $messageType = "error";

        } else {

            $today = date("Y-m-d");


            // ---------------------------------------------
            // CHECK MEMBERSHIP STATUS
            // ---------------------------------------------

            if ($member["expiration_date"] < $today) {

                $message = "This member's membership has expired. Check-in is not allowed.";
                $messageType = "error";

            } else {

                // ---------------------------------------------
                // CHECK DUPLICATE ATTENDANCE
                // ---------------------------------------------

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM attendance
                    WHERE member_id = ?
                    AND attendance_date = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $memberId,
                    $today
                ]);

                $existingAttendance = $stmt->fetch();


                if ($existingAttendance) {

                    $message = "This member has already checked in today.";
                    $messageType = "error";

                } else {

                    // ---------------------------------------------
                    // RECORD ATTENDANCE
                    // ---------------------------------------------

                    $currentTime = date("H:i:s");

                    $stmt = $pdo->prepare("
                        INSERT INTO attendance
                        (
                            member_id,
                            attendance_date,
                            attendance_time
                        )
                        VALUES (?, ?, ?)
                    ");

                    $stmt->execute([
                        $memberId,
                        $today,
                        $currentTime
                    ]);


                    $message =
                        $member["full_name"] .
                        " successfully checked in.";

                    $messageType = "success";

                }

            }

        }

    }

}


// =====================================================
// LOAD ATTENDANCE HISTORY
// =====================================================

$stmt = $pdo->query("
    SELECT
        attendance.id,
        attendance.attendance_date,
        attendance.attendance_time,
        members.id AS member_id,
        members.full_name,
        members.membership_plan
    FROM attendance

    INNER JOIN members
        ON attendance.member_id = members.id

    ORDER BY
        attendance.attendance_date DESC,
        attendance.attendance_time DESC

    LIMIT 100
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

    <title>Attendance | ONYX GYM</title>

    <link
        rel="stylesheet"
        href="/onyx-gym/css/style.css"
    >

</head>


<body>


<?php require_once "../includes/sidebar.php"; ?>


<main class="main-content">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="top-header">

        <div>

            <h1>Attendance</h1>

            <p>
                Search members and record gym check-ins.
            </p>

        </div>

        <div class="header-brand">
            ONYX GYM
        </div>

    </div>


    <!-- =================================================
         MESSAGE
    ================================================== -->

    <?php if ($message !== ""): ?>

        <div class="<?= $messageType === "success"
            ? "success-message"
            : "error-message"
        ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         SEARCH MEMBER
    ================================================== -->

    <section class="dashboard-section">


        <div class="section-header">

            <h2>Search Member</h2>

            <p>
                Search by member ID, name, or contact number.
            </p>

        </div>


        <div class="attendance-search-area">


            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Enter member name, ID, or contact..."
                    value="<?= htmlspecialchars($search) ?>"
                    required
                >

                <button type="submit">
                    Search
                </button>

            </form>


        </div>


    </section>


    <!-- =================================================
         MEMBER RESULT
    ================================================== -->

    <?php if ($search !== ""): ?>


        <section class="dashboard-section">


            <div class="section-header">

                <h2>Member Result</h2>

            </div>


            <?php if ($member): ?>


                <?php

                $isActive =
                    $member["expiration_date"] >= date("Y-m-d");

                ?>


                <div class="attendance-member-card">


                    <div class="attendance-member-info">


                        <span class="member-label">
                            MEMBER
                        </span>

                        <h2>
                            <?= htmlspecialchars($member["full_name"]) ?>
                        </h2>


                        <div class="member-details">

                            <div>

                                <span>
                                    Member ID
                                </span>

                                <strong>
                                    #<?= htmlspecialchars($member["id"]) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Membership
                                </span>

                                <strong>
                                    <?= htmlspecialchars($member["membership_plan"]) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Expiration
                                </span>

                                <strong>
                                    <?= date(
                                        "M d, Y",
                                        strtotime($member["expiration_date"])
                                    ) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Status
                                </span>


                                <?php if ($isActive): ?>

                                    <strong class="attendance-active">
                                        ACTIVE
                                    </strong>

                                <?php else: ?>

                                    <strong class="attendance-expired">
                                        EXPIRED
                                    </strong>

                                <?php endif; ?>


                            </div>


                        </div>


                    </div>


                    <div class="attendance-checkin">


                        <?php if ($isActive): ?>


                            <form method="POST">

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


                        <?php else: ?>


                            <button
                                type="button"
                                class="checkin-button disabled"
                                disabled
                            >

                                EXPIRED

                            </button>


                        <?php endif; ?>


                    </div>


                </div>


            <?php else: ?>


                <div class="empty-table">

                    No member found matching:

                    <strong>
                        <?= htmlspecialchars($search) ?>
                    </strong>

                </div>


            <?php endif; ?>


        </section>


    <?php endif; ?>


    <!-- =================================================
         ATTENDANCE HISTORY
    ================================================== -->

    <section class="dashboard-section">


        <div class="section-header">

            <h2>Attendance History</h2>

            <p>
                Recent member check-ins.
            </p>

        </div>


        <div class="table-container">


            <table>


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Member</th>

                        <th>Membership</th>

                        <th>Date</th>

                        <th>Time</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($attendanceHistory) > 0): ?>


                    <?php foreach ($attendanceHistory as $record): ?>


                        <tr>


                            <td>
                                #<?= htmlspecialchars($record["id"]) ?>
                            </td>


                            <td>

                                <strong>
                                    <?= htmlspecialchars($record["full_name"]) ?>
                                </strong>

                                <small>
                                    Member #<?= htmlspecialchars($record["member_id"]) ?>
                                </small>

                            </td>


                            <td>
                                <?= htmlspecialchars($record["membership_plan"]) ?>
                            </td>


                            <td>

                                <?= date(
                                    "M d, Y",
                                    strtotime($record["attendance_date"])
                                ) ?>

                            </td>


                            <td>

                                <?= date(
                                    "h:i A",
                                    strtotime($record["attendance_time"])
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="5"
                            class="empty-table"
                        >

                            No attendance records yet.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


</main>


</body>

</html>