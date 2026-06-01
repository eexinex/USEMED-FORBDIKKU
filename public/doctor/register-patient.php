<?php
// public/doctor/register-patient.php
// Step 10: reliable real DB save for doctor-created patients.

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

function doctor_register_pdo(): ?PDO
{
    try {
        $pdo = db();
        return $pdo instanceof PDO ? $pdo : null;
    } catch (Throwable $e) {
        return null;
    }
}

function doctor_register_columns(PDO $pdo, string $table = 'patients'): array
{
    try {
        $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = :tbl ORDER BY ordinal_position");
        $stmt->execute(['tbl' => $table]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cols = [];
        foreach ($rows as $row) {
            if (!empty($row['column_name'])) {
                $cols[] = (string) $row['column_name'];
            }
        }
        return $cols;
    } catch (Throwable $e) {
        return [];
    }
}

function doctor_register_has_column(PDO $pdo, string $column): bool
{
    return in_array($column, doctor_register_columns($pdo), true);
}

function doctor_register_exec_ignore(PDO $pdo, string $sql): void
{
    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        // Ignore duplicate column/index errors. The save step below will show a real error if critical.
    }
}

function doctor_register_ensure_schema(PDO $pdo): void
{
    // Create a safe patients table if schema.sql was not imported yet.
    doctor_register_exec_ignore($pdo, "CREATE TABLE IF NOT EXISTS patients (
        id SERIAL PRIMARY KEY,
        hn VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        gender VARCHAR(30) DEFAULT NULL,
        age INT DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        id_card VARCHAR(30) DEFAULT NULL,
        birth_date DATE DEFAULT NULL,
        disease VARCHAR(255) DEFAULT NULL,
        allergy_history TEXT DEFAULT NULL,
        address TEXT DEFAULT NULL,
        care_area VARCHAR(80) DEFAULT 'OPD',
        hospital VARCHAR(255) DEFAULT NULL,
        ward VARCHAR(255) DEFAULT NULL,
        department VARCHAR(255) DEFAULT NULL,
        surgery_status VARCHAR(255) DEFAULT NULL,
        high_watch SMALLINT DEFAULT 0,
        blood_group VARCHAR(20) DEFAULT NULL,
        payment_method VARCHAR(100) DEFAULT NULL,
        insurance_detail VARCHAR(255) DEFAULT NULL,
        risk_level VARCHAR(50) DEFAULT 'Low',
        risk_score INT DEFAULT 0,
        registration_source VARCHAR(80) DEFAULT 'staff',
        registration_status VARCHAR(80) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        UNIQUE (hn)
    )");

    $adds = [
        'hn' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS hn VARCHAR(50) NOT NULL DEFAULT ''",
        'password' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '123456'",
        'full_name' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) NOT NULL DEFAULT ''",
        'gender' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS gender VARCHAR(30) DEFAULT NULL",
        'age' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS age INT DEFAULT NULL",
        'phone' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL",
        'email' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL",
        'id_card' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS id_card VARCHAR(30) DEFAULT NULL",
        'birth_date' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS birth_date DATE DEFAULT NULL",
        'disease' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS disease VARCHAR(255) DEFAULT NULL",
        'allergy_history' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS allergy_history TEXT DEFAULT NULL",
        'address' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL",
        'care_area' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS care_area VARCHAR(80) DEFAULT 'OPD'",
        'hospital' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS hospital VARCHAR(255) DEFAULT NULL",
        'ward' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS ward VARCHAR(255) DEFAULT NULL",
        'department' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS department VARCHAR(255) DEFAULT NULL",
        'surgery_status' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS surgery_status VARCHAR(255) DEFAULT NULL",
        'high_watch' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS high_watch SMALLINT DEFAULT 0",
        'blood_group' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS blood_group VARCHAR(20) DEFAULT NULL",
        'payment_method' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS payment_method VARCHAR(100) DEFAULT NULL",
        'insurance_detail' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS insurance_detail VARCHAR(255) DEFAULT NULL",
        'risk_level' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS risk_level VARCHAR(50) DEFAULT 'Low'",
        'risk_score' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS risk_score INT DEFAULT 0",
        'registration_source' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS registration_source VARCHAR(80) DEFAULT 'staff'",
        'registration_status' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS registration_status VARCHAR(80) DEFAULT 'active'",
        'created_at' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NULL",
    ];

    foreach ($adds as $column => $sql) {
        doctor_register_exec_ignore($pdo, $sql);
    }

    doctor_register_exec_ignore($pdo, 'CREATE UNIQUE INDEX IF NOT EXISTS uniq_patients_hn ON patients (hn)');

    if (function_exists('usemed_ensure_extended_schema')) {
        try {
            usemed_ensure_extended_schema();
        } catch (Throwable $e) {
            // Keep this page independent; the important columns above are already ensured.
        }
    }
}

