<?php
// public/doctor/login.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

if (is_logged_in() && user_role() === 'doctor') {
    redirect_to('doctor/dashboard.php');
}

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash_set('danger', 'กรุณากรอก Username และ Password');
        redirect_to('doctor/login.php');
    }

    if (login_doctor($username, $password)) {
        flash_set('success', 'เข้าสู่ระบบแพทย์สำเร็จ');
        redirect_to('doctor/dashboard.php');
    }

    flash_set('danger', 'Username หรือ Password ไม่ถูกต้อง');
    redirect_to('doctor/login.php');
}

page_start('Doctor Login', 'guest');
?>

<section class="auth-wrap">
    <div class="auth-hero">
        <div class="auth-logo">👨‍⚕️</div>

        <h1>Doctor<br>Portal</h1>

        <p>
            เข้าสู่ระบบสำหรับแพทย์ เพื่อดูข้อมูลผู้ป่วย ประวัติการรักษา
            เอกสารสุขภาพ และประเมินความเสี่ยงด้วย AI Risk Engine
        </p>

        <div class="auth-points">
            <div>✅ ดูข้อมูลผู้ป่วย</div>
            <div>✅ เพิ่มประวัติการรักษา</div>
            <div>✅ ประเมิน AI Risk</div>
        </div>
    </div>

    <div class="auth-form">
        <h2>เข้าสู่ระบบแพทย์</h2>
        <p class="sub">
            ใช้บัญชีแพทย์เพื่อเข้าสู่ Dashboard
        </p>

        <div class="demo-box">
            <strong>Demo Doctor 3 คน</strong><br>
            Username: <b>doctor1</b>, <b>doctor2</b>, <b>doctor3</b><br>
            Password ทุกคน: <b>123456</b>
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

            <button class="btn secondary full mt-1" type="button" data-demo-login="doctor">
                ใช้บัญชี Demo
            </button>

            <div class="mini-links">
                <a href="<?= e(app_url('index.php')) ?>">กลับหน้าแรก</a>
                <a href="<?= e(app_url('patient/login.php')) ?>">Patient Login</a>
                <a href="<?= e(app_url('admin/login.php')) ?>">Admin Login</a>
            </div>
        </form>
    </div>
</section>

<?php
page_end();