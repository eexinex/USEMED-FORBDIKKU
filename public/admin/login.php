<?php
// public/admin/login.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

if (is_logged_in() && user_role() === 'admin') {
    redirect_to('admin/dashboard.php');
}

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash_set('danger', 'กรุณากรอก Username และ Password');
        redirect_to('admin/login.php');
    }

    if (login_admin($username, $password)) {
        flash_set('success', 'เข้าสู่ระบบ Admin สำเร็จ');
        redirect_to('admin/dashboard.php');
    }

    flash_set('danger', 'Username หรือ Password ไม่ถูกต้อง');
    redirect_to('admin/login.php');
}

page_start('Admin Login', 'guest');
?>

<section class="auth-wrap">
    <div class="auth-hero">
        <div class="auth-logo">🛠️</div>

        <h1>Admin<br>Control Center</h1>

        <p>
            เข้าสู่ระบบสำหรับผู้ดูแล USE MED เพื่อตรวจสอบภาพรวมระบบ
            รายการแจ้งปัญหา และสถานะการใช้งาน
        </p>

        <div class="auth-points">
            <div>✅ ดูภาพรวมระบบ</div>
            <div>✅ ตรวจสอบผู้ใช้งาน</div>
            <div>✅ จัดการรายการ Support</div>
        </div>
    </div>

    <div class="auth-form">
        <h2>เข้าสู่ระบบ Admin</h2>
        <p class="sub">
            ใช้บัญชีผู้ดูแลระบบเพื่อเข้าสู่ Dashboard
        </p>

        <div class="demo-box">
            <strong>Demo Admin</strong><br>
            Username: <b>admin</b><br>
            Password: <b>admin123</b>
        </div>

        <form method="post">
            <div class="field">
                <label for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    required
                    autocomplete="username"
                    placeholder="กรอก Username"
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="กรอก Password"
                >
            </div>

            <button class="btn full" type="submit">
                เข้าสู่ระบบ
            </button>

            <button class="btn secondary full mt-1" type="button" data-demo-login="admin">
                ใช้บัญชี Demo
            </button>

            <div class="mini-links">
                <a href="<?= e(app_url('index.php')) ?>">กลับหน้าแรก</a>
                <a href="<?= e(app_url('patient/login.php')) ?>">Patient Login</a>
                <a href="<?= e(app_url('doctor/login.php')) ?>">Doctor Login</a>
            </div>
        </form>
    </div>
</section>

<?php
page_end();