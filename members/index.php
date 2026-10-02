<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


// Get members
$stmt = $pdo->query("
    SELECT *
    FROM members
    ORDER BY date_registered DESC
");

$members = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Members | ONYX GYM</title>

    <link rel="stylesheet" href="/onyx-gym/css/style.css">

</head>


<body>


<?php require_once "../includes/sidebar.php"; ?>


<main class="main-content">


    <!-- PAGE HEADER -->

    <div class="top-header">

        <div>

            <h1>Members</h1>

            <p>
                Manage registered gym members.
            </p>

        </div>

        <div class="header-brand">
            ONYX GYM
        </div>

    </div>


    <!-- ACTION BAR -->

    <div class="member-toolbar">


        <form method="GET" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search members..."
            >

            <button type="submit">
                Search
            </button>

        </form>


        <a
            href="add.php"
            class="primary-button"
        >
            + Add Member
        </a>


    </div>


    <!-- MEMBER TABLE -->

    <section class="dashboard-section">


        <div class="section-header">

            <h2>Member List</h2>

            <p>
                <?= count($members) ?> member(s) registered.
            </p>

        </div>


        <div class="table-container">


            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Member</th>

                        <th>Age</th>

                        <th>Gender</th>

                        <th>Contact</th>

                        <th>Membership</th>

                        <th>Expiration</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($members) > 0): ?>


                    <?php foreach ($members as $member): ?>


                        <?php

                        $status =
                            $member["expiration_date"] >= date("Y-m-d")
                            ? "ACTIVE"
                            : "EXPIRED";

                        ?>


                        <tr>


                            <td>
                                #<?= htmlspecialchars($member["id"]) ?>
                            </td>


                            <td>

                                <strong>
                                    <?= htmlspecialchars($member["full_name"]) ?>
                                </strong>

                                <small>
                                    Registered
                                </small>

                            </td>


                            <td>
                                <?= htmlspecialchars($member["age"]) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($member["gender"]) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($member["contact"]) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($member["membership_plan"]) ?>
                            </td>


                            <td>
                                <?= date(
                                    "M d, Y",
                                    strtotime($member["expiration_date"])
                                ) ?>
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

                                <div class="action-buttons">


                                    <a
                                        href="edit.php?id=<?= $member["id"] ?>"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        method="POST"
                                        action="delete.php"
                                        onsubmit="return confirm('Delete this member?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $member["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-button"
                                        >
                                            Delete
                                        </button>

                                    </form>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="9"
                            class="empty-table"
                        >

                            No members registered yet.

                            <br><br>

                            <a
                                href="add.php"
                                class="primary-button"
                            >
                                + Add Your First Member
                            </a>

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