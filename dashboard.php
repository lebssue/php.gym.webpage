<?php

require_once "includes/auth.php";
require_once "config/database.php";

$pageTitle = "Dashboard";

/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

// Total members
$stmt = $pdo->query("SELECT COUNT(*) FROM members");
$totalMembers = $stmt->fetchColumn();

// Active members
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM members
    WHERE expiration_date >= CURDATE()
");
$activeMembers = $stmt->fetchColumn();

// Expired members
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM members
    WHERE expiration_date < CURDATE()
");
$expiredMembers = $stmt->fetchColumn();

// Today's attendance
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = CURDATE()
");
$todayAttendance = $stmt->fetchColumn();

// Total payments
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
");
$totalPayments = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| SALES GRAPH
|--------------------------------------------------------------------------
|
| Default graph = last 7 days
|
*/

$graphPeriod = $_GET["period"] ?? "daily";

$allowedPeriods = ["daily", "weekly", "monthly", "yearly"];

if (!in_array($graphPeriod, $allowedPeriods)) {
    $graphPeriod = "daily";
}

$graphLabels = [];
$graphValues = [];


/*
|--------------------------------------------------------------------------
| DAILY
|--------------------------------------------------------------------------
|
| Shows the last 7 days.
|
*/

if ($graphPeriod === "daily") {

    for ($i = 6; $i >= 0; $i--) {

        $date = date(
            "Y-m-d",
            strtotime("-$i days")
        );

        $label = date(
            "D",
            strtotime($date)
        );

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date = ?
        ");

        $stmt->execute([$date]);

        $revenue = $stmt->fetchColumn();

        $graphLabels[] = $label;
        $graphValues[] = (float)$revenue;
    }
}


/*
|--------------------------------------------------------------------------
| WEEKLY
|--------------------------------------------------------------------------
|
| Shows the last 8 weeks.
|
*/

elseif ($graphPeriod === "weekly") {

    for ($i = 7; $i >= 0; $i--) {

        $startDate = date(
            "Y-m-d",
            strtotime("monday -$i week")
        );

        $endDate = date(
            "Y-m-d",
            strtotime("$startDate +6 days")
        );

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date BETWEEN ? AND ?
        ");

        $stmt->execute([
            $startDate,
            $endDate
        ]);

        $revenue = $stmt->fetchColumn();

        $graphLabels[] = date(
            "M d",
            strtotime($startDate)
        );

        $graphValues[] = (float)$revenue;
    }
}


/*
|--------------------------------------------------------------------------
| MONTHLY
|--------------------------------------------------------------------------
|
| Shows the last 12 months.
|
*/

elseif ($graphPeriod === "monthly") {

    for ($i = 11; $i >= 0; $i--) {

        $monthDate = date(
            "Y-m-01",
            strtotime("-$i months")
        );

        $startDate = date(
            "Y-m-01",
            strtotime($monthDate)
        );

        $endDate = date(
            "Y-m-t",
            strtotime($monthDate)
        );

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date BETWEEN ? AND ?
        ");

        $stmt->execute([
            $startDate,
            $endDate
        ]);

        $revenue = $stmt->fetchColumn();

        $graphLabels[] = date(
            "M",
            strtotime($monthDate)
        );

        $graphValues[] = (float)$revenue;
    }
}


/*
|--------------------------------------------------------------------------
| YEARLY
|--------------------------------------------------------------------------
|
| Shows the last 5 years.
|
*/

elseif ($graphPeriod === "yearly") {

    for ($i = 4; $i >= 0; $i--) {

        $year = date(
            "Y",
            strtotime("-$i years")
        );

        $startDate = $year . "-01-01";
        $endDate = $year . "-12-31";

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date BETWEEN ? AND ?
        ");

        $stmt->execute([
            $startDate,
            $endDate
        ]);

        $revenue = $stmt->fetchColumn();

        $graphLabels[] = $year;
        $graphValues[] = (float)$revenue;
    }
}


