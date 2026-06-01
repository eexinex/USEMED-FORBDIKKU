<?php
// public/doctor/referral.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$patients = demo_patients();
$doctors = demo_doctors();
$departments = demo_departments();
$hospitals = demo_hospitals();
$currentDoctor = current_user();
$referrals = demo_referrals();

$form = [
    'hn' => trim($_GET['hn'] ?? 'HN0001'),
    'from_department' => $currentDoctor['department'] ?? demo_doctor()['department'],
    'to_department' => 'ศัลยกรรมทั่วไป',
    'to_doctor' => 'พญ.ณิชา ศรีแพทย์',
    'to_hospital' => 'โรงพยาบาลขอนแก่น',
    'urgency' => 'ปกติ',
    'reason' => '',
];

if (db_is_connected()) {
    $dbPatients = db_fetch_all('SELECT * FROM patients ORDER BY hn ASC');
    if (!empty($dbPatients)) {
        $patients = $dbPatients;
    }

    $dbDoctors = db_fetch_all('SELECT * FROM doctors ORDER BY full_name ASC');
    if (!empty($dbDoctors)) {
        $doctors = $dbDoctors;
    }

    $dbReferrals = db_fetch_all(
        'SELECT r.*, p.hn, p.full_name AS patient_name
         FROM referrals r
         LEFT JOIN patients p ON p.id = r.patient_id
         ORDER BY r.created_at DESC, r.id DESC
         LIMIT 12'
    );
    if (!empty($dbReferrals)) {
        $referrals = $dbReferrals;
    }
}

