
<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$membershipPlans = require "../config/plans.php";
$pageTitle = "Add Member";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $middleName = trim($_POST["middle_name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");

    // Combine the names for compatibility with existing pages.
    $nameParts = array_filter(
        [$firstName, $middleName, $surname],
        fn($part) => $part !== ""
    );
    $fullName = implode(" ", $nameParts);

    $age = trim($_POST["age"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $membershipPlan = trim($_POST["membership_plan"] ?? "");
    $startDate = trim($_POST["start_date"] ?? "");

    if (
        $firstName === "" ||
        $surname === "" ||
        $age === "" ||
        $gender === "" ||
        $contact === "" ||
        $address === "" ||
        $membershipPlan === "" ||
        $startDate === ""
    ) {
        $error = "Please complete all required fields.";

    } elseif (!is_numeric($age) || $age < 1 || $age > 120) {
        $error = "Please enter a valid age.";

    } elseif (!array_key_exists($membershipPlan, $membershipPlans)) {
        $error = "Invalid membership plan.";

    } else {
        $dateObject = DateTime::createFromFormat("!Y-m-d", $startDate);

        if (
            !$dateObject ||
            $dateObject->format("Y-m-d") !== $startDate
        ) {
            $error = "Please enter a valid start date.";

        } else {
            $expirationDate = clone $dateObject;
            $plan = $membershipPlans[$membershipPlan];

            if ($plan["unit"] === "day") {
                $expirationDate->modify(
                    "+" . $plan["amount"] . " days"
                );
            } elseif ($plan["unit"] === "month") {
                $expirationDate->modify(
                    "+" . $plan["amount"] . " months"
                );
            } elseif ($plan["unit"] === "year") {
                $expirationDate->modify(
                    "+" . $plan["amount"] . " years"
                );
            }

            $expirationDate = $expirationDate->format("Y-m-d");

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO members (
                        first_name,
                        middle_name,
                        surname,
                        full_name,
                        age,
                        gender,
                        contact,
                        address,
                        membership_plan,
                        start_date,
                        expiration_date
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $firstName,
                    $middleName !== "" ? $middleName : null,
                    $surname,
                    $fullName,
                    $age,
                    $gender,
                    $contact,
                    $address,
                    $membershipPlan,
                    $startDate,
                    $expirationDate
                ]);

                // Generate the QR card for this new member.
                $newMemberId = (int) $pdo->lastInsertId();

                header(
                    "Location: qr.php?id=" . $newMemberId . "&new=1"
                );
                exit;

            } catch (PDOException $e) {
                error_log("Add member error: " . $e->getMessage());
                $error = "Unable to add member. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member | ONYX GYM</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php require_once "../includes/sidebar.php"; ?>

<main class="main-content">

    <header class="top-header">
        <div>
            <h1>Add Member</h1>
            <p>Register a new gym member.</p>
        </div>

        <div class="header-brand">ONYX GYM</div>
    </header>

    <?php if ($error !== ""): ?>
        <div class="error-message">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="form-section">

        <form method="POST" class="member-form">

            <h2>Personal Information</h2>

            <div class="form-grid">

                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="Juan"
                        maxlength="50"
                        value="<?= htmlspecialchars($_POST["first_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        placeholder="Dela"
                        maxlength="50"
                        value="<?= htmlspecialchars($_POST["middle_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="surname">Surname *</label>
                    <input
                        type="text"
                        id="surname"
                        name="surname"
                        placeholder="Cruz"
                        maxlength="50"
                        value="<?= htmlspecialchars($_POST["surname"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="age">Age *</label>
                    <input
                        type="number"
                        id="age"
                        name="age"
                        min="1"
                        max="120"
                        placeholder="18"
                        value="<?= htmlspecialchars($_POST["age"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= (($_POST["gender"] ?? "") === "Male") ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= (($_POST["gender"] ?? "") === "Female") ? "selected" : "" ?>>Female</option>
                        <option value="Other" <?= (($_POST["gender"] ?? "") === "Other") ? "selected" : "" ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number *</label>
                    <input
                        type="text"
                        id="contact"
                        name="contact"
                        placeholder="09XXXXXXXXX"
                        value="<?= htmlspecialchars($_POST["contact"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="form-group full-width">
                    <label for="address">Address *</label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="Complete address"
                        value="<?= htmlspecialchars($_POST["address"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

            </div>

            <h2 class="form-heading">Membership Information</h2>

            <div class="form-grid">

                <div class="form-group">
                    <label for="membership_plan">Membership Plan *</label>
                    <select id="membership_plan" name="membership_plan" required>
                        <option value="">Select Plan</option>

                        <?php foreach ($membershipPlans as $name => $plan): ?>
                            <option
                                value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                                <?= (($_POST["membership_plan"] ?? "") === $name) ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>
                                - ₱<?= number_format($plan["price"], 2) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date *</label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= htmlspecialchars($_POST["start_date"] ?? date("Y-m-d"), ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-note">
                The expiration date will be calculated automatically
                based on the selected membership plan.
            </div>

            <div class="form-actions">
                <a href="index.php" class="secondary-button">Cancel</a>

                <button type="submit" class="primary-button">
                    Save Member
                </button>
            </div>

        </form>

    </section>

</main>

</body>
</html>