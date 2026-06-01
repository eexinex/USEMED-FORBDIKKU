<?php
// public/patient/portal.php
// Elder-friendly patient home: fewer clicks, clear summary, reminders, and direct actions.

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('patient');
usemed_ensure_extended_schema();

$user = current_user();

$patient = demo_patient($user['hn'] ?? null);
$visits = demo_visits();
$documents = demo_documents();
$selfAssessment = null;

if (db_is_connected()) {
    $patientRow = db_fetch_one(
        'SELECT * FROM patients WHERE hn = :hn LIMIT 1',
        ['hn' => $user['hn'] ?? 'HN0001']
    );

    if ($patientRow) {
        $patient = array_merge($patient, $patientRow);

        $dbVisits = db_fetch_all(
            'SELECT v.*, d.full_name AS doctor_name
             FROM visits v
             LEFT JOIN doctors d ON d.id = v.doctor_id
             WHERE v.patient_id = :patient_id
             ORDER BY v.visit_date DESC, v.id DESC
             LIMIT 5',
            ['patient_id' => (int) $patientRow['id']]
        );
        if ($dbVisits) { $visits = $dbVisits; }

        $dbDocuments = db_fetch_all(
            'SELECT * FROM documents
             WHERE patient_id = :patient_id
             ORDER BY created_at DESC, id DESC
             LIMIT 5',
            ['patient_id' => (int) $patientRow['id']]
        );
        if ($dbDocuments) { $documents = $dbDocuments; }

        $selfAssessment = db_fetch_one(
            'SELECT * FROM patient_self_assessments
             WHERE patient_id = :patient_id OR hn = :hn
             ORDER BY created_at DESC, id DESC
             LIMIT 1',
            ['patient_id' => (int) $patientRow['id'], 'hn' => (string)($patientRow['hn'] ?? '')]
        );
    }
}

$latestVisit = $visits[0] ?? [];
$latestDocument = $documents[0] ?? [];

$riskLevel = $latestVisit['risk'] ?? $latestVisit['risk_level'] ?? $patient['risk_level'] ?? 'Medium';
$riskScore = (int) ($latestVisit['risk_score'] ?? $patient['risk_score'] ?? 62);
$riskBadge = badge_class((string) $riskLevel);

$paymentMethod = usemed_visit_field($latestVisit, 'payment_method', (string) ($patient['payment_method'] ?? 'ยังไม่มีข้อมูลสิทธิ'));
$insuranceDetail = usemed_visit_field($latestVisit, 'insurance_detail', (string) ($patient['insurance_detail'] ?? 'ยังไม่มีรายละเอียด'));
$visitHospital = usemed_visit_field($latestVisit, 'hospital', (string) ($patient['hospital'] ?? '-'));
$educationText = usemed_visit_field($latestVisit, 'doctor_education', 'ยังไม่มีคำแนะนำล่าสุดจากแพทย์');
$nextAppointmentDetail = usemed_visit_field($latestVisit, 'next_appointment_detail', (string) ($patient['next_appointment'] ?? '-'));
$followupDate = usemed_visit_field($latestVisit, 'followup_date', (string) ($patient['next_appointment'] ?? '-'));
$doctorName = $latestVisit['doctor'] ?? $latestVisit['doctor_name'] ?? 'ทีมแพทย์';
$lastVisitDate = $latestVisit['date'] ?? $latestVisit['visit_date'] ?? '-';
$lastVisitTitle = $latestVisit['title'] ?? $latestVisit['diagnosis'] ?? 'Visit ล่าสุด';
$lastDocTitle = $latestDocument['title'] ?? 'ยังไม่มีเอกสารล่าสุด';
$lastDocDate = $latestDocument['created_at'] ?? $latestDocument['date'] ?? '-';
$selfDate = $selfAssessment['created_at'] ?? '-';
$selfSummary = $selfAssessment ? 'ประเมินล่าสุดแล้วเมื่อ ' . $selfDate : 'ยังไม่เคยประเมินสุขภาพด้วยตนเอง';

