<?php
// public/patient/login.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

if (is_logged_in() && user_role() === 'patient') {
    redirect_to('patient/portal.php');
}

if (is_post()) {
    $hn = trim($_POST['hn'] ?? $_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($hn === '' || $password === '') {
        flash_set('danger', 'กรุณากรอก HN และ Password');
        redirect_to('patient/login.php');
    }

    if (login_patient($hn, $password)) {
        flash_set('success', 'เข้าสู่ระบบผู้ป่วยสำเร็จ');
        redirect_to('patient/portal.php');
    }

    flash_set('danger', 'HN หรือ Password ไม่ถูกต้อง');
    redirect_to('patient/login.php');
}

page_start('Patient Login', 'guest');
?>

<section class="auth-wrap">
    <div class="auth-hero">
        <div class="auth-logo">🧑‍⚕️</div>

        <h1>Patient<br>Portal</h1>

        <p>
            เข้าสู่ระบบสำหรับผู้ป่วย เพื่อดูประวัติการรักษา Timeline
            เอกสารสุขภาพ ใบนัด และข้อมูลสำคัญจากโรงพยาบาล
        </p>

        <div class="auth-points">
            <div>✅ ดูประวัติการรักษา</div>
            <div>✅ เปิดเอกสารสุขภาพ</div>
            <div>✅ ติดตามนัดหมายและ Timeline</div>
        </div>
    </div>

    <div class="auth-form">
        <h2>เข้าสู่ระบบผู้ป่วย</h2>
        <p class="sub">
            ใช้เลข HN และรหัสผ่านเพื่อเข้าสู่ Patient Portal
        </p>

        <div class="demo-box">
            <strong>Demo Patient 10 คน</strong><br>
            HN: <b>HN0001</b> ถึง <b>HN0010</b><br>
            Password ทุกคน: <b>123456</b>
        </div>

        <div class="register-mini-link">
            ยังไม่มีบัญชีผู้ป่วย?
            <a href="<?= e(app_url('patient/register.php')) ?>">ลงทะเบียนผู้ป่วยใหม่ด้วยตนเอง</a>
            ระบบจะสร้าง HN ให้และบันทึกเข้าหลังบ้านจริงเมื่อเชื่อม MySQL แล้ว
        </div>

        <form method="post">
            <div class="field">
                <label for="hn">HN</label>
                <input
                    id="hn"
                    name="hn"
                    type="text"
                    required
                    autocomplete="username"
                    placeholder="กรอกเลข HN เช่น HN0001"
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
                    placeholder="กรอกรหัสผ่าน"
                >
            </div>

            <button class="btn full" type="submit">
                เข้าสู่ระบบ
            </button>

            <button class="btn secondary full mt-1" type="button" data-demo-login="patient">
                ใช้บัญชี Demo
            </button>

            <div class="mini-links">
                <a href="<?= e(app_url('index.php')) ?>">กลับหน้าแรก</a>
                <a href="<?= e(app_url('patient/register.php')) ?>">ลงทะเบียนใหม่</a>
                <a href="<?= e(app_url('doctor/login.php')) ?>">Doctor Login</a>
                <a href="<?= e(app_url('support.php')) ?>">แจ้งปัญหา</a>
            </div>
        </form>
    </div>
</section>

<?php
page_end();