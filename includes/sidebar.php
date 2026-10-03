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
           <span class="nav-icon">
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="lucide lucide-layout-dashboard"
    >
        <rect width="7" height="9" x="3" y="3" rx="1"/>
        <rect width="7" height="5" x="14" y="3" rx="1"/>
        <rect width="7" height="9" x="14" y="12" rx="1"/>
        <rect width="7" height="5" x="3" y="16" rx="1"/>
    </svg>
</span>

<span>Dashboard</span>
        </a>


        <!-- MEMBERS -->
        <a
            href="/onyx-gym/members/index.php"
            class="<?= $currentFolder === 'members' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="lucide lucide-user-group preview-icon"
    >
        <path d="M17 21v-1a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v1"/>
        <path d="M19 10h1a2 2 0 0 1 2 2v1"/>
        <path d="M5 10H4a2 2 0 0 0-2 2v1"/>
        <circle cx="12" cy="11" r="3"/>
        <circle cx="18" cy="4" r="2"/>
        <circle cx="6" cy="4" r="2"/>
    </svg>
</span>

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