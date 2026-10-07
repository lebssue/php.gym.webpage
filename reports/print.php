<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| REPORT SETTINGS
|--------------------------------------------------------------------------
*/

$allowedPeriods = [
    "daily",
    "weekly",
    "monthly",
    "yearly"
];

$period = $_GET["period"] ?? "daily";

if (!in_array($period, $allowedPeriods, true)) {
    $period = "daily";
}


$selectedDate = $_GET["date"] ?? date("Y-m-d");

$dateObject = DateTime::createFromFormat(
    "Y-m-d",
    $selectedDate
);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $selectedDate
) {

    $selectedDate = date("Y-m-d");

    $dateObject = new DateTime(
        $selectedDate
    );
}


/*
|--------------------------------------------------------------------------
| REPORT RANGE
|--------------------------------------------------------------------------
*/

switch ($period) {

    case "daily":

        $startDate = $dateObject->format("Y-m-d");
        $endDate = $dateObject->format("Y-m-d");

        $periodLabel =
            $dateObject->format("F d, Y");

        break;


    case "weekly":

        $start = clone $dateObject;

        $dayOfWeek =
            (int)$start->format("N");

        $start->modify(
            "-" . ($dayOfWeek - 1) . " days"
        );

        $end = clone $start;

        $end->modify("+6 days");

        $startDate =
            $start->format("Y-m-d");

        $endDate =
            $end->format("Y-m-d");

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

        $end->modify(
            "last day of this month"
        );

        $startDate =
            $start->format("Y-m-d");

        $endDate =
            $end->format("Y-m-d");

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

        $startDate =
            $start->format("Y-m-d");

        $endDate =
            $end->format("Y-m-d");

        $periodLabel =
            $dateObject->format("Y");

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
        COUNT(DISTINCT member_id) AS members_paid
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$summary = $stmt->fetch();

$revenue =
    (float)$summary["revenue"];

$paymentCount =
    (int)$summary["payment_count"];

$membersPaid =
    (int)$summary["members_paid"];


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
        payments.payment_date ASC,
        payments.id ASC
");

$stmt->execute([
    $startDate,
    $endDate
]);

$payments = $stmt->fetchAll();

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
        ONYX GYM - Revenue Report
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            padding: 40px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #fff;

            color: #111;
        }


        .report-container {

            max-width: 1100px;

            margin: auto;
        }


        .report-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            border-bottom: 3px solid #111;

            padding-bottom: 20px;

            margin-bottom: 25px;
        }


        .brand h1 {

            margin: 0;

            font-size: 30px;

            letter-spacing: 2px;
        }


        .brand p {

            margin: 6px 0 0;

            color: #666;

            font-size: 12px;
        }


        .report-title {

            text-align: right;
        }


        .report-title h2 {

            margin: 0;

            font-size: 20px;
        }


        .report-title p {

            margin: 7px 0 0;

            color: #666;

            font-size: 12px;
        }


        .summary {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 30px;
        }


        .summary-card {

            border: 1px solid #ddd;

            padding: 20px;

            border-radius: 6px;
        }


        .summary-card span {

            display: block;

            font-size: 10px;

            color: #777;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 8px;
        }


        .summary-card strong {

            font-size: 22px;
        }


        .summary-card.revenue strong {

            color: #b28a00;
        }


        .section-title {

            margin: 30px 0 12px;

            font-size: 16px;
        }


        table {

            width: 100%;

            border-collapse: collapse;
        }


        th {

            background: #111;

            color: #fff;

            padding: 11px;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;
        }


        td {

            border-bottom: 1px solid #ddd;

            padding: 11px;

            font-size: 12px;
        }


        .amount {

            font-weight: bold;

            color: #b28a00;
        }


        .total-row td {

            font-weight: bold;

            border-top: 2px solid #111;

            border-bottom: none;

            padding-top: 15px;
        }


        .report-footer {

            display: flex;

            justify-content: space-between;

            margin-top: 60px;
        }


        .signature {

            width: 220px;

            border-top: 1px solid #111;

            padding-top: 7px;

            font-size: 11px;
        }


        .print-button {

            display: block;

            margin: 30px auto 0;

            padding: 12px 25px;

            background: #111;

            color: #fff;

            border: none;

            cursor: pointer;

            border-radius: 5px;
        }


        @media print {

            body {

                padding: 0;
            }


            .print-button {

                display: none;
            }


            .report-container {

                max-width: none;
            }

        }


        @media (max-width: 700px) {

            body {

                padding: 20px;
            }


            .report-header {

                flex-direction: column;

                gap: 20px;
            }


            .report-title {

                text-align: left;
            }


            .summary {

                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="report-container">


    <!-- HEADER -->

    <div class="report-header">


        <div class="brand">

            <h1>
                ONYX GYM
            </h1>

            <p>
                Gym Management System
            </p>

        </div>


        <div class="report-title">

            <h2>
                Revenue Report
            </h2>

            <p>
                <?= htmlspecialchars($periodLabel) ?>
            </p>

        </div>


    </div>


    <!-- SUMMARY -->

    <div class="summary">


        <div class="summary-card revenue">

            <span>
                Total Revenue
            </span>

            <strong>
                ₱<?= number_format(
                    $revenue,
                    2
                ) ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Total Payments
            </span>

            <strong>
                <?= number_format(
                    $paymentCount
                ) ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Members Paid
            </span>

            <strong>
                <?= number_format(
                    $membersPaid
                ) ?>
            </strong>

        </div>


    </div>


    <!-- TABLE -->

    <h3 class="section-title">
        Detailed Payment Records
    </h3>


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
                        #<?= htmlspecialchars(
                            $payment["id"]
                        ) ?>
                    </td>


                    <td>
                        <?= date(
                            "M d, Y",
                            strtotime(
                                $payment["payment_date"]
                            )
                        ) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $payment["full_name"]
                        ) ?>
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


                    <td class="amount">

                        ₱<?= number_format(
                            $payment["amount"],
                            2
                        ) ?>

                    </td>

                </tr>

            <?php endforeach; ?>


            <tr class="total-row">

                <td colspan="5">
                    TOTAL REVENUE
                </td>

                <td class="amount">

                    ₱<?= number_format(
                        $revenue,
                        2
                    ) ?>

                </td>

            </tr>


        <?php else: ?>


            <tr>

                <td
                    colspan="6"
                    style="text-align:center;padding:30px;"
                >

                    No payment records
                    for this period.

                </td>

            </tr>


        <?php endif; ?>


        </tbody>

    </table>


    <!-- SIGNATURE -->

    <div class="report-footer">


        <div class="signature">

            Prepared by

        </div>


        <div class="signature">

            Date

        </div>


    </div>


    <button
        class="print-button"
        onclick="window.print()"
    >

        PRINT REPORT

    </button>


</div>


</body>

</html>