<?php
// public/support.php

declare(strict_types=1);

require_once __DIR__ . '/../backend/shared/layout.php';

$user = current_user();

$role = $user['role'] ?? 'guest';
$name = $user['name'] ?? '';

if (is_post()) {
    $userRole = trim($_POST['user_role'] ?? $role);
    $userName = trim($_POST['user_name'] ?? $name);
    $subject = trim($_POST['subject'] ?? '');
    $problemType = trim($_POST['problem_type'] ?? 'ทั่วไป');
    $menuPath = trim($_POST['menu_path'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        flash_set('danger', 'กรุณากรอกหัวข้อและรายละเอียดปัญหา');
        redirect_to('support.php');
    }

    db_execute(
        'INSERT INTO support_tickets (user_role, user_name, subject, problem_type, menu_path, message, status)
         VALUES (:user_role, :user_name, :subject, :problem_type, :menu_path, :message, :status)',
        [
            'user_role' => $userRole,
            'user_name' => $userName,
            'subject' => $subject,
            'problem_type' => $problemType,
            'menu_path' => $menuPath,
            'message' => $message,
            'status' => 'open',
        ]
    );

    flash_set('success', 'ส่งเรื่องแจ้งปัญหาเรียบร้อยแล้ว');
    redirect_to('support.php');
}

page_start('แจ้งปัญหา', $role === 'guest' ? 'guest' : $role, 'support');
?>

<?php if ($role === 'guest'): ?>
<section class="auth-wrap">
    <div class="auth-hero">
        <div class="auth-logo">🛟</div>
        <h1>USE MED Support</h1>
        <p>
            แจ้งปัญหาการใช้งานระบบ หรือส่งข้อความถึงทีมดูแลระบบ
            สำหรับผู้ป่วย แพทย์ และผู้ดูแลระบบ
        </p>

        <div class="auth-points">
            <div>✅ แจ้งปัญหาการเข้าสู่ระบบ</div>
            <div>✅ แจ้งเอกสารเปิดไม่ได้</div>
            <div>✅ แจ้งข้อมูลสุขภาพไม่ถูกต้อง</div>
        </div>
    </div>

    <div class="auth-form">
        <h2>แจ้งปัญหา</h2>
        <p class="sub">กรอกข้อมูลด้านล่างเพื่อส่งเรื่องให้ทีมดูแลระบบ</p>

        <form method="post">
            <div class="field">
                <label for="user_role">ประเภทผู้ใช้งาน</label>
                <select id="user_role" name="user_role">
                    <option value="guest">ผู้ใช้งานทั่วไป</option>
                    <option value="patient">ผู้ป่วย</option>
                    <option value="doctor">แพทย์</option>
                    <option value="admin">ผู้ดูแลระบบ</option>
                </select>
            </div>

            <div class="field">
                <label for="user_name">ชื่อผู้แจ้ง</label>
                <input id="user_name" name="user_name" type="text" placeholder="กรอกชื่อผู้แจ้ง">
            </div>

            <div class="field">
                <label for="subject">หัวข้อปัญหา</label>
                <input id="subject" name="subject" type="text" required placeholder="เช่น เปิดเอกสารไม่ได้">
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="problem_type">ประเภทปัญหา</label>
                    <select id="problem_type" name="problem_type">
                        <option value="เข้าเมนูไม่ได้">เข้าเมนูไม่ได้</option>
                        <option value="Login">Login</option>
                        <option value="เอกสาร">เอกสาร</option>
                        <option value="ข้อมูลไม่ถูกต้อง">ข้อมูลไม่ถูกต้อง</option>
                        <option value="อื่น ๆ">อื่น ๆ</option>
                    </select>
                </div>
                <div class="field">
                    <label for="menu_path">เมนู/ลิงก์ที่เข้าไม่ได้</label>
                    <input id="menu_path" name="menu_path" type="text" placeholder="เช่น doctor/documents.php หรือ Patient Timeline">
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="problem_type">ประเภทปัญหา</label>
                    <select id="problem_type" name="problem_type">
                        <option value="เข้าเมนูไม่ได้">เข้าเมนูไม่ได้</option>
                        <option value="Login">Login</option>
                        <option value="เอกสาร">เอกสาร</option>
                        <option value="ข้อมูลไม่ถูกต้อง">ข้อมูลไม่ถูกต้อง</option>
                        <option value="อื่น ๆ">อื่น ๆ</option>
                    </select>
                </div>
                <div class="field">
                    <label for="menu_path">เมนู/ลิงก์ที่เข้าไม่ได้</label>
                    <input id="menu_path" name="menu_path" type="text" placeholder="เช่น doctor/documents.php หรือ Patient Timeline">
                </div>
            </div>

            <div class="field">
                <label for="message">รายละเอียด</label>
                <textarea id="message" name="message" required placeholder="อธิบายปัญหาที่พบ"></textarea>
            </div>

            <button class="btn full" type="submit">ส่งเรื่องแจ้งปัญหา</button>

            <div class="mini-links">
                <a href="<?= e(app_url('index.php')) ?>">กลับหน้าแรก</a>
                <a href="<?= e(app_url('patient/login.php')) ?>">เข้าสู่ระบบผู้ป่วย</a>
                <a href="<?= e(app_url('doctor/login.php')) ?>">เข้าสู่ระบบแพทย์</a>
            </div>
        </form>
    </div>
</section>
<?php else: ?>

<?php
topbar('แจ้งปัญหา', 'ส่งเรื่องให้ทีมดูแลระบบ USE MED');
?>

<section class="grid grid-2">
    <div class="form-card">
        <h2>ฟอร์มแจ้งปัญหา</h2>
        <p class="text-muted">
            ระบบจะบันทึกรายการแจ้งปัญหาไว้ให้ Admin ตรวจสอบ
        </p>

        <form method="post" class="mt-2">
            <input type="hidden" name="user_role" value="<?= e($role) ?>">

            <div class="field">
                <label for="user_name">ชื่อผู้แจ้ง</label>
                <input id="user_name" name="user_name" type="text" value="<?= e($name) ?>">
            </div>

            <div class="field">
                <label for="subject">หัวข้อปัญหา</label>
                <input id="subject" name="subject" type="text" required placeholder="เช่น วันนัดไม่ตรงกับเอกสาร">
            </div>

            <div class="field">
                <label for="message">รายละเอียด</label>
                <textarea id="message" name="message" required placeholder="อธิบายปัญหาที่พบ"></textarea>
            </div>

            <button class="btn" type="submit">ส่งเรื่อง</button>
        </form>
    </div>

    <div class="card">
        <h2>ตัวอย่างเรื่องที่แจ้งได้</h2>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>เข้าสู่ระบบไม่ได้</strong>
                    <span>รหัสผ่านไม่ถูกต้อง หรือเลข HN ใช้งานไม่ได้</span>
                </div>
                <span class="badge orange">Login</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>เอกสารเปิดไม่ได้</strong>
                    <span>ผลตรวจ ใบนัด หรือสรุปการรักษาไม่แสดง</span>
                </div>
                <span class="badge blue">Document</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>ข้อมูลไม่ถูกต้อง</strong>
                    <span>ประวัติการรักษาหรือข้อมูลส่วนตัวไม่ตรง</span>
                </div>
                <span class="badge red">Data</span>
            </div>
        </div>
    </div>
</section>

<?php endif; ?>

<?php
page_end();