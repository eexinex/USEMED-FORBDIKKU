<?php
// public/doctor/ai-risk.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_once __DIR__ . '/../../backend/shared/ai_engine.php';

require_login('doctor');

$patient = demo_patient();

$formData = [
    'age' => $patient['age'],
    'systolic' => 148,
    'diastolic' => 92,
    'glucose' => 142,
    'hba1c' => 7.8,
    'bmi' => 27.4,
    'cholesterol' => 218,
];

$risk = ai_predict_risk_with_ml($formData, $patient);

if (is_post()) {
    $formData = [
        'age' => (float) ($_POST['age'] ?? 0),
        'systolic' => (float) ($_POST['systolic'] ?? 0),
        'diastolic' => (float) ($_POST['diastolic'] ?? 0),
        'glucose' => (float) ($_POST['glucose'] ?? 0),
        'hba1c' => (float) ($_POST['hba1c'] ?? 0),
        'bmi' => (float) ($_POST['bmi'] ?? 0),
        'cholesterol' => (float) ($_POST['cholesterol'] ?? 0),
    ];

    $risk = ai_predict_risk_with_ml($formData, $patient);
}

page_start('AI Risk', 'doctor', 'ai');

topbar(
    'AI Risk Assessment',
    'ประเมินความเสี่ยงเบื้องต้นจากข้อมูลสุขภาพของผู้ป่วย'
);
?>

<section class="stat-grid">
    <?php stat_card('Risk Score', (string) $risk['score'] . '/100', $risk['level_th']); ?>
    <?php stat_card('ระดับความเสี่ยง', $risk['level_th'], $risk['level']); ?>
    <?php stat_card('ผู้ป่วยตัวอย่าง', $patient['hn'], $patient['full_name']); ?>
    <?php stat_card('อายุ', (string) $patient['age'] . ' ปี', $patient['disease']); ?>
</section>

<section class="grid grid-2">
    <div class="form-card">
        <h2>กรอกข้อมูลเพื่อประเมิน</h2>
        <p class="text-muted">
            ประเมินแบบเร็วทันทีเป็นค่าเริ่มต้น หากต้องการเรียก ML service สดให้เปิดหน้านี้ด้วย live_ai=1
        </p>

        <form method="post" class="mt-2" data-loading-title="กำลังประเมิน AI Risk" data-loading-detail="ระบบกำลังคำนวณความเสี่ยงจากข้อมูลสุขภาพล่าสุด">
            <div class="form-grid">
                <div class="field">
                    <label for="age">อายุ</label>
                    <input
                        id="age"
                        name="age"
                        type="number"
                        value="<?= e($formData['age']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="systolic">Systolic BP</label>
                    <input
                        id="systolic"
                        name="systolic"
                        type="number"
                        value="<?= e($formData['systolic']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="diastolic">Diastolic BP</label>
                    <input
                        id="diastolic"
                        name="diastolic"
                        type="number"
                        value="<?= e($formData['diastolic']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="glucose">Glucose</label>
                    <input
                        id="glucose"
                        name="glucose"
                        type="number"
                        step="0.01"
                        value="<?= e($formData['glucose']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="hba1c">HbA1c</label>
                    <input
                        id="hba1c"
                        name="hba1c"
                        type="number"
                        step="0.01"
                        value="<?= e($formData['hba1c']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="bmi">BMI</label>
                    <input
                        id="bmi"
                        name="bmi"
                        type="number"
                        step="0.01"
                        value="<?= e($formData['bmi']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="cholesterol">Cholesterol</label>
                    <input
                        id="cholesterol"
                        name="cholesterol"
                        type="number"
                        step="0.01"
                        value="<?= e($formData['cholesterol']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="btn-row mt-2">
                <button class="btn" type="submit">
                    คำนวณความเสี่ยง
                </button>

                <a class="btn secondary" href="<?= e(app_url('doctor/add-treatment.php')) ?>">
                    เพิ่มการรักษา
                </a>
            </div>
        </form>
    </div>

    <div class="risk-card">
        <div class="risk-score">
            <div>
                <span class="badge <?= e($risk['color']) ?>">
                    Risk <?= e($risk['level_th']) ?>
                </span>

                <h2 style="margin:12px 0 6px;">
                    คะแนนความเสี่ยง <?= e($risk['score']) ?>/100
                </h2>

                <p class="text-muted">
                    <?= e($risk['summary']) ?>
                </p>
            </div>

            <div class="score-circle" style="--value:<?= e($risk['score']) ?>">
                <strong><?= e($risk['score']) ?></strong>
            </div>
        </div>

        <div class="mt-2">
            <div class="riskbar">
                <span style="width:<?= e($risk['score']) ?>%"></span>
            </div>
        </div>

        <h3 class="mt-2">ปัจจัยเสี่ยงที่พบ</h3>

        <ul class="factor-list">
            <?php foreach ($risk['factors'] as $factor): ?>
                <li><?= e($factor) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="grid grid-2 mt-2">
    <div class="card">
        <h2>คำแนะนำเบื้องต้น</h2>

        <div class="document-grid mt-2">
            <?php foreach ($risk['recommendations'] as $recommendation): ?>
                <div class="document-card">
                    <div>
                        <strong>Recommendation</strong>
                        <span><?= e($recommendation) ?></span>
                    </div>
                    <span class="badge blue"><?= e(!empty($risk['model_available']) ? 'XGBoost' : 'Instant') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name']) ?></strong>
                    <span>HN: <?= e($patient['hn']) ?></span>
                </div>
                <span class="badge orange"><?= e($patient['risk_level']) ?></span>
            </div>

            <div class="document-card">
                <div>
                    <strong>โรคประจำตัว</strong>
                    <span><?= e($patient['disease']) ?></span>
                </div>
                <span class="badge red">Chronic</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>นัดหมายถัดไป</strong>
                    <span><?= e($patient['next_appointment']) ?></span>
                </div>
                <span class="badge green">Follow-up</span>
            </div>
        </div>
    </div>
</section>

<?php
page_end();
