<?php
// public/patient/self-assessment.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('patient');
usemed_ensure_extended_schema();

$user = current_user();
$patient = demo_patient($user['hn'] ?? 'HN0001');
if (db_is_connected()) {
    $row = db_fetch_one('SELECT * FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $user['hn'] ?? 'HN0001']);
    if ($row) {
        $patient = array_merge($patient, $row);
    }
}

$form = [
    'systolic' => $_POST['systolic'] ?? '',
    'diastolic' => $_POST['diastolic'] ?? '',
    'fasting_glucose' => $_POST['fasting_glucose'] ?? '',
    'hba1c' => $_POST['hba1c'] ?? '',
    'weight_kg' => $_POST['weight_kg'] ?? '',
    'height_cm' => $_POST['height_cm'] ?? '',
    'medication_adherence' => $_POST['medication_adherence'] ?? 'สม่ำเสมอ',
    'symptoms' => $_POST['symptoms'] ?? [],
];

$result = null;
if (is_post()) {
    $result = usemed_chronic_self_assessment($form);
    $adviceText = implode("\n", $result['advice']);
    $symptomsText = implode(', ', (array) $form['symptoms']);

    if (db_is_connected()) {
        db_execute(
            'INSERT INTO patient_self_assessments
                (patient_id, hn, systolic, diastolic, fasting_glucose, hba1c, weight_kg, height_cm, bmi, symptoms, medication_adherence, risk_score, risk_level, advice)
             VALUES
                (:patient_id, :hn, :systolic, :diastolic, :fasting_glucose, :hba1c, :weight_kg, :height_cm, :bmi, :symptoms, :medication_adherence, :risk_score, :risk_level, :advice)',
            [
                'patient_id' => $patient['id'] ?? null,
                'hn' => $patient['hn'] ?? ($user['hn'] ?? null),
                'systolic' => $form['systolic'] !== '' ? (int) $form['systolic'] : null,
                'diastolic' => $form['diastolic'] !== '' ? (int) $form['diastolic'] : null,
                'fasting_glucose' => $form['fasting_glucose'] !== '' ? (float) $form['fasting_glucose'] : null,
                'hba1c' => $form['hba1c'] !== '' ? (float) $form['hba1c'] : null,
                'weight_kg' => $form['weight_kg'] !== '' ? (float) $form['weight_kg'] : null,
                'height_cm' => $form['height_cm'] !== '' ? (float) $form['height_cm'] : null,
                'bmi' => $result['bmi'] ?: null,
                'symptoms' => $symptomsText,
                'medication_adherence' => $form['medication_adherence'],
                'risk_score' => $result['score'],
                'risk_level' => $result['level'],
                'advice' => $adviceText,
            ]
        );
    } else {
        $_SESSION['self_assessments'][] = [
            'created_at' => date('Y-m-d H:i:s'),
            'risk_score' => $result['score'],
            'risk_level' => $result['level'],
            'advice' => $adviceText,
        ];
    }
}

$history = [];
if (db_is_connected()) {
    $history = db_fetch_all(
        'SELECT * FROM patient_self_assessments WHERE hn = :hn ORDER BY created_at DESC, id DESC LIMIT 5',
        ['hn' => $patient['hn'] ?? ($user['hn'] ?? 'HN0001')]
    );
} else {
    $history = array_reverse($_SESSION['self_assessments'] ?? []);
}

page_start('ประเมินสุขภาพ', 'patient', 'self_assessment');

topbar('ประเมินเบาหวาน/ความดัน', 'กรอกข้อมูลล่าสุดเพื่อรับคำแนะนำส่วนตัว ข้อมูลนี้เก็บเป็นประวัติของผู้ป่วยและไม่ส่งเข้า Queue แพทย์อัตโนมัติ');
?>

<section class="patient-health-hero premium-self-hero">
    <div>
        <span class="badge blue">Personal Health Check</span>
        <h2>ประเมินสุขภาพด้วยตัวเอง</h2>
        <p>กรอกค่าล่าสุดเพื่อดูระดับความเสี่ยง คำแนะนำ และสัญญาณที่ควรเฝ้าระวัง ผลนี้เป็นข้อมูลประกอบการดูแลตนเอง ไม่ใช่การวินิจฉัยแทนแพทย์</p>
    </div>
    <div class="patient-mini-profile">
        <strong><?= e($patient['full_name'] ?? '-') ?></strong>
        <span><?= e($patient['hn'] ?? '-') ?> · <?= e($patient['age'] ?? '-') ?> ปี</span>
        <span><?= e($patient['disease'] ?? 'ยังไม่มีข้อมูลโรคประจำตัว') ?></span>
    </div>
</section>

<section class="patient-assessment-layout premium-self-layout">
    <div class="form-card patient-form-card">
        <h2>กรอกค่าล่าสุด</h2><div class="patient-form-note">ใส่ค่าที่มีจริง ระบบจะคำนวณจากข้อมูลที่กรอกและบันทึกไว้ดูย้อนหลัง</div>
        <p class="text-muted">ใส่เฉพาะค่าที่มี ระบบยังประเมินได้แม้ไม่มีทุกช่อง</p>

        <form method="post" class="mt-2">
            <div class="patient-form-section">
                <h3><span>01</span> ความดันและน้ำตาล</h3>
                <div class="form-grid compact-form patient-compact-form">
                    <div class="field"><label>ความดันตัวบน</label><input type="number" name="systolic" value="<?= e($form['systolic']) ?>" placeholder="เช่น 135" required></div>
                    <div class="field"><label>ความดันตัวล่าง</label><input type="number" name="diastolic" value="<?= e($form['diastolic']) ?>" placeholder="เช่น 85" required></div>
                    <div class="field"><label>น้ำตาลอดอาหาร/FBS</label><input type="number" step="0.01" name="fasting_glucose" value="<?= e($form['fasting_glucose']) ?>" placeholder="mg/dL"></div>
                    <div class="field"><label>HbA1c</label><input type="number" step="0.01" name="hba1c" value="<?= e($form['hba1c']) ?>" placeholder="%"></div>
                </div>
            </div>

            <div class="patient-form-section">
                <h3><span>02</span> ร่างกายและการกินยา</h3>
                <div class="form-grid compact-form patient-compact-form">
                    <div class="field"><label>น้ำหนัก</label><input type="number" step="0.01" name="weight_kg" value="<?= e($form['weight_kg']) ?>" placeholder="kg"></div>
                    <div class="field"><label>ส่วนสูง</label><input type="number" step="0.01" name="height_cm" value="<?= e($form['height_cm']) ?>" placeholder="cm"></div>
                    <div class="field span-2"><label>การกินยา</label>
                        <select name="medication_adherence">
                            <?php foreach (['สม่ำเสมอ','ลืมบางครั้ง','ไม่สม่ำเสมอ','หยุดยาเอง/มีผลข้างเคียง'] as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= $form['medication_adherence'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="patient-form-section">
                <h3><span>03</span> อาการตอนนี้</h3>
                <div class="check-grid patient-check-grid">
                    <?php foreach (['ไม่มีอาการ','เวียนหัว','ปวดศีรษะ','เจ็บหน้าอก','หอบเหนื่อย','ปัสสาวะบ่อย','ชาปลายมือปลายเท้า','ซึม/อ่อนแรง'] as $symptom): ?>
                        <label class="check-pill"><input type="checkbox" name="symptoms[]" value="<?= e($symptom) ?>" <?= in_array($symptom, (array) $form['symptoms'], true) ? 'checked' : '' ?>> <span><?= e($symptom) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="patient-sticky-actions">
                <button class="btn" type="submit">ประเมินและขอคำแนะนำ</button>
                <a class="btn secondary" href="<?= e(app_url('patient/portal.php')) ?>">กลับหน้าหลัก</a>
            </div>
        </form>
    </div>

    <aside class="risk-card patient-result-card">
        <h2>ผลประเมิน</h2>
        <?php if (!$result): ?>
            <div class="empty-state patient-empty">
                <div class="empty-icon">♥</div>
                <h3>พร้อมประเมิน</h3>
                <p>กรอกข้อมูลด้านซ้าย แล้วระบบจะสรุปคะแนน เหตุผล และคำแนะนำให้อ่านแบบสั้นชัดเจน</p>
            </div>
        <?php else: ?>
            <div class="patient-result-summary">
                <div>
                    <span class="badge <?= e($result['color']) ?>">ความเสี่ยง <?= e($result['level']) ?></span>
                    <h2><?= e($result['score']) ?>/100</h2>
                    <p><?= e($result['summary']) ?></p>
                </div>
                <div class="score-circle compact" style="--value:<?= e($result['score']) ?>"><strong><?= e($result['score']) ?></strong></div>
            </div>
            <div class="riskbar mt-2"><span style="width:<?= e($result['score']) ?>%"></span></div>

            <div class="patient-result-block">
                <h3>ระบบพบอะไรบ้าง</h3>
                <ul class="factor-list compact"><?php foreach ($result['factors'] as $factor): ?><li><?= e($factor) ?></li><?php endforeach; ?></ul>
            </div>
            <div class="patient-result-block">
                <h3>คำแนะนำเบื้องต้น</h3>
                <div class="patient-advice-list"><?php foreach ($result['advice'] as $advice): ?><div><span>✓</span><p><?= e($advice) ?></p></div><?php endforeach; ?></div>
            </div>
        <?php endif; ?>
    </aside>
</section>

<section class="table-card mt-2 patient-history-card">
    <div class="topbar compact-topbar"><div><h1>ประวัติการประเมินของฉัน</h1><p>เก็บไว้ดูแนวโน้มตัวเอง ไม่ใช่รายการส่งหาแพทย์</p></div></div>
    <?php if (empty($history)): ?>
        <?php render_empty_state('ยังไม่มีประวัติ', 'เมื่อประเมินแล้วจะเห็นประวัติย้อนหลังตรงนี้'); ?>
    <?php else: ?>
        <div class="patient-history-list">
            <?php foreach ($history as $row): ?>
                <div class="patient-history-item">
                    <div><strong><?= e($row['risk_score'] ?? '-') ?>/100</strong><span><?= e($row['created_at'] ?? '-') ?></span></div>
                    <span class="badge <?= e(badge_class((string) ($row['risk_level'] ?? ''))) ?>"><?= e($row['risk_level'] ?? '-') ?></span>
                    <p><?= nl2br(e($row['advice'] ?? '-')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php page_end(); ?>
