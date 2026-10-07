<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$pageTitle = "Reports";

/*
|--------------------------------------------------------------------------
| REPORT SETTINGS
|--------------------------------------------------------------------------
*/

$allowedPeriods = ["daily", "weekly", "monthly", "yearly"];

$period = $_GET["period"] ?? "daily";

if (!in_array($period, $allowedPeriods, true)) {
    $period = "daily";
}

$selectedDate = $_GET["date"] ?? date("Y-m-d");

$dateObject = DateTime::createFromFormat("Y-m-d", $selectedDate);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $selectedDate
) {
    $selectedDate = date("Y-m-d");
    $dateObject = new DateTime($selectedDate);
}


/*
|--------------------------------------------------------------------------
| DETERMINE REPORT RANGE
|--------------------------------------------------------------------------
*/

$startDate = "";
$endDate = "";
$periodLabel = "";

switch ($period) {

    case "daily":

        $startDate = $dateObject->format("Y-m-d");
        $endDate = $dateObject->format("Y-m-d");

        $periodLabel = $dateObject->format("F d, Y");

        break;


    case "weekly":

        $start = clone $dateObject;

        $dayOfWeek = (int)$start->format("N");

        $start->modify("-" . ($dayOfWeek - 1) . " days");

        $end = clone $start;
        $end->modify("+6 days");

        $startDate = $start->format("Y-m-d");
        $endDate = $end->format("Y-m-d");

        $periodLabel =
            $start->format("M d, Y") .
            " - " .
            $end->format("M d, Y");

        break;


    case "monthly":

        $start = new DateTime(
            $dateObject->format("Y-m-01")
        );

        $end = clone $start;
        $end->modify("last day of this month");

        $startDate = $start->format("Y-m-d");
        $endDate = $end->format("Y-m-d");

        $periodLabel =
            $start->format("F Y");

        break;


    case "yearly":

        $start = new DateTime(
            $dateObject->format("Y-01-01")
        );

        $end = new DateTime(
            $dateObject->format("Y-12-31")
        );

        $startDate = $start->format("Y-m-d");
        $endDate = $end->format("Y-m-d");

        $periodLabel =
            $start->format("Y");

        break;
}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(amount), 0) AS revenue,
        COUNT(*) AS payment_count,
        COUNT(DISTINCT member_id) AS members_paid,
        COALESCE(AVG(amount), 0) AS average_payment
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$summary = $stmt->fetch();

$revenue = (float)$summary["revenue"];
$paymentCount = (int)$summary["payment_count"];
$membersPaid = (int)$summary["members_paid"];
$averagePayment = (float)$summary["average_payment"];


/*
|--------------------------------------------------------------------------
| PAYMENT DETAILS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        payments.id,
        payments.membership_plan,
        payments.amount,
        payments.payment_method,
        payments.payment_date,
        members.full_name,
        members.id AS member_id

    FROM payments

    INNER JOIN members
        ON payments.member_id = members.id

    WHERE payments.payment_date BETWEEN ? AND ?

    ORDER BY
        payments.payment_date DESC,
        payments.id DESC
");

$stmt->execute([
    $startDate,
    $endDate
]);

$payments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| CHART DATA
|--------------------------------------------------------------------------
*/

$chartLabels = [];
$chartValues = [];


/*
|--------------------------------------------------------------------------
| DAILY
|--------------------------------------------------------------------------
*/

