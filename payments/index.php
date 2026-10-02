<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$pageTitle = "Payments";

$message = "";
$messageType = "";


// =====================================================
// RECORD PAYMENT
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $memberId = filter_input(
        INPUT_POST,
        "member_id",
        FILTER_VALIDATE_INT
    );

    $membershipPlan = trim($_POST["membership_plan"] ?? "");
    $amount = trim($_POST["amount"] ?? "");
    $paymentMethod = trim($_POST["payment_method"] ?? "");
    $paymentDate = trim($_POST["payment_date"] ?? "");


    // -------------------------------------------------
    // VALIDATE MEMBER
    // -------------------------------------------------

    if (!$memberId) {

        $message = "Please select a valid member.";
        $messageType = "error";

    } else {

        $stmt = $pdo->prepare("
            SELECT id, full_name
            FROM members
            WHERE id = ?
        ");

        $stmt->execute([$memberId]);

        $member = $stmt->fetch();


        if (!$member) {

            $message = "Selected member does not exist.";
            $messageType = "error";

        }

        // -------------------------------------------------
        // VALIDATE PLAN
        // -------------------------------------------------

        elseif ($membershipPlan === "") {

            $message = "Please enter the membership plan.";
            $messageType = "error";

        }

        // -------------------------------------------------
        // VALIDATE AMOUNT
        // -------------------------------------------------

        elseif (!is_numeric($amount) || $amount <= 0) {

            $message = "Please enter a valid payment amount.";
            $messageType = "error";

        }

        // -------------------------------------------------
        // VALIDATE PAYMENT METHOD
        // -------------------------------------------------

        elseif (!in_array(
            $paymentMethod,
            ["Cash", "GCash", "Other"],
            true
        )) {

            $message = "Please select a valid payment method.";
            $messageType = "error";

        }

        // -------------------------------------------------
        // VALIDATE DATE
        // -------------------------------------------------

        elseif (
            !$paymentDate ||
            !DateTime::createFromFormat("Y-m-d", $paymentDate) ||
            DateTime::createFromFormat("Y-m-d", $paymentDate)->format("Y-m-d") !== $paymentDate
        ) {

            $message = "Please enter a valid payment date.";
            $messageType = "error";

        }

        // -------------------------------------------------
        // SAVE PAYMENT
        // -------------------------------------------------

        else {

            $stmt = $pdo->prepare("
                INSERT INTO payments
                (
                    member_id,
                    membership_plan,
                    amount,
                    payment_method,
                    payment_date
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $memberId,
                $membershipPlan,
                $amount,
                $paymentMethod,
                $paymentDate
            ]);


            $message =
                "Payment successfully recorded for " .
                $member["full_name"] . ".";

            $messageType = "success";
        }

    }

}


// =====================================================
// GET MEMBERS
// =====================================================

$stmt = $pdo->query("
    SELECT
        id,
        full_name,
        membership_plan,
        expiration_date
    FROM members
    ORDER BY full_name ASC
");

$members = $stmt->fetchAll();


// =====================================================
// PAYMENT HISTORY
// =====================================================

$stmt = $pdo->query("
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

    ORDER BY
        payments.payment_date DESC,
        payments.id DESC
");

$payments = $stmt->fetchAll();


// =====================================================
// TOTAL PAYMENTS
// =====================================================

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
");

$totalPayments = $stmt->fetchColumn();

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
        Payments | ONYX GYM
    </title>

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

            <h1>Payments</h1>

            <p>
                Record and manage gym membership payments.
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

        <div
            class="<?= $messageType === "success"
                ? "success-message"
                : "error-message"
            ?>"
        >

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         PAYMENT SUMMARY
    ================================================== -->

    <div class="payment-summary">


        <div class="payment-total-card">

            <span>
                TOTAL PAYMENTS
            </span>

            <strong>
                ₱<?= number_format($totalPayments, 2) ?>
            </strong>

        </div>


        <div class="payment-total-card">

            <span>
                PAYMENT RECORDS
            </span>

            <strong>
                <?= count($payments) ?>
            </strong>

        </div>


    </div>


    <!-- =================================================
         RECORD PAYMENT
    ================================================== -->

    <section class="dashboard-section">


        <div class="section-header">

            <h2>Record Payment</h2>

            <p>
                Enter the payment information below.
            </p>

        </div>


        <form
            method="POST"
            class="payment-form"
        >


            <!-- MEMBER -->

            <div class="form-group">

                <label for="member_id">
                    Member
                </label>

                <select
                    id="member_id"
                    name="member_id"
                    required
                >

                    <option value="">
                        Select Member
                    </option>


                    <?php foreach ($members as $member): ?>

                        <option
                            value="<?= htmlspecialchars($member["id"]) ?>"
                        >

                            <?= htmlspecialchars($member["full_name"]) ?>

                            —
                            <?= htmlspecialchars($member["membership_plan"]) ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- PLAN -->

            <div class="form-group">

                <label for="membership_plan">
                    Membership Plan
                </label>

                <input
                    type="text"
                    id="membership_plan"
                    name="membership_plan"
                    placeholder="Example: Monthly"
                    required
                >

            </div>


            <!-- AMOUNT -->

            <div class="form-group">

                <label for="amount">
                    Amount
                </label>

                <input
                    type="number"
                    id="amount"
                    name="amount"
                    placeholder="500.00"
                    min="0.01"
                    step="0.01"
                    required
                >

            </div>


            <!-- DATE -->

            <div class="form-group">

                <label for="payment_date">
                    Payment Date
                </label>

                <input
                    type="date"
                    id="payment_date"
                    name="payment_date"
                    value="<?= date("Y-m-d") ?>"
                    required
                >

            </div>


            <!-- METHOD -->

            <div class="form-group">

                <label for="payment_method">
                    Payment Method
                </label>

                <select
                    id="payment_method"
                    name="payment_method"
                    required
                >

                    <option value="">
                        Select Method
                    </option>

                    <option value="Cash">
                        Cash
                    </option>

                    <option value="GCash">
                        GCash
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>


            <div class="payment-form-button">

                <button
                    type="submit"
                    class="primary-button"
                >
                    RECORD PAYMENT
                </button>

            </div>


        </form>


    </section>


    <!-- =================================================
         PAYMENT HISTORY
    ================================================== -->

    <section class="dashboard-section">


        <div class="section-header">

            <h2>Payment History</h2>

            <p>
                All recorded payments.
            </p>

        </div>


        <div class="table-container">


            <table>


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Member</th>

                        <th>Plan</th>

                        <th>Amount</th>

                        <th>Method</th>

                        <th>Date</th>

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

                                <strong>
                                    <?= htmlspecialchars($payment["full_name"]) ?>
                                </strong>

                                <small>
                                    Member #<?= htmlspecialchars($payment["member_id"]) ?>
                                </small>

                            </td>


                            <td>
                                <?= htmlspecialchars($payment["membership_plan"]) ?>
                            </td>


                            <td>

                                <strong class="payment-amount">

                                    ₱<?= number_format(
                                        $payment["amount"],
                                        2
                                    ) ?>

                                </strong>

                            </td>


                            <td>
                                <?= htmlspecialchars($payment["payment_method"]) ?>
                            </td>


                            <td>

                                <?= date(
                                    "M d, Y",
                                    strtotime($payment["payment_date"])
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty-table"
                        >

                            No payment records yet.

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