/*
|--------------------------------------------------------------------------
| RECENT MEMBERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM members
    ORDER BY date_registered DESC
    LIMIT 5
");

$recentMembers = $stmt->fetchAll();

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
        Dashboard | ONYX GYM
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<?php require_once "includes/sidebar.php"; ?>


<main class="main-content">

    <!-- HEADER -->

    <div class="page-header">

        <div>
            <h1>Dashboard</h1>

            <p>
                Welcome back,
                <?= htmlspecialchars($_SESSION["username"]) ?>.
            </p>
        </div>

    </div>


    <!-- STATISTICS -->

    <div class="stats-grid">

        <!-- TOTAL MEMBERS -->

        <div class="stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Total Members
                </span>

                <strong>
                    <?= $totalMembers ?>
                </strong>

            </div>

        </div>


        <!-- ACTIVE MEMBERS -->

        <div class="stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Active Members
                </span>

                <strong>
                    <?= $activeMembers ?>
                </strong>

            </div>

        </div>


        <!-- EXPIRED MEMBERS -->

        <div class="stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Expired Members
                </span>

                <strong>
                    <?= $expiredMembers ?>
                </strong>

            </div>

        </div>


        <!-- TODAY ATTENDANCE -->

        <div class="stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Today's Attendance
                </span>

                <strong>
                    <?= $todayAttendance ?>
                </strong>

            </div>

        </div>


        <!-- TOTAL PAYMENTS -->

        <div class="stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Total Payments
                </span>

                <strong>
                    ₱<?= number_format($totalPayments, 2) ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- SALES GRAPH -->

    <section class="dashboard-section sales-section">

        <div class="section-header">

            <div>

                <h2>
                    Sales Overview
                </h2>

                <p>
                    Revenue collected from gym payments.
                </p>

            </div>


            <!-- GRAPH FILTER -->

            <form method="GET" class="sales-filter">

                <select
                    name="period"
                    onchange="this.form.submit()"
                >

                    <option
                        value="daily"
                        <?= $graphPeriod === "daily" ? "selected" : "" ?>
                    >
                        Daily
                    </option>

                    <option
                        value="weekly"
                        <?= $graphPeriod === "weekly" ? "selected" : "" ?>
                    >
                        Weekly
                    </option>

                    <option
                        value="monthly"
                        <?= $graphPeriod === "monthly" ? "selected" : "" ?>
                    >
                        Monthly
                    </option>

                    <option
                        value="yearly"
                        <?= $graphPeriod === "yearly" ? "selected" : "" ?>
                    >
                        Yearly
                    </option>

                </select>

            </form>

        </div>


        <div class="sales-chart-container">

            <canvas id="salesChart"></canvas>

        </div>

    </section>


    <!-- RECENT MEMBERS -->

    <section class="dashboard-section">

        <div class="section-header">

            <div>

                <h2>
                    Recent Members
                </h2>

                <p>
                    Latest registered gym members.
                </p>

            </div>

            <a
                href="members/index.php"
                class="view-all"
            >
                View All
            </a>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Member</th>

                        <th>Membership</th>

                        <th>Expiration</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (empty($recentMembers)): ?>

                    <tr>

                        <td
                            colspan="4"
                            style="text-align:center;"
                        >
                            No members registered yet.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($recentMembers as $member): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($member["id"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($member["full_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($member["membership_plan"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($member["expiration_date"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


</main>


<script>

/*
|--------------------------------------------------------------------------
| SALES GRAPH DATA
|--------------------------------------------------------------------------
*/

const salesLabels =
    <?= json_encode($graphLabels) ?>;

const salesValues =
    <?= json_encode($graphValues) ?>;


/*
|--------------------------------------------------------------------------
| CREATE SALES CHART
|--------------------------------------------------------------------------
*/

const salesCanvas =
    document.getElementById("salesChart");

if (salesCanvas) {

    new Chart(
        salesCanvas,
        {

            type: "line",

            data: {

                labels: salesLabels,

                datasets: [

                    {

                        label: "Revenue",

                        data: salesValues,

                        borderColor: "#e5b82e",

                        backgroundColor:
                            "rgba(229, 184, 46, 0.10)",

                        borderWidth: 2,

                        tension: 0.35,

                        fill: true,

                        pointBackgroundColor:
                            "#e5b82e",

                        pointBorderColor:
                            "#111111",

                        pointBorderWidth: 2,

                        pointRadius: 4,

                        pointHoverRadius: 6

                    }

                ]

            },


            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        display: false

                    },

                    tooltip: {

                        callbacks: {

                            label: function(context) {

                                return " ₱" +
                                    Number(
                                        context.raw
                                    ).toLocaleString(
                                        "en-PH",
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    );

                            }

                        }

                    }

                },


                scales: {

                    x: {

                        grid: {

                            color:
                                "rgba(255,255,255,0.05)"

                        },

                        ticks: {

                            color: "#888"

                        }

                    },


                    y: {

                        beginAtZero: true,

                        grid: {

                            color:
                                "rgba(255,255,255,0.05)"

                        },

                        ticks: {

                            color: "#888",

                            callback: function(value) {

                                return "₱" +
                                    Number(value)
                                    .toLocaleString(
                                        "en-PH"
                                    );

                            }

                        }

                    }

                }

            }

        }
    );

}

</script>

</body>

</html>