$todoItems = [
    [
        'title' => 'เช็กวันนัด',
        'desc' => $followupDate !== '-' ? 'วันนัดติดตาม: ' . $followupDate : 'ยังไม่มีวันนัดในระบบ',
        'href' => app_url('patient/timeline.php'),
        'tone' => 'green',
    ],
    [
        'title' => 'อ่านคำแนะนำแพทย์',
        'desc' => $educationText,
        'href' => app_url('patient/visit-detail.php?id=' . (int)($latestVisit['id'] ?? 1)),
        'tone' => 'blue',
    ],
    [
        'title' => 'ประเมินสุขภาพตัวเอง',
        'desc' => 'กรอกเบาหวาน/ความดันเพื่อรับคำแนะนำส่วนตัว ข้อมูลไม่ส่งให้แพทย์อัตโนมัติ',
        'href' => app_url('patient/self-assessment.php'),
        'tone' => 'orange',
    ],
];

page_start('Patient Portal', 'patient', 'portal');

$bpSys = usemed_visit_field($latestVisit, 'systolic', '128');
$bpDia = usemed_visit_field($latestVisit, 'diastolic', '78');
$glucose = usemed_visit_field($latestVisit, 'glucose', '145');
$weight = usemed_visit_field($latestVisit, 'weight_kg', (string)($patient['weight_kg'] ?? '68'));
$appointmentDate = $followupDate !== '-' ? $followupDate : '29 พ.ค. 2568';
$appointmentTime = usemed_visit_field($latestVisit, 'next_appointment_time', '09:00 น.');
$careStatus = $riskScore >= 75 ? 'ต้องติดตามใกล้ชิด' : 'กำลังดูแลต่อเนื่อง';
$medTitle = str_contains(mb_strtolower((string)($patient['disease'] ?? ''), 'UTF-8'), 'diabetes') || str_contains((string)($patient['disease'] ?? ''), 'เบาหวาน') ? 'ยาควบคุมน้ำตาล' : 'ยาประจำตัว';
?>