if (is_post()) {
    $form['hn'] = trim($_POST['hn'] ?? '');
    $form['from_department'] = trim($_POST['from_department'] ?? '');
    $form['to_department'] = trim($_POST['to_department'] ?? '');
    $form['to_doctor'] = trim($_POST['to_doctor'] ?? '');
    $form['to_hospital'] = trim($_POST['to_hospital'] ?? '');
    $form['urgency'] = trim($_POST['urgency'] ?? 'ปกติ');
    $form['reason'] = trim($_POST['reason'] ?? '');

    if ($form['hn'] === '' || $form['to_department'] === '' || $form['to_hospital'] === '' || $form['reason'] === '') {
        flash_set('danger', 'กรุณากรอก HN, แผนกปลายทาง, โรงพยาบาลปลายทาง และเหตุผลการส่งต่อ');
        redirect_to('doctor/referral.php');
    }

    $selectedPatient = null;
    foreach ($patients as $p) {
        if (strcasecmp((string) ($p['hn'] ?? ''), $form['hn']) === 0) {
            $selectedPatient = $p;
            break;
        }
    }

    if (db_is_connected()) {
        $patientRow = db_fetch_one('SELECT * FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $form['hn']]);
        if (!$patientRow) {
            flash_set('danger', 'ไม่พบผู้ป่วย HN นี้ กรุณาลงทะเบียนผู้ป่วยก่อน');
            redirect_to('doctor/register-patient.php');
        }

        db_execute(
            'INSERT INTO referrals (
                patient_id, doctor_id, from_department, to_department, to_doctor,
                to_hospital, urgency, reason, status
             ) VALUES (
                :patient_id, :doctor_id, :from_department, :to_department, :to_doctor,
                :to_hospital, :urgency, :reason, :status
             )',
            [
                'patient_id' => (int) $patientRow['id'],
                'doctor_id' => (int) ($currentDoctor['id'] ?? 1),
                'from_department' => $form['from_department'],
                'to_department' => $form['to_department'],
                'to_doctor' => $form['to_doctor'],
                'to_hospital' => $form['to_hospital'],
                'urgency' => $form['urgency'],
                'reason' => $form['reason'],
                'status' => 'รอรับเคส',
            ]
        );
    } else {
        $patientName = $selectedPatient['full_name'] ?? 'Demo Patient';
        $_SESSION['demo_referrals'] = $_SESSION['demo_referrals'] ?? [];
        array_unshift($_SESSION['demo_referrals'], [
            'id' => count($_SESSION['demo_referrals']) + 100,
            'hn' => $form['hn'],
            'patient_name' => $patientName,
            'from_department' => $form['from_department'],
            'to_department' => $form['to_department'],
            'to_doctor' => $form['to_doctor'],
            'to_hospital' => $form['to_hospital'],
            'urgency' => $form['urgency'],
            'reason' => $form['reason'],
            'status' => 'รอรับเคส',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    flash_set('success', 'บันทึกใบส่งตัวเรียบร้อยแล้ว: ' . $form['hn'] . ' → ' . $form['to_department']);
    redirect_to('doctor/referral.php?hn=' . urlencode($form['hn']));
}

$selectedPatient = demo_patient($form['hn']);
foreach ($patients as $p) {
    if (strcasecmp((string) ($p['hn'] ?? ''), $form['hn']) === 0) {
        $selectedPatient = array_merge($selectedPatient, $p);
        break;
    }
}

page_start('ส่งตัว / ส่งต่อ', 'doctor', 'referral');

topbar('ส่งตัว / ส่งต่อผู้ป่วย', 'เลือก HN แผนกปลายทาง แพทย์ปลายทาง และโรงพยาบาลปลายทาง');
?>

<section class="grid grid-2">
    <div class="form-card">
        <h2>แบบฟอร์มส่งตัว</h2>
        <p class="text-muted">ใช้สำหรับส่งผู้ป่วยไปแผนกอื่น แพทย์คนอื่น หรือโรงพยาบาลปลายทาง</p>

        <form method="post" class="mt-2">
            <div class="form-grid">
                <div class="field">
                    <label for="hn">เลือกผู้ป่วย / HN</label>
                    <select id="hn" name="hn" required>
                        <?php foreach ($patients as $p): ?>
                            <?php $hn = (string) ($p['hn'] ?? ''); ?>
                            <option value="<?= e($hn) ?>" <?= $hn === $form['hn'] ? 'selected' : '' ?>>
                                <?= e($hn . ' · ' . ($p['full_name'] ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="from_department">แผนกต้นทาง</label>
                    <select id="from_department" name="from_department" required>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= e($department) ?>" <?= $department === $form['from_department'] ? 'selected' : '' ?>><?= e($department) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="to_department">แผนกปลายทาง</label>
                    <select id="to_department" name="to_department" required>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= e($department) ?>" <?= $department === $form['to_department'] ? 'selected' : '' ?>><?= e($department) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="to_doctor">แพทย์ปลายทาง</label>
                    <select id="to_doctor" name="to_doctor">
                        <?php foreach ($doctors as $doctor): ?>
                            <?php $name = (string) ($doctor['full_name'] ?? $doctor['username'] ?? '-'); ?>
                            <option value="<?= e($name) ?>" <?= $name === $form['to_doctor'] ? 'selected' : '' ?>>
                                <?= e($name . ' · ' . ($doctor['department'] ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="to_hospital">โรงพยาบาลปลายทาง</label>
                    <select id="to_hospital" name="to_hospital" required>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= e($hospital) ?>" <?= $hospital === $form['to_hospital'] ? 'selected' : '' ?>><?= e($hospital) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="urgency">ความเร่งด่วน</label>
                    <select id="urgency" name="urgency">
                        <?php foreach (['ปกติ','ด่วน','ฉุกเฉิน'] as $urgency): ?>
                            <option value="<?= e($urgency) ?>" <?= $urgency === $form['urgency'] ? 'selected' : '' ?>><?= e($urgency) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="reason">เหตุผล / ข้อมูลประกอบการส่งต่อ</label>
                <textarea id="reason" name="reason" required placeholder="เช่น ต้องประเมินโดยศัลยแพทย์ / ขอรับ ICU / ส่งต่อเพื่อผ่าตัด"></textarea>
            </div>

            <div class="btn-row mt-2">
                <button class="btn" type="submit">บันทึกใบส่งตัว</button>
                <a class="btn secondary" href="<?= e(app_url('doctor/dashboard.php#patient-flow')) ?>">กลับ Dashboard</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>ข้อมูลผู้ป่วยที่เลือก</h2>
        <div class="document-grid mt-2">
            <div class="document-card">
                <div><strong><?= e($selectedPatient['full_name'] ?? '-') ?></strong><span>HN: <?= e($selectedPatient['hn'] ?? '-') ?></span></div>
                <span class="badge <?= e(badge_class((string) ($selectedPatient['risk_level'] ?? 'Medium'))) ?>"><?= e($selectedPatient['risk_level'] ?? 'Medium') ?></span>
            </div>
            <div class="document-card"><div><strong>สถานะ</strong><span><?= e(($selectedPatient['care_area'] ?? 'OPD') . ' · ' . ($selectedPatient['ward'] ?? '-')) ?></span></div><span class="badge blue">Flow</span></div>
            <div class="document-card"><div><strong>แผนกปัจจุบัน</strong><span><?= e($selectedPatient['department'] ?? '-') ?></span></div><span class="badge green">Dept</span></div>
            <div class="document-card"><div><strong>โรงพยาบาล</strong><span><?= e($selectedPatient['hospital'] ?? '-') ?></span></div><span class="badge orange">Hospital</span></div>
        </div>

        <div class="note-box mt-2">
            ถ้าเชื่อม MySQL แล้ว รายการส่งต่อจะถูกบันทึกลงตาราง <strong>referrals</strong> จริง
        </div>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>ประวัติการส่งต่อ</h1>
            <p>รายการส่งตัวล่าสุดในระบบ</p>
        </div>
        <div class="searchbar"><input type="search" data-table-search="referralTable" placeholder="ค้นหารายการส่งต่อ..."></div>
    </div>

    <div class="table-wrap">
        <table class="table" id="referralTable">
            <thead>
                <tr>
                    <th>ผู้ป่วย</th>
                    <th>จากแผนก</th>
                    <th>ไปแผนก / แพทย์</th>
                    <th>โรงพยาบาล</th>
                    <th>ความเร่งด่วน</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referrals as $ref): ?>
                    <tr>
                        <td><strong><?= e($ref['hn'] ?? '-') ?></strong><br><span class="text-muted"><?= e($ref['patient_name'] ?? '-') ?></span></td>
                        <td><?= e($ref['from_department'] ?? '-') ?></td>
                        <td><strong><?= e($ref['to_department'] ?? '-') ?></strong><br><span class="text-muted"><?= e($ref['to_doctor'] ?? '-') ?></span></td>
                        <td><?= e($ref['to_hospital'] ?? '-') ?></td>
                        <td><span class="badge <?= e(($ref['urgency'] ?? '') === 'ฉุกเฉิน' || ($ref['urgency'] ?? '') === 'ด่วน' ? 'red' : 'blue') ?>"><?= e($ref['urgency'] ?? 'ปกติ') ?></span></td>
                        <td><?= e($ref['status'] ?? 'รอรับเคส') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php page_end();
