<?php
// public/patient/register.php

/**
 * Patient self-registration.
 * Requires MySQL connection because this page creates a real patient account.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

if (is_logged_in() && user_role() === 'patient') {
    redirect_to('patient/portal.php');
}

usemed_ensure_extended_schema();

function usemed_next_patient_hn(): string
{
    $row = db_fetch_one(
        "SELECT hn
         FROM patients
         WHERE hn REGEXP '^HN[0-9]+$'
         ORDER BY CAST(SUBSTRING(hn, 3) AS UNSIGNED) DESC
         LIMIT 1"
    );

    $last = (string) ($row['hn'] ?? 'HN0010');
    $number = (int) preg_replace('/\D+/', '', $last);
    if ($number < 10) {
        $number = 10;
    }

    return 'HN' . str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT);
}

function usemed_age_from_birth_date(string $birthDate): ?int
{
    if ($birthDate === '') {
        return null;
    }

    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime('today');
        return (int) $birth->diff($today)->y;
    } catch (Throwable $e) {
        return null;
    }
}

$form = [
    'full_name' => '',
    'id_card' => '',
    'birth_date' => '',
    'gender' => '',
    'phone' => '',
    'email' => '',
    'blood_group' => '',
    'payment_method' => '',
    'insurance_detail' => '',
    'hospital' => '',
    'disease' => '',
    'allergy_history' => '',
    'address' => '',
];

if (is_post()) {
    foreach ($form as $key => $value) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $password = trim((string) ($_POST['password'] ?? ''));
    $passwordConfirm = trim((string) ($_POST['password_confirm'] ?? ''));
    $consent = (string) ($_POST['consent'] ?? '');

    if (!db_is_connected()) {
        flash_set('danger', 'ยังไม่ได้เชื่อมต่อฐานข้อมูลจริง จึงลงทะเบียนผู้ป่วยเองไม่ได้ กรุณาตั้งค่า MySQL ก่อน');
        redirect_to('patient/register.php');
    }

    if ($form['full_name'] === '' || $form['phone'] === '' || $password === '') {
        flash_set('danger', 'กรุณากรอกชื่อ-นามสกุล เบอร์โทร และรหัสผ่าน');
        redirect_to('patient/register.php');
    }

    if (strlen($password) < 6) {
        flash_set('danger', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        redirect_to('patient/register.php');
    }

    if ($password !== $passwordConfirm) {
        flash_set('danger', 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
        redirect_to('patient/register.php');
    }

    if ($consent !== '1') {
        flash_set('danger', 'กรุณายอมรับการใช้ข้อมูลเพื่อสร้างบัญชีผู้ป่วย');
        redirect_to('patient/register.php');
    }

    if ($form['phone'] !== '') {
        $existingPhone = db_fetch_one(
            'SELECT id FROM patients WHERE phone = :phone LIMIT 1',
            ['phone' => $form['phone']]
        );

        if ($existingPhone) {
            flash_set('danger', 'เบอร์โทรนี้มีบัญชีผู้ป่วยในระบบแล้ว กรุณาเข้าสู่ระบบหรือแจ้ง Support');
            redirect_to('patient/login.php');
        }
    }

    if ($form['id_card'] !== '') {
        $existingIdCard = db_fetch_one(
            'SELECT id FROM patients WHERE id_card = :id_card LIMIT 1',
            ['id_card' => $form['id_card']]
        );

        if ($existingIdCard) {
            flash_set('danger', 'เลขบัตรประชาชนนี้มีบัญชีผู้ป่วยในระบบแล้ว');
            redirect_to('patient/login.php');
        }
    }

    $hn = usemed_next_patient_hn();
    $age = usemed_age_from_birth_date($form['birth_date']);

    $patientData = [
        'hn' => $hn,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'full_name' => $form['full_name'],
        'gender' => $form['gender'] ?: null,
        'age' => $age,
        'phone' => $form['phone'],
        'email' => $form['email'] ?: null,
        'id_card' => $form['id_card'] ?: null,
        'birth_date' => $form['birth_date'] ?: null,
        'blood_group' => $form['blood_group'] ?: null,
        'payment_method' => $form['payment_method'] ?: null,
        'insurance_detail' => $form['insurance_detail'] ?: null,
        'hospital' => $form['hospital'] ?: null,
        'disease' => $form['disease'] ?: null,
        'allergy_history' => $form['allergy_history'] ?: null,
        'address' => $form['address'] ?: null,
        'care_area' => 'OPD',
        'department' => 'ลงทะเบียนออนไลน์',
        'registration_source' => 'patient_self',
        'registration_status' => 'active',
        'consent_accepted_at' => date('Y-m-d H:i:s'),
    ];

    $saved = false;
    for ($i = 0; $i < 3; $i++) {
        if ($i > 0) {
            $hn = usemed_next_patient_hn();
            $patientData['hn'] = $hn;
        }

        if (usemed_insert_available('patients', $patientData)) {
            $saved = true;
            break;
        }
    }

    if (!$saved) {
        flash_set('danger', 'ลงทะเบียนไม่สำเร็จ กรุณาตรวจสอบฐานข้อมูลหรือลองใหม่อีกครั้ง');
        redirect_to('patient/register.php');
    }

    login_patient($hn, $password);
    flash_set('success', 'ลงทะเบียนสำเร็จ เลข HN ของคุณคือ ' . $hn);
    redirect_to('patient/portal.php');
}

page_start('ลงทะเบียนผู้ป่วย', 'guest');
?>

<section class="auth-wrap register-auth-wrap">
    <div class="auth-hero register-hero">
        <div class="auth-logo">📝</div>

        <h1>Patient<br>Register</h1>

        <p>
            ลงทะเบียนผู้ป่วยใหม่ด้วยตนเอง ระบบจะสร้างเลข HN ให้อัตโนมัติ
            แล้วเข้าสู่ Patient Portal ได้ทันทีเมื่อบันทึกสำเร็จ
        </p>

        <div class="auth-points">
            <div>✅ สร้างบัญชีผู้ป่วยจริงในฐานข้อมูล</div>
            <div>✅ ได้ HN ใหม่อัตโนมัติ</div>
            <div>✅ หมอและ Admin เห็นข้อมูลในหลังบ้าน</div>
        </div>
    </div>

    <div class="auth-form register-form-panel">
        <h2>ลงทะเบียนผู้ป่วยใหม่</h2>
        <p class="sub">
            กรอกข้อมูลพื้นฐานก่อนเข้าระบบ หลังสมัครสำเร็จให้จดเลข HN ไว้ใช้ Login ครั้งถัดไป
        </p>

        <?php if (!db_is_connected()): ?>
            <div class="note-box danger-note">
                ยังไม่ได้เชื่อม MySQL จริง จึงบันทึกผู้ป่วยใหม่ไม่ได้<br>
                ให้ตั้งค่า <strong>backend/config.local.php</strong> หรือเปิดหน้า <strong>check.php</strong> ตรวจฐานข้อมูลก่อน
            </div>
        <?php endif; ?>

        <form method="post" class="register-form">
            <div class="form-grid register-grid">
                <div class="field span-2">
                    <label for="full_name">ชื่อ-นามสกุล *</label>
                    <input id="full_name" name="full_name" type="text" value="<?= e($form['full_name']) ?>" required placeholder="เช่น กานต์พิชชา สุขใจ">
                </div>

                <div class="field">
                    <label for="id_card">เลขบัตรประชาชน</label>
                    <input id="id_card" name="id_card" type="text" value="<?= e($form['id_card']) ?>" inputmode="numeric" maxlength="13" placeholder="13 หลัก ถ้ามี">
                </div>

                <div class="field">
                    <label for="birth_date">วันเกิด</label>
                    <input id="birth_date" name="birth_date" type="date" value="<?= e($form['birth_date']) ?>">
                </div>

                <div class="field">
                    <label for="gender">เพศ</label>
                    <select id="gender" name="gender">
                        <option value="">เลือกเพศ</option>
                        <?php foreach (['ชาย', 'หญิง', 'อื่น ๆ', 'ไม่ระบุ'] as $gender): ?>
                            <option value="<?= e($gender) ?>" <?= $form['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="phone">เบอร์โทร *</label>
                    <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" required placeholder="เช่น 0812345678">
                </div>

                <div class="field">
                    <label for="email">อีเมล</label>
                    <input id="email" name="email" type="email" value="<?= e($form['email']) ?>" placeholder="example@email.com">
                </div>

                <div class="field">
                    <label for="blood_group">กรุ๊ปเลือด</label>
                    <select id="blood_group" name="blood_group">
                        <option value="">เลือกกรุ๊ปเลือด</option>
                        <?php foreach (demo_blood_groups() as $blood): ?>
                            <option value="<?= e($blood) ?>" <?= $form['blood_group'] === $blood ? 'selected' : '' ?>><?= e($blood) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="payment_method">สิทธิ/การจ่ายเงินที่ใช้รักษา</label>
                    <select id="payment_method" name="payment_method">
                        <option value="">เลือกสิทธิ</option>
                        <?php foreach (demo_payment_methods() as $method): ?>
                            <option value="<?= e($method) ?>" <?= $form['payment_method'] === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field span-2">
                    <label for="insurance_detail">รายละเอียดสิทธิ/ประกัน</label>
                    <input id="insurance_detail" name="insurance_detail" type="text" value="<?= e($form['insurance_detail']) ?>" placeholder="เช่น บริษัทประกัน / เลขกรมธรรม์ / สิทธิหลัก">
                </div>

                <div class="field span-2">
                    <label for="hospital">โรงพยาบาลที่เคยใช้บริการ/ต้องการผูกข้อมูล</label>
                    <select id="hospital" name="hospital">
                        <option value="">เลือกโรงพยาบาล</option>
                        <?php foreach (demo_hospitals() as $hospital): ?>
                            <option value="<?= e($hospital) ?>" <?= $form['hospital'] === $hospital ? 'selected' : '' ?>><?= e($hospital) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field span-2">
                    <label for="password">ตั้งรหัสผ่าน *</label>
                    <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password" placeholder="อย่างน้อย 6 ตัวอักษร">
                </div>

                <div class="field span-2">
                    <label for="password_confirm">ยืนยันรหัสผ่าน *</label>
                    <input id="password_confirm" name="password_confirm" type="password" required minlength="6" autocomplete="new-password" placeholder="กรอกรหัสผ่านซ้ำ">
                </div>

                <div class="field span-2">
                    <label for="disease">โรคประจำตัว / ปัญหาสุขภาพสำคัญ</label>
                    <textarea id="disease" name="disease" placeholder="เช่น เบาหวาน ความดัน โรคหัวใจ หรือไม่มีโรคประจำตัว"><?= e($form['disease']) ?></textarea>
                </div>

                <div class="field span-2">
                    <label for="allergy_history">ประวัติแพ้ยา/แพ้อาหาร</label>
                    <textarea id="allergy_history" name="allergy_history" placeholder="ระบุชื่อยา อาหาร หรือสารที่แพ้ ถ้าไม่มีให้ใส่ ไม่มี"><?= e($form['allergy_history']) ?></textarea>
                </div>

                <div class="field span-4">
                    <label for="address">ที่อยู่</label>
                    <textarea id="address" name="address" placeholder="ที่อยู่สำหรับติดต่อ"><?= e($form['address']) ?></textarea>
                </div>
            </div>

            <label class="consent-box">
                <input type="checkbox" name="consent" value="1" required>
                <span>ยืนยันว่าข้อมูลถูกต้อง และยินยอมให้ USE MED ใช้ข้อมูลเพื่อสร้างบัญชีผู้ป่วยและแสดงในระบบหลังบ้านของแพทย์/Admin</span>
            </label>

            <div class="btn-row mt-2">
                <button class="btn" type="submit" <?= db_is_connected() ? '' : 'disabled' ?>>
                    ลงทะเบียนและเข้าสู่ระบบ
                </button>
                <a class="btn secondary" href="<?= e(app_url('patient/login.php')) ?>">มีบัญชีแล้ว เข้าสู่ระบบ</a>
                <a class="btn secondary" href="<?= e(app_url('index.php')) ?>">กลับหน้าแรก</a>
            </div>
        </form>
    </div>
</section>

<?php page_end();
