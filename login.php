<?php

session_start();

require_once "config/database.php";


// If already logged in, go to dashboard
if (isset($_SESSION["admin_id"])) {

    header("Location: dashboard.php");
    exit;

}


$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];


    if (empty($username) || empty($password)) {

        $error = "Please enter your username and password.";

    } else {

        // Find admin account
        $sql = "SELECT id, username, password 
                FROM admins 
                WHERE username = ?";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([$username]);

        $admin = $stmt->fetch();


        // Check password
        if ($admin && password_verify($password, $admin["password"])) {

            // Create a new session ID
            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["username"] = $admin["username"];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Incorrect username or password.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | ONYX GYM</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body class="login-page">

    <div class="login-container">

        <div class="logo">
            ONYX
            <span>GYM</span>
        </div>

        <h1>Admin Login</h1>

        <p class="subtitle">
            Sign in to manage ONYX GYM.
        </p>


        <?php if (!empty($error)): ?>

            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter username"
                    required
                >

            </div>


           <div class="form-group">

    <label for="password">
        Password
    </label>

    <div class="password-wrapper">

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter password"
            required
        >

        <button
            type="button"
            class="toggle-password"
            id="togglePassword"
            aria-label="Show password"
        >
            👁
        </button>

    </div>

</div>


            <button type="submit">
                LOGIN
            </button>

        </form>


        <p class="default-account">

            Default Account<br>

            <strong>admin</strong> /
            <strong>admin123</strong>

        </p>

    </div>

</body>

</html>
<script>

    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    togglePassword.addEventListener("click", function () {

        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            togglePassword.textContent = "🙈";

            togglePassword.setAttribute(
                "aria-label",
                "Hide password"
            );

        } else {

            passwordInput.type = "password";

            togglePassword.textContent = "👁";

            togglePassword.setAttribute(
                "aria-label",
                "Show password"
            );

        }

    });

</script>