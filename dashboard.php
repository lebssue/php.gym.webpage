<?php

require_once "includes/auth.php";
require_once "config/database.php";

$pageTitle = "Dashboard";


// ========================================
// TOTAL MEMBERS
// ========================================

$stmt = $pdo->query("SELECT COUNT(*) FROM members");
$totalMembers = $stmt->fetchColumn();


// ========================================
// ACTIVE MEMBERS
// Expiration date is today or later
// ========================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM members
    WHERE expiration_date >= CURDATE()
");

$activeMembers = $stmt->fetchColumn();


// ========================================
// EXPIRED MEMBERS
// ========================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM members
    WHERE expiration_date < CURDATE()
");

$expiredMembers = $stmt->fetchColumn();


// ========================================
// TODAY'S ATTENDANCE
// ========================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = CURDATE()
");

$todayAttendance = $stmt->fetchColumn();


// ========================================
// TOTAL PAYMENTS
// ========================================

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
");

$totalPayments = $stmt->fetchColumn();


// ========================================
// RECENT MEMBERS
// ========================================

$stmt = $pdo->query("
    SELECT
        id,
        full_name,
        membership_plan,
        expiration_date
    FROM members
    ORDER BY date_registered DESC
    LIMIT 5
");

$recentMembers = $stmt->fetchAll();

?>

<?php require_once "includes/header.php"; ?>

<?php require_once "includes/sidebar.php"; ?>


<main class="main-content">

    <!-- TOP HEADER -->

    <header class="top-header">

        <div>
            <h1>Dashboard</h1>

            <p>
                Welcome back,
                <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong>
            </p>
        </div>

        <div class="header-brand">
            ONYX GYM
        </div>

    </header>


    <!-- STATISTICS -->

    <section class="stats-grid">


        <!-- TOTAL MEMBERS -->

        <div class="stat-card">

            <div class="stat-icon">
                ♙
            </div>

            <div>

                <p>Total Members</p>

                <h2>
                    <?= number_format($totalMembers) ?>
                </h2>

            </div>

        </div>


        <!-- ACTIVE MEMBERS -->

        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <p>Active Members</p>

                <h2>
                    <?= number_format($activeMembers) ?>
                </h2>

            </div>

        </div>


        <!-- EXPIRED MEMBERS -->

        <div class="stat-card">

            <div class="stat-icon">
                !
            </div>

            <div>

                <p>Expired Members</p>

                <h2>
                    <?= number_format($expiredMembers) ?>
                </h2>

            </div>

        </div>


        <!-- TODAY ATTENDANCE -->

        <div class="stat-card">

            <div class="stat-icon">
                ↗
            </div>

            <div>

                <p>Today's Attendance</p>

                <h2>
                    <?= number_format($todayAttendance) ?>
                </h2>

            </div>

        </div>


        <!-- TOTAL PAYMENTS -->

        <div class="stat-card payment-card">

            <div class="stat-icon">
                ₱
            </div>

            <div>

                <p>Total Payments</p>

                <h2>
                    ₱<?= number_format($totalPayments, 2) ?>
                </h2>

            </div>

        </div>


    </section>


    <!-- RECENT MEMBERS -->

    <section class="dashboard-section">

        <div class="section-header">

            <div>

                <h2>Recently Registered Members</h2>

                <p>Latest members added to the system.</p>

            </div>

            <a href="/onyx-gym/members/index.php" class="view-all">
                View All
            </a>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Member</th>

                        <th>Membership</th>

                        <th>Status</th>

                        <th>Expiration</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (count($recentMembers) > 0): ?>

                    <?php foreach ($recentMembers as $member): ?>

                        <?php

                        $status =
                            $member["expiration_date"] >= date("Y-m-d")
                            ? "ACTIVE"
                            : "EXPIRED";

                        ?>

                        <tr>

                            <td>

                                <strong>
                                    <?= htmlspecialchars($member["full_name"]) ?>
                                </strong>

                                <small>
                                    ID #<?= htmlspecialchars($member["id"]) ?>
                                </small>

                            </td>


                            <td>
                                <?= htmlspecialchars($member["membership_plan"]) ?>
                            </td>


                            <td>

                                <?php if ($status === "ACTIVE"): ?>

                                    <span class="status active">
                                        ACTIVE
                                    </span>

                                <?php else: ?>

                                    <span class="status expired">
                                        EXPIRED
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>
                                <?= date(
                                    "M d, Y",
                                    strtotime($member["expiration_date"])
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="4" class="empty-table">

                            No members registered yet.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


</main>


<?php require_once "includes/footer.php"; ?>