<style>
/* STEP23 patient quick service fix: keep text horizontal and readable */
.patient-premium-page{display:grid!important;gap:18px!important}.quick-service-section{padding:20px!important;border-radius:24px!important;overflow:hidden!important}.quick-service-grid{display:grid!important;grid-template-columns:repeat(2,minmax(320px,1fr))!important;gap:12px!important}.quick-service-grid a{display:grid!important;grid-template-columns:42px minmax(0,1fr) 20px!important;grid-template-areas:"icon title arrow" "icon desc arrow"!important;align-items:center!important;gap:3px 12px!important;min-width:0!important;min-height:78px!important;padding:15px 17px!important;border-radius:18px!important;overflow:hidden!important;background:#fff!important}.quick-service-grid a>span{grid-area:icon!important;width:42px!important;height:42px!important;border-radius:15px!important;display:grid!important;place-items:center!important}.quick-service-grid a>span svg{width:18px!important;height:18px!important}.quick-service-grid a>strong{grid-area:title!important;display:block!important;font-size:16px!important;line-height:1.25!important;white-space:normal!important;word-break:keep-all!important;overflow-wrap:normal!important}.quick-service-grid a>small{grid-area:desc!important;display:block!important;font-size:12.5px!important;line-height:1.3!important;white-space:normal!important;word-break:keep-all!important;overflow-wrap:normal!important;color:#64748b!important}.quick-service-grid a>i{grid-area:arrow!important;display:block!important;font-size:22px!important;line-height:1!important;justify-self:end!important;color:#3345c7!important}.quick-service-grid *{writing-mode:horizontal-tb!important;text-orientation:mixed!important}.quick-service-grid br{display:none!important}@media(max-width:1100px){.quick-service-grid{grid-template-columns:1fr!important}}
</style>


<section class="patient-premium-page">
    <section class="patient-hero-card">
        <div class="patient-identity">
            <div class="patient-avatar-xl"><?= e(initials($patient['full_name'] ?? 'ผู้ป่วย')) ?></div>
            <div>
                <div class="patient-name-row">
                    <h1><?= e($patient['full_name'] ?? 'ผู้ป่วย') ?></h1>
                    <span class="soft-tag">ผู้ป่วย</span>
                </div>
                <p><?= e($patient['hn'] ?? '-') ?> · <?= e($patient['gender'] ?? '-') ?> · อายุ <?= e((string)($patient['age'] ?? '-')) ?> ปี</p>
                <strong><?= e((string)($patient['disease'] ?? 'ข้อมูลสุขภาพ')) ?></strong>
            </div>
        </div>
        <div class="patient-hero-divider"></div>
        <div class="patient-hero-stat">
            <span class="ux-card-icon calendar"><?= icon_svg('calendar') ?></span>
            <small>เข้ารับการรักษาล่าสุด</small>
            <strong><?= e($lastVisitDate) ?></strong>
            <p><?= e($doctorName) ?></p>
        </div>
        <div class="patient-hero-stat">
            <span class="ux-card-icon heart"><?= icon_svg('icu') ?></span>
            <small>สถานะการดูแล</small>
            <strong><?= e($careStatus) ?></strong>
            <p>ติดตามอาการตามแผนการรักษา</p>
        </div>
    </section>

    <div class="section-label">สิ่งที่ควรทราบ</div>
    <section class="patient-reminder-grid">
        <a class="patient-reminder-card purple" href="<?= e(app_url('patient/timeline.php')) ?>">
            <span class="ux-card-icon"><?= icon_svg('calendar') ?></span>
            <div><small>นัดหมายครั้งถัดไป</small><strong><?= e($appointmentDate) ?></strong><p><?= e($appointmentTime) ?> · แผนกอายุรกรรม</p></div>
            <i>›</i>
        </a>
        <a class="patient-reminder-card green" href="<?= e(app_url('patient/documents.php')) ?>">
            <span class="ux-card-icon"><?= icon_svg('rx') ?></span>
            <div><small>ใกล้ถึงเวลายา</small><strong><?= e($medTitle) ?></strong><p>ตรวจสอบรายการยาและใบสั่งยา</p></div>
            <i>›</i>
        </a>
        <a class="patient-reminder-card amber" href="<?= e(app_url('patient/self-assessment.php')) ?>">
            <span class="ux-card-icon"><?= icon_svg('assessment') ?></span>
            <div><small>ติดตามอาการ</small><strong>บันทึกสุขภาพประจำสัปดาห์</strong><p>ประเมินเบาหวาน / ความดัน</p></div>
            <i>›</i>
        </a>
        <a class="patient-reminder-card blue" href="<?= e(app_url('patient/documents.php')) ?>">
            <span class="ux-card-icon"><?= icon_svg('doc') ?></span>
            <div><small>ผลตรวจแนะนำ</small><strong>ตรวจเลือดประจำ 3 เดือน</strong><p>ดูผลตรวจและเอกสารล่าสุด</p></div>
            <i>›</i>
        </a>
    </section>

    <section class="patient-main-grid">
        <article class="patient-assessment-card">
            <div>
                <span class="card-eyebrow">ประเมินสุขภาพด้วยตนเอง</span>
                <h2>เบาหวาน / ความดัน</h2>
                <p>ติดตามสุขภาพของคุณง่าย ๆ สม่ำเสมอ เพื่อรับคำแนะนำเบื้องต้นแบบเป็นส่วนตัว</p>
                <div class="patient-benefits">
                    <span>แนวโน้มสุขภาพ</span>
                    <span>คำแนะนำเฉพาะคุณ</span>
                    <span>ข้อมูลเป็นความลับ</span>
                </div>
                <a class="gradient-action" href="<?= e(app_url('patient/self-assessment.php')) ?>">เริ่มประเมินตอนนี้ <b>›</b></a>
            </div>
            <div class="assessment-illustration">
                <span><?= icon_svg('assessment') ?></span>
            </div>
        </article>

        <article class="patient-visit-card">
            <div class="card-headline">
                <div><span class="card-eyebrow">สรุปล่าสุด</span><h2>การเข้ารับการรักษา</h2></div>
                <a href="<?= e(app_url('patient/timeline.php')) ?>">ดูประวัติทั้งหมด</a>
            </div>
            <p><?= e($lastVisitDate) ?> · <?= e($doctorName) ?></p>
            <strong><?= e($lastVisitTitle) ?></strong>
            <div class="vital-grid">
                <div><span>ความดันโลหิต</span><b><?= e($bpSys) ?>/<?= e($bpDia) ?></b><small>mmHg</small></div>
                <div><span>น้ำตาลในเลือด</span><b><?= e($glucose) ?></b><small>mg/dL</small></div>
                <div><span>น้ำหนัก</span><b><?= e($weight) ?></b><small>กก.</small></div>
                <div><span>คำแนะนำแพทย์</span><b>ต่อเนื่อง</b><small><?= e(mb_strimwidth($educationText, 0, 60, '...', 'UTF-8')) ?></small></div>
            </div>
        </article>
    </section>

    <section class="patient-results-section">
        <div class="card-headline">
            <div><span class="card-eyebrow">เอกสาร / ผลตรวจล่าสุด</span><h2>ข้อมูลสุขภาพของฉัน</h2></div>
            <a href="<?= e(app_url('patient/documents.php')) ?>">ดูทั้งหมด ›</a>
        </div>
        <div class="result-card-row">
            <?php if (empty($documents)): ?>
                <div class="result-mini-card"><span><?= icon_svg('doc') ?></span><strong>ยังไม่มีเอกสาร</strong><small>เมื่อมีเอกสารจะแสดงที่นี่</small></div>
            <?php else: ?>
                <?php foreach (array_slice($documents, 0, 5) as $index => $doc): ?>
                    <?php $id = (int)($doc['id'] ?? 1); ?>
                    <a class="result-mini-card" href="<?= e(app_url('patient/document-view.php?id=' . $id)) ?>">
                        <span><?= icon_svg($index === 1 ? 'assessment' : 'doc') ?></span>
                        <strong><?= e($doc['title'] ?? 'เอกสารสุขภาพ') ?></strong>
                        <small><?= e($doc['document_type'] ?? $doc['type'] ?? 'PDF') ?> · <?= e($doc['created_at'] ?? $doc['date'] ?? '-') ?></small>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="quick-service-section">
        <div class="section-label">บริการด่วน</div>
        <div class="quick-service-grid">
            <a href="<?= e(app_url('patient/timeline.php')) ?>"><span><?= icon_svg('calendar') ?></span><strong>นัดหมายแพทย์</strong><small>จองหรือเปลี่ยนวันนัด</small><i>›</i></a>
            <a href="<?= e(app_url('support.php')) ?>"><span><?= icon_svg('message') ?></span><strong>ปรึกษาออนไลน์</strong><small>แจ้งคำถามถึงเจ้าหน้าที่</small><i>›</i></a>
            <a href="<?= e(app_url('support.php')) ?>"><span><?= icon_svg('help') ?></span><strong>แจ้งปัญหา</strong><small>รายงานปัญหาในระบบ</small><i>›</i></a>
            <a href="<?= e(app_url('support.php')) ?>"><span><?= icon_svg('headset') ?></span><strong>ติดต่อโรงพยาบาล</strong><small>เบอร์โทร / แผนที่</small><i>›</i></a>
        </div>
    </section>
</section>

<?php page_end(); ?>
