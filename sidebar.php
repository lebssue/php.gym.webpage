<?php
$currentPage = basename($_SERVER["PHP_SELF"]);
$currentFolder = basename(dirname($_SERVER["PHP_SELF"]));
?>

<aside class="sidebar">

    <!-- LOGO -->
    <div class="sidebar-logo">
        <h2>ONYX</h2>
        <span>GYM MANAGEMENT</span>
    </div>


    <!-- NAVIGATION -->
    <nav class="sidebar-nav">

        <!-- DASHBOARD -->
        <a
            href="/onyx-gym/dashboard.php"
            class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>


        <!-- MEMBERS -->
        <a
            href="/onyx-gym/members/index.php"
            class="<?= $currentFolder === 'members' ? 'active' : '' ?>"
        >
            <span class="nav-icon">👤</span>
            <span>Members</span>
        </a>


        <!-- ATTENDANCE -->
        <a
            href="/onyx-gym/attendance/index.php"
            class="<?= $currentFolder === 'attendance' ? 'active' : '' ?>"
        >
            <span class="nav-icon">✓</span>
            <span>Attendance</span>
        </a>

        <!-- PAYMENT REPORTS -->
        <a
    href="/onyx-gym/payments/index.php"
    class="<?= $currentFolder === 'payments' ? 'active' : '' ?>"
>
    <span class="nav-icon">₱</span>
    <span>Payments</span>
</a>

    </nav>


    <!-- BOTTOM -->
    <div class="sidebar-bottom">

        <a
            href="/onyx-gym/logout.php"
            class="logout-link"
        >
            <span class="nav-icon">↪</span>
            <span>Logout</span>
        </a>

    </div>

</aside>