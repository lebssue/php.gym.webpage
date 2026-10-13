
<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

// Get the member ID from the URL.
$memberId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$memberId || $memberId < 1) {
    exit("Invalid member ID.");
}

// Find the member in the database.
$stmt = $pdo->prepare("
    SELECT id, full_name, membership_plan, expiration_date
    FROM members
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    exit("Member not found.");
}

// Create the unique QR content.
$qrValue = "ONYX-MEMBER-" . (int) $member["id"];

$name = htmlspecialchars(
    $member["full_name"],
    ENT_QUOTES,
    "UTF-8"
);

$plan = htmlspecialchars(
    (string) $member["membership_plan"],
    ENT_QUOTES,
    "UTF-8"
);

$expiration = !empty($member["expiration_date"])
    ? date("M d, Y", strtotime($member["expiration_date"]))
    : "Not set";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Member QR Code | ONYX GYM</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            background: #f1f1f1;
            color: #111;
            font-family: Arial, sans-serif;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        button {
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .print-button {
            background: #e5b82e;
            color: #111;
        }

        .close-button {
            background: #222;
            color: white;
        }

        .qr-card {
            width: 360px;
            max-width: 100%;
            margin: auto;
            padding: 25px;
            background: white;
            border: 3px solid #e5b82e;
            border-radius: 12px;
            text-align: center;
        }

        .brand {
            margin: 0;
            font-size: 30px;
            font-weight: 900;
            letter-spacing: 3px;
        }

        .brand span {
            color: #b58a00;
        }

        .subtitle {
            margin-bottom: 25px;
            color: #777;
            font-size: 11px;
            letter-spacing: 2px;
        }

        .member-name {
            overflow-wrap: anywhere;
        }

        .member-id {
            color: #9a7500;
            font-weight: bold;
            font-size: 13px;
        }

        #qrcode {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 220px;
            margin: 20px 0;
        }

        #qrcode img,
        #qrcode canvas {
            max-width: 100%;
            height: auto;
        }

        .instruction {
            color: #666;
            font-size: 12px;
        }

        .details {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: left;
            font-size: 11px;
        }

        .details strong {
            display: block;
            margin-top: 5px;
            font-size: 12px;
        }

        @media print {
            @page {
                margin: 8mm;
            }

            body {
                padding: 0;
                background: white;
            }

            .actions {
                display: none;
            }

            .qr-card {
                border-radius: 0;
            }
        }
    </style>
</head>

<body>

    <div class="actions">
        <button
            class="print-button"
            onclick="window.print()"
        >
            Print QR Card
        </button>

        
<button
    class="close-button"
    type="button"
    onclick="window.location.href='index.php'"
>
    Back to Members
</button>
    </div>

    <main class="qr-card">

        <h1 class="brand">
            ONYX <span>GYM</span>
        </h1>

        <p class="subtitle">
            OFFICIAL MEMBER CHECK-IN
        </p>

        <h2 class="member-name">
            <?= $name ?>
        </h2>

        <div class="member-id">
            <?= htmlspecialchars($qrValue, ENT_QUOTES, "UTF-8") ?>
        </div>

        <div id="qrcode"></div>

        <p class="instruction">
            Scan this code at the ONYX GYM attendance station.
        </p>

        <div class="details">
            <div>
                MEMBERSHIP
                <strong><?= $plan ?></strong>
            </div>

            <div>
                EXPIRES
                <strong>
                    <?= htmlspecialchars($expiration, ENT_QUOTES, "UTF-8") ?>
                </strong>
            </div>
        </div>

    </main>

    <!-- Generates the QR code in your browser -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        const qrContainer = document.getElementById("qrcode");

        const qrValue = <?= json_encode(
            $qrValue,
            JSON_HEX_TAG | JSON_HEX_APOS |
            JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        if (typeof QRCode === "undefined") {
            qrContainer.textContent =
                "QR generator failed to load. Check your internet connection and refresh.";
        } else {
            new QRCode(qrContainer, {
                text: qrValue,
                width: 210,
                height: 210,
                colorDark: "#111111",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }
    </script>

</body>
</html>