function doctor_register_next_hn(?PDO $pdo): string
{
    if (!$pdo) {
        return 'HN0011';
    }

    $max = 10;
    try {
        $stmt = $pdo->query("SELECT hn FROM patients WHERE hn LIKE 'HN%' ORDER BY id DESC LIMIT 3000");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        foreach ($rows as $hn) {
            if (preg_match('/HN(\d+)/i', (string) $hn, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
    } catch (Throwable $e) {
        $row = db_fetch_one('SELECT hn FROM patients ORDER BY id DESC LIMIT 1');
        if (!empty($row['hn']) && preg_match('/(\d+)/', (string) $row['hn'], $m)) {
            $max = max($max, (int) $m[1]);
        }
    }

    return 'HN' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
}

function doctor_register_age_from_birth_date(string $birthDate): ?int
{
    if ($birthDate === '') {
        return null;
    }
    try {
        return (int) (new DateTime($birthDate))->diff(new DateTime('today'))->y;
    } catch (Throwable $e) {
        return null;
    }
}

function doctor_register_insert(PDO $pdo, array $data, string &$error = ''): bool
{
    $columns = doctor_register_columns($pdo);
    $filtered = [];
    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    foreach (['hn', 'password', 'full_name'] as $required) {
        if (!array_key_exists($required, $filtered)) {
            $error = 'ตาราง patients ขาด column สำคัญ: ' . $required;
            return false;
        }
    }

    $names = array_keys($filtered);
    $params = array_map(static fn($name) => ':' . $name, $names);
    $sql = 'INSERT INTO patients (' . implode(',', $names) . ') VALUES (' . implode(',', $params) . ')';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($filtered);

        $verify = $pdo->prepare('SELECT id FROM patients WHERE hn = :hn LIMIT 1');
        $verify->execute(['hn' => $filtered['hn']]);
        return (bool) $verify->fetchColumn();
    } catch (Throwable $e) {
        $error = $e->getMessage();
        return false;
    }
}

$pdo = doctor_register_pdo();
$dbReady = $pdo instanceof PDO;
if ($dbReady) {
    doctor_register_ensure_schema($pdo);
}

$nextHn = doctor_register_next_hn($pdo);
$errors = [];

$form = [
    'hn' => $nextHn,
    'password' => '123456',
    'full_name' => '',
    'gender' => '',
    'birth_date' => '',
    'age' => '',
    'id_card' => '',
    'phone' => '',
    'email' => '',
    'blood_group' => '',
    'payment_method' => '',
    'insurance_detail' => '',
    'hospital' => 'โรงพยาบาลขอนแก่น',
    'care_area' => 'OPD',
    'department' => '',
    'ward' => '',
    'disease' => '',
    'allergy_history' => '',
    'address' => '',
    'high_watch' => '0',
];

$hospitals = [
    'โรงพยาบาลขอนแก่น',
    'โรงพยาบาลศรีนครินทร์',
    'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร',
    'โรงพยาบาลราชวิถี',
    'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย',
];
$careAreas = ['OPD', 'IPD', 'ICU', 'ผ่าตัด', 'คิวผ่าตัด', 'คนไข้เฝ้าระวังสูง'];
$paymentMethods = ['เงินสด', 'ประกันส่วนตัว', 'บัตร 30 บาท / UC', 'ประกันสังคม', 'ราชการ', 'รัฐวิสาหกิจ'];
$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'ไม่ทราบ'];
$genders = ['ชาย', 'หญิง', 'อื่น ๆ', 'ไม่ระบุ'];

if (is_post()) {
    foreach ($form as $key => $value) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $form['hn'] = strtoupper($form['hn'] !== '' ? $form['hn'] : doctor_register_next_hn($pdo));
    $form['password'] = $form['password'] !== '' ? $form['password'] : '123456';

    if (!$dbReady || !$pdo) {
        $errors[] = 'Database ยังไม่เชื่อม จึงบันทึกผู้ป่วยใหม่จริงไม่ได้ ให้เช็ก backend/config.php หรือหน้า check.php ก่อน';
    }
    if ($form['full_name'] === '') {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุลผู้ป่วย';
    }
    if (strlen($form['password']) < 6) {
        $errors[] = 'รหัสผ่านเริ่มต้นต้องมีอย่างน้อย 6 ตัวอักษร';
    }

    if ($pdo && empty($errors)) {
        try {
            $check = $pdo->prepare('SELECT id FROM patients WHERE hn = :hn LIMIT 1');
            $check->execute(['hn' => $form['hn']]);
            if ($check->fetchColumn()) {
                $errors[] = 'HN ' . $form['hn'] . ' มีอยู่แล้ว ให้ใช้ HN ถัดไป: ' . doctor_register_next_hn($pdo);
            }
        } catch (Throwable $e) {
            $errors[] = 'ตรวจ HN ซ้ำไม่ได้: ' . $e->getMessage();
        }
    }

    if ($pdo && empty($errors) && $form['id_card'] !== '' && doctor_register_has_column($pdo, 'id_card')) {
        try {
            $check = $pdo->prepare('SELECT id FROM patients WHERE id_card = :id_card LIMIT 1');
            $check->execute(['id_card' => $form['id_card']]);
            if ($check->fetchColumn()) {
                $errors[] = 'เลขบัตรประชาชนนี้มีผู้ป่วยในระบบแล้ว';
            }
        } catch (Throwable $e) {
            // id_card may not be indexed; not critical.
        }
    }

    if ($pdo && empty($errors)) {
        $age = $form['age'] !== '' ? (int) $form['age'] : doctor_register_age_from_birth_date($form['birth_date']);
        $highWatch = $form['high_watch'] === '1' || $form['care_area'] === 'คนไข้เฝ้าระวังสูง' || $form['care_area'] === 'ICU';

        $patientData = [
            'hn' => $form['hn'],
            'password' => password_hash($form['password'], PASSWORD_DEFAULT),
            'full_name' => $form['full_name'],
            'gender' => $form['gender'] ?: null,
            'age' => $age,
            'birth_date' => $form['birth_date'] ?: null,
            'id_card' => $form['id_card'] ?: null,
            'phone' => $form['phone'] ?: null,
            'email' => $form['email'] ?: null,
            'blood_group' => $form['blood_group'] ?: null,
            'payment_method' => $form['payment_method'] ?: null,
            'insurance_detail' => $form['insurance_detail'] ?: null,
            'hospital' => $form['hospital'] ?: null,
            'care_area' => $form['care_area'] ?: 'OPD',
            'department' => $form['department'] ?: null,
            'ward' => $form['ward'] ?: null,
            'disease' => $form['disease'] ?: null,
            'allergy_history' => $form['allergy_history'] ?: null,
            'address' => $form['address'] ?: null,
            'high_watch' => $highWatch ? 1 : 0,
            'risk_level' => $highWatch ? 'High' : 'Low',
            'risk_score' => $highWatch ? 75 : 20,
            'registration_source' => 'staff',
            'registration_status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $insertError = '';
        if (doctor_register_insert($pdo, $patientData, $insertError)) {
            flash_set('success', 'บันทึกผู้ป่วยใหม่สำเร็จ: ' . $form['full_name'] . ' (' . $form['hn'] . ')');
            redirect_to('doctor/patient-profile.php?hn=' . urlencode($form['hn']));
        }

        $errors[] = 'บันทึกผู้ป่วยไม่สำเร็จ: ' . ($insertError !== '' ? $insertError : 'ไม่ทราบสาเหตุ');
    }
}

page_start('ลงทะเบียนผู้ป่วยใหม่', 'doctor', 'patient');

topbar('Register Patient', 'ลงทะเบียนผู้ป่วยใหม่เข้าฐานข้อมูลจริง');
?>

<section class="stat-grid">
    <?php stat_card('HN ถัดไป', $nextHn, 'Auto'); ?>
    <?php stat_card('สถานะ Database', $dbReady ? 'พร้อมบันทึก' : 'ยังไม่เชื่อม', $dbReady ? 'CONNECTED' : 'NOT CONNECTED'); ?>
    <?php stat_card('หลังบันทึก', 'ไปหน้า Profile', 'Verify immediately'); ?>
</section>

<?php if (!$dbReady): ?>
    <section class="card mt-2">
        <h2>Database ยังไม่พร้อม</h2>
        <p class="text-muted">หน้านี้บันทึกจริงลง MySQL เท่านั้น ตอนนี้ระบบยังต่อ DB ไม่ได้ ให้เปิดหน้า <a href="<?= e(app_url('check.php')) ?>">ตรวจระบบ</a> เพื่อตรวจค่า DB_HOST / DB_NAME / DB_USER / DB_PASS</p>
    </section>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <section class="card mt-2" style="border:1px solid rgba(220,38,38,.25); background:#fff7f7;">
        <h2>บันทึกไม่สำเร็จ</h2>
        <ul class="text-muted">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="grid grid-2 mt-2">
    <div class="form-card span-2">
        <h2>ข้อมูลผู้ป่วยใหม่</h2>
        <p class="text-muted">กรอกข้อมูลแล้วกดบันทึก ระบบจะ insert ลงตาราง <strong>patients</strong> และตรวจซ้ำทันทีว่า HN ถูกบันทึกแล้ว</p>

        <form method="post" class="compact-form mt-2">
            <div class="form-grid compact-grid">
                <div class="field">
                    <label for="hn">HN ผู้ป่วย *</label>
                    <input id="hn" name="hn" type="text" value="<?= e($form['hn']) ?>" required>
                    <small>ระบบแนะนำ HN ถัดไปให้อัตโนมัติ</small>
                </div>

                <div class="field">
                    <label for="password">รหัสผ่านเริ่มต้น *</label>
                    <input id="password" name="password" type="text" value="<?= e($form['password']) ?>" required>
                </div>

                <div class="field span-2">
                    <label for="full_name">ชื่อ-นามสกุล *</label>
                    <input id="full_name" name="full_name" type="text" value="<?= e($form['full_name']) ?>" required placeholder="เช่น กานต์พิชชา สุขใจ">
                </div>

                <div class="field">
                    <label for="gender">เพศ</label>
                    <select id="gender" name="gender">
                        <option value="">เลือกเพศ</option>
                        <?php foreach ($genders as $gender): ?>
                            <option value="<?= e($gender) ?>" <?= $form['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="birth_date">วันเกิด</label>
                    <input id="birth_date" name="birth_date" type="date" value="<?= e($form['birth_date']) ?>">
                </div>

                <div class="field">
                    <label for="age">อายุ</label>
                    <input id="age" name="age" type="number" min="0" max="130" value="<?= e($form['age']) ?>" placeholder="ถ้าไม่ใส่ ระบบคำนวณจากวันเกิด">
                </div>

                <div class="field">
                    <label for="id_card">เลขบัตรประชาชน</label>
                    <input id="id_card" name="id_card" type="text" inputmode="numeric" maxlength="13" value="<?= e($form['id_card']) ?>">
                </div>

                <div class="field">
                    <label for="phone">เบอร์โทร</label>
                    <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" placeholder="เช่น 0812345678">
                </div>

                <div class="field">
                    <label for="email">อีเมล</label>
                    <input id="email" name="email" type="email" value="<?= e($form['email']) ?>">
                </div>

                <div class="field">
                    <label for="blood_group">กรุ๊ปเลือด</label>
                    <select id="blood_group" name="blood_group">
                        <option value="">เลือกกรุ๊ปเลือด</option>
                        <?php foreach ($bloodGroups as $bloodGroup): ?>
                            <option value="<?= e($bloodGroup) ?>" <?= $form['blood_group'] === $bloodGroup ? 'selected' : '' ?>><?= e($bloodGroup) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="payment_method">สิทธิ/วิธีจ่าย</label>
                    <select id="payment_method" name="payment_method">
                        <option value="">เลือกสิทธิ</option>
                        <?php foreach ($paymentMethods as $method): ?>
                            <option value="<?= e($method) ?>" <?= $form['payment_method'] === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field span-2">
                    <label for="insurance_detail">รายละเอียดสิทธิ/ประกัน</label>
                    <input id="insurance_detail" name="insurance_detail" type="text" value="<?= e($form['insurance_detail']) ?>" placeholder="เช่น UC รพ.ขอนแก่น / AIA / ประกันสังคม รพ.ศรีนครินทร์">
                </div>

                <div class="field">
                    <label for="hospital">โรงพยาบาล</label>
                    <select id="hospital" name="hospital">
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= e($hospital) ?>" <?= $form['hospital'] === $hospital ? 'selected' : '' ?>><?= e($hospital) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="care_area">ประเภทผู้ป่วย</label>
                    <select id="care_area" name="care_area">
                        <?php foreach ($careAreas as $area): ?>
                            <option value="<?= e($area) ?>" <?= $form['care_area'] === $area ? 'selected' : '' ?>><?= e($area) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="department">แผนก</label>
                    <input id="department" name="department" type="text" value="<?= e($form['department']) ?>" placeholder="เช่น อายุรกรรม / ศัลยกรรม">
                </div>

                <div class="field">
                    <label for="ward">Ward / Clinic</label>
                    <input id="ward" name="ward" type="text" value="<?= e($form['ward']) ?>" placeholder="เช่น 5A / OPD เบาหวาน">
                </div>

                <div class="field">
                    <label for="high_watch">เฝ้าระวังสูง</label>
                    <select id="high_watch" name="high_watch">
                        <option value="0" <?= $form['high_watch'] !== '1' ? 'selected' : '' ?>>ไม่ใช่</option>
                        <option value="1" <?= $form['high_watch'] === '1' ? 'selected' : '' ?>>ใช่</option>
                    </select>
                </div>

                <div class="field span-2">
                    <label for="disease">โรคประจำตัว / ปัญหาสำคัญ</label>
                    <input id="disease" name="disease" type="text" value="<?= e($form['disease']) ?>" placeholder="เช่น Type 2 Diabetes Mellitus, Hypertension">
                </div>

                <div class="field span-2">
                    <label for="allergy_history">ประวัติแพ้ยา/อาหาร</label>
                    <textarea id="allergy_history" name="allergy_history" rows="2" placeholder="เช่น แพ้ Penicillin / ไม่มีประวัติแพ้ยา"><?= e($form['allergy_history']) ?></textarea>
                </div>

                <div class="field span-2">
                    <label for="address">ที่อยู่</label>
                    <textarea id="address" name="address" rows="2" placeholder="กรอกที่อยู่ผู้ป่วย"><?= e($form['address']) ?></textarea>
                </div>
            </div>

            <div class="btn-row mt-2">
                <button class="btn" type="submit">บันทึกผู้ป่วยใหม่</button>
                <a class="btn secondary" href="<?= e(app_url('doctor/dashboard.php')) ?>">กลับ Dashboard</a>
                <a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php')) ?>">ค้นหาผู้ป่วย</a>
            </div>
        </form>
    </div>
</section>

<?php
page_end();