if ($period === "daily") {

    $stmt = $pdo->prepare("
        SELECT
            payment_date,
            SUM(amount) AS total
        FROM payments
        WHERE payment_date = ?
        GROUP BY payment_date
    ");

    $stmt->execute([$selectedDate]);

    $row = $stmt->fetch();

    $chartLabels[] = $dateObject->format("M d");
    $chartValues[] = $row
        ? (float)$row["total"]
        : 0;
}


/*
|--------------------------------------------------------------------------
| WEEKLY
|--------------------------------------------------------------------------
*/

elseif ($period === "weekly") {

    $current = new DateTime($startDate);
    $end = new DateTime($endDate);

    while ($current <= $end) {

        $date = $current->format("Y-m-d");

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date = ?
        ");

        $stmt->execute([$date]);

        $total = (float)$stmt->fetchColumn();

        $chartLabels[] = $current->format("D");
        $chartValues[] = $total;

        $current->modify("+1 day");
    }
}


/*
|--------------------------------------------------------------------------
| MONTHLY
|--------------------------------------------------------------------------
*/

elseif ($period === "monthly") {

    $daysInMonth = (int)$dateObject->format("t");

    for ($day = 1; $day <= $daysInMonth; $day++) {

        $date = sprintf(
            "%s-%02d",
            $dateObject->format("Y-m"),
            $day
        );

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date = ?
        ");

        $stmt->execute([$date]);

        $total = (float)$stmt->fetchColumn();

        $chartLabels[] = (string)$day;
        $chartValues[] = $total;
    }
}


/*
|--------------------------------------------------------------------------
| YEARLY
|--------------------------------------------------------------------------
*/

elseif ($period === "yearly") {

    for ($month = 1; $month <= 12; $month++) {

        $monthString = sprintf(
            "%s-%02d",
            $dateObject->format("Y"),
            $month
        );

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE payment_date LIKE ?
        ");

        $stmt->execute([
            $monthString . "%"
        ]);

        $total = (float)$stmt->fetchColumn();

        $chartLabels[] = date(
            "M",
            strtotime(
                $dateObject->format("Y") .
                "-" .
                sprintf("%02d", $month) .
                "-01"
            )
        );

        $chartValues[] = $total;
    }
}


/*
|--------------------------------------------------------------------------
| PREVIOUS / NEXT PERIOD
|--------------------------------------------------------------------------
*/

$previousDate = clone $dateObject;
$nextDate = clone $dateObject;

switch ($period) {

    case "daily":

        $previousDate->modify("-1 day");
        $nextDate->modify("+1 day");

        break;

    case "weekly":

        $previousDate->modify("-7 days");
        $nextDate->modify("+7 days");

        break;

    case "monthly":

        $previousDate->modify("-1 month");
        $nextDate->modify("+1 month");

        break;

    case "yearly":

        $previousDate->modify("-1 year");
        $nextDate->modify("+1 year");

        break;
}

$previousDateValue = $previousDate->format("Y-m-d");
$nextDateValue = $nextDate->format("Y-m-d");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports | ONYX GYM</title>

    <link
        rel="stylesheet"
        href="/onyx-gym/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | REPORT PAGE
        |--------------------------------------------------------------------------
        */

        .reports-toolbar {

            display: flex;
            align-items: flex-end;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;

            flex-wrap: wrap;
        }


        .report-controls {

            display: flex;

            align-items: flex-end;

            gap: 12px;

            flex-wrap: wrap;
        }


        .report-control {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .report-control label {

            color: #777;

            font-size: 10px;

            font-weight: bold;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .report-control select,
        .report-control input {

            min-width: 150px;

            padding: 11px 13px;

            background: #111;

            color: #fff;

            border: 1px solid #333;

            border-radius: 6px;

            outline: none;

            font-family: inherit;
        }


        .report-control select:focus,
        .report-control input:focus {

            border-color: #e5b82e;
        }


        .report-control select option {

            background: #111;

            color: #fff;
        }


        .period-wrapper {

            position: relative;
        }


        .period-button {

            min-width: 150px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 11px 13px;

            background: #111;

            color: #fff;

            border: 1px solid #333;

            border-radius: 6px;

            cursor: pointer;
        }


        .period-button:hover {

            border-color: #e5b82e;
        }


        .period-arrow {

            transition: transform 0.2s ease;
        }


        .period-wrapper.open .period-arrow {

            transform: rotate(180deg);
        }


        .period-menu {

            position: absolute;

            top: calc(100% + 6px);

            left: 0;

            width: 100%;

            background: #111;

            border: 1px solid #333;

            border-radius: 6px;

            overflow: hidden;

            z-index: 50;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-6px);

            transition:
                opacity 0.2s ease,
                transform 0.2s ease,
                visibility 0.2s ease;
        }


        .period-wrapper.open .period-menu {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        .period-menu a {

            display: block;

            padding: 11px 13px;

            color: #aaa;

            text-decoration: none;

            font-size: 12px;
        }


        .period-menu a:hover {

            background: #1d1d1d;

            color: #e5b82e;
        }


        .period-menu a.active {

            background: #191919;

            color: #e5b82e;

            font-weight: bold;
        }


        .report-navigation {

            display: flex;

            gap: 8px;
        }


        .report-nav-button {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #111;

            border: 1px solid #333;

            color: #aaa;

            border-radius: 6px;

            text-decoration: none;

            transition: 0.2s;
        }


        .report-nav-button:hover {

            border-color: #e5b82e;

            color: #e5b82e;

            transform: translateY(-2px);
        }


        .print-report-button {

            padding: 12px 18px;

            background: #e5b82e;

            color: #111;

            border: none;

            border-radius: 6px;

            font-size: 11px;

            font-weight: bold;

            text-decoration: none;

            cursor: pointer;

            transition: 0.2s;
        }


        .print-report-button:hover {

            background: #f0c743;

            transform: translateY(-2px);
        }


        /*
        |--------------------------------------------------------------------------
        | SUMMARY CARDS
        |--------------------------------------------------------------------------
        */

        .report-summary {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }


        .report-card {

            background: #111;

            border: 1px solid #292929;

            border-radius: 8px;

            padding: 22px;

            transition: 0.2s;
        }


        .report-card:hover {

            border-color: #3a3a3a;

            transform: translateY(-2px);
        }


        .report-card span {

            display: block;

            color: #777;

            font-size: 10px;

            font-weight: bold;

            letter-spacing: 1px;

            margin-bottom: 10px;
        }


        .report-card strong {

            display: block;

            color: #e5b82e;

            font-size: 25px;
        }


        /*
        |--------------------------------------------------------------------------
        | CHART
        |--------------------------------------------------------------------------
        */

        .report-chart-section {

            background: #111;

            border: 1px solid #292929;

            border-radius: 8px;

            padding: 25px;

            margin-bottom: 25px;
        }


        .report-chart-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .report-chart-header h2 {

            margin: 0;

            color: #fff;

            font-size: 17px;
        }


        .report-chart-header span {

            color: #777;

            font-size: 11px;
        }


        .chart-wrapper {

            position: relative;

            width: 100%;

            height: 330px;
        }


        /*
        |--------------------------------------------------------------------------
        | REPORT INFO
        |--------------------------------------------------------------------------
        */

        .report-period-info {

            background: #111;

            border: 1px solid #292929;

            border-radius: 8px;

            padding: 20px 25px;

            margin-bottom: 25px;
        }


        .report-period-info span {

            display: block;

            color: #777;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 7px;
        }


        .report-period-info strong {

            color: #fff;

            font-size: 17px;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .report-amount {

            color: #e5b82e !important;

            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .report-summary {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 700px) {

            .report-summary {

                grid-template-columns: 1fr;
            }


            .reports-toolbar {

                align-items: stretch;
            }


            .report-controls {

                width: 100%;

                flex-direction: column;

                align-items: stretch;
            }


            .report-control,
            .report-control select,
            .report-control input {

                width: 100%;

                box-sizing: border-box;
            }


            .report-navigation {

                width: 100%;
            }


            .report-nav-button {

                flex: 1;
            }


            .print-report-button {

                width: 100%;

                text-align: center;

                box-sizing: border-box;
            }


            .chart-wrapper {

                height: 260px;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PRINT
        |--------------------------------------------------------------------------
        */

        @media print {

            .sidebar,
            .reports-toolbar,
            .top-header {

                display: none !important;
            }


            .main-content {

                margin-left: 0 !important;

                width: 100% !important;

                padding: 20px !important;
            }


            .report-card,
            .report-chart-section,
            .report-period-info,
            .dashboard-section {

                border: 1px solid #ddd;

                background: #fff;
            }


            body {

                background: #fff;

                color: #111;
            }
        }

    </style>

</head>


<body>


<?php require_once "../includes/sidebar.php"; ?>


<main class="main-content">


    <!-- HEADER -->

    <div class="top-header">

        <div>

            <h1>Reports</h1>

            <p>
                View and analyze ONYX GYM revenue.
            </p>

        </div>


        <div class="header-brand">
            ONYX GYM
        </div>

    </div>


    <!-- REPORT CONTROLS -->

    <div class="reports-toolbar">


        <div class="report-controls">


            <!-- PERIOD -->

            <div class="report-control">

                <label>
                    Report Period
                </label>


                <div
                    class="period-wrapper"
                    id="periodWrapper"
                >

                    <button
                        type="button"
                        class="period-button"
                        id="periodButton"
                    >

                        <span>
                            <?= ucfirst($period) ?>
                        </span>

                        <span class="period-arrow">
                            ▼
                        </span>

                    </button>


                    <div
                        class="period-menu"
                        id="periodMenu"
                    >

                        <a
                            href="?period=daily&date=<?= $selectedDate ?>"
                            class="<?= $period === "daily" ? "active" : "" ?>"
                        >
                            Daily
                        </a>

                        <a
                            href="?period=weekly&date=<?= $selectedDate ?>"
                            class="<?= $period === "weekly" ? "active" : "" ?>"
                        >
                            Weekly
                        </a>

                        <a
                            href="?period=monthly&date=<?= $selectedDate ?>"
                            class="<?= $period === "monthly" ? "active" : "" ?>"
                        >
                            Monthly
                        </a>

                        <a
                            href="?period=yearly&date=<?= $selectedDate ?>"
                            class="<?= $period === "yearly" ? "active" : "" ?>"
                        >
                            Yearly
                        </a>

                    </div>

                </div>

            </div>


            <!-- DATE -->

            <form
                method="GET"
                class="report-control"
            >

                <label for="reportDate">
                    Date
                </label>

                <input
                    type="date"
                    id="reportDate"
                    name="date"
                    value="<?= htmlspecialchars($selectedDate) ?>"
                    onchange="this.form.submit()"
                >

                <input
                    type="hidden"
                    name="period"
                    value="<?= htmlspecialchars($period) ?>"
                >

            </form>


            <!-- PREVIOUS / NEXT -->

            <div class="report-navigation">

                <a
                    href="?period=<?= $period ?>&date=<?= $previousDateValue ?>"
                    class="report-nav-button"
                    title="Previous"
                >
                    ‹
                </a>


                <a
                    href="?period=<?= $period ?>&date=<?= $nextDateValue ?>"
                    class="report-nav-button"
                    title="Next"
                >
                    ›
                </a>

            </div>


        </div>


        <a
            href="print.php?period=<?= $period ?>&date=<?= $selectedDate ?>"
            target="_blank"
            class="print-report-button"
        >
            PRINT REPORT
        </a>


    </div>


    <!-- PERIOD -->

    <div class="report-period-info">

        <span>
            Current Report
        </span>

        <strong>
            <?= htmlspecialchars($periodLabel) ?>
        </strong>

    </div>


    <!-- SUMMARY -->

    <div class="report-summary">


        <div class="report-card">

            <span>
                PERIOD REVENUE
            </span>

            <strong>
                ₱<?= number_format($revenue, 2) ?>
            </strong>

        </div>


        <div class="report-card">

            <span>
                TOTAL PAYMENTS
            </span>

            <strong>
                <?= number_format($paymentCount) ?>
            </strong>

        </div>


        <div class="report-card">

            <span>
                MEMBERS PAID
            </span>

            <strong>
                <?= number_format($membersPaid) ?>
            </strong>

        </div>


        <div class="report-card">

            <span>
                AVERAGE PAYMENT
            </span>

            <strong>
                ₱<?= number_format($averagePayment, 2) ?>
            </strong>

        </div>


    </div>


    <!-- CHART -->

    <section class="report-chart-section">


        <div class="report-chart-header">

            <h2>
                Revenue Overview
            </h2>

            <span>
                <?= htmlspecialchars($periodLabel) ?>
            </span>

        </div>


        <div class="chart-wrapper">

            <canvas id="revenueChart"></canvas>

        </div>


    </section>


    <!-- DETAILED REPORT -->

    <section class="dashboard-section">


        <div class="section-header">

            <h2>
                Detailed Revenue Report
            </h2>

            <p>
                Payment records for this period.
            </p>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Member
                        </th>

                        <th>
                            Plan
                        </th>

                        <th>
                            Method
                        </th>

                        <th>
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($payments) > 0): ?>


                    <?php foreach ($payments as $payment): ?>

                        <tr>

                            <td>
                                #<?= htmlspecialchars($payment["id"]) ?>
                            </td>


                            <td>
                                <?= date(
                                    "M d, Y",
                                    strtotime($payment["payment_date"])
                                ) ?>
                            </td>


                            <td>

                                <strong>
                                    <?= htmlspecialchars(
                                        $payment["full_name"]
                                    ) ?>
                                </strong>

                                <small>
                                    Member #
                                    <?= htmlspecialchars(
                                        $payment["member_id"]
                                    ) ?>
                                </small>

                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $payment["membership_plan"]
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $payment["payment_method"]
                                ) ?>
                            </td>


                            <td>

                                <strong class="report-amount">

                                    ₱<?= number_format(
                                        $payment["amount"],
                                        2
                                    ) ?>

                                </strong>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty-table"
                        >

                            No payment records found
                            for this period.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


    </section>


</main>


<!-- CHART.JS -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

    /*
    |--------------------------------------------------------------------------
    | PERIOD DROPDOWN
    |--------------------------------------------------------------------------
    */

    const periodWrapper =
        document.getElementById("periodWrapper");

    const periodButton =
        document.getElementById("periodButton");


    periodButton.addEventListener(
        "click",
        function () {

            periodWrapper.classList.toggle("open");

        }
    );


    document.addEventListener(
        "click",
        function (event) {

            if (
                !periodWrapper.contains(event.target)
            ) {

                periodWrapper.classList.remove("open");

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | REVENUE CHART
    |--------------------------------------------------------------------------
    */

    const chartLabels =
        <?= json_encode($chartLabels) ?>;

    const chartValues =
        <?= json_encode($chartValues) ?>;


    const ctx =
        document
            .getElementById("revenueChart")
            .getContext("2d");


    new Chart(ctx, {

        type: "line",

        data: {

            labels: chartLabels,

            datasets: [

                {

                    label: "Revenue",

                    data: chartValues,

                    borderWidth: 2,

                    tension: 0.35,

                    fill: true

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
                                        minimumFractionDigits: 2
                                    }
                                );

                        }

                    }

                }

            },


            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {

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

    });

</script>


</body>

</html>