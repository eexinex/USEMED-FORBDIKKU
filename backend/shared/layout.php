<?php
// backend/shared/layout.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/auth.php';

function page_start(string $title = 'USE MED', string $role = 'guest', string $active = ''): void
{
    $flash = flash_get();

    ?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | USE MED</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/usemed.css')) ?>?v=step7-care-followup-lists">
</head>
<body class="role-<?= e($role) ?> active-<?= e($active) ?>">
<div class="<?= $role === 'guest' ? 'guest-shell' : 'app-shell' ?>">
    <?php if ($role !== 'guest'): ?>
        <?= render_sidebar($role, $active) ?>
    <?php endif; ?>

    <main class="<?= $role === 'guest' ? 'guest-main' : 'main-content' ?>">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
    <?php
}

function page_end(): void
{
    ?>
    </main>
</div>
<script src="<?= e(app_url('assets/app.js')) ?>?v=step7-care-followup-lists" defer></script>
</body>
</html>
    <?php
}

function render_sidebar(string $role, string $active = ''): string
{
    $user = current_user();
    $name = $user['name'] ?? 'User';

    ob_start();
    ?>
    <aside class="sidebar">
        <a class="brand-block" href="<?= e(app_url('index.php')) ?>">
            <div class="brand-logo">UM</div>
            <div>
                <strong>USE MED</strong>
                <span><?= e(ucfirst($role)) ?> Portal</span>
            </div>
        </a>

        <div class="user-card">
            <div class="user-avatar"><?= e(initials($name)) ?></div>
            <div>
                <strong><?= e($name) ?></strong>
                <span><?= e($role) ?></span>
            </div>
        </div>

        <nav class="side-nav">
            <?php foreach (nav_items($role) as $key => $item): ?>
                <a class="<?= e(active_class($active, $key)) ?>" href="<?= e(app_url($item['href'])) ?>">
                    <span><?= e($item['icon']) ?></span>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <a class="logout-link" href="<?= e(app_url($role . '/logout.php')) ?>">
            ออกจากระบบ
        </a>
    </aside>
    <?php

    return ob_get_clean();
}

function nav_items(string $role): array
{
    if ($role === 'patient') {
        return [
            'portal' => [
                'label' => 'หน้าหลัก',
                'href' => 'patient/portal.php',
                'icon' => '🏠',
            ],
            'timeline' => [
                'label' => 'Timeline',
                'href' => 'patient/timeline.php',
                'icon' => '🕒',
            ],
            'documents' => [
                'label' => 'เอกสาร',
                'href' => 'patient/documents.php',
                'icon' => '📄',
            ],
            'support' => [
                'label' => 'แจ้งปัญหา',
                'href' => 'support.php',
                'icon' => '🛟',
            ],
        ];
    }

    if ($role === 'doctor') {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'href' => 'doctor/dashboard.php',
                'icon' => '📊',
            ],
            'patient' => [
                'label' => 'ข้อมูลผู้ป่วย',
                'href' => 'doctor/patient-profile.php',
                'icon' => '🔎',
            ],
            'treatment' => [
                'label' => 'เพิ่มการรักษา',
                'href' => 'doctor/add-treatment.php',
                'icon' => '➕',
            ],
            'ai' => [
                'label' => 'AI Risk',
                'href' => 'doctor/ai-risk.php',
                'icon' => '🧠',
            ],
            'referral' => [
                'label' => 'ส่งตัว/ส่งต่อ',
                'href' => 'doctor/referral.php',
                'icon' => '🔁',
            ],
            'icu' => [
                'label' => 'IPD / ICU / ผ่าตัด',
                'href' => 'doctor/care-list.php?type=IPD',
                'icon' => '🏥',
            ],
            'documents' => [
                'label' => 'เอกสาร',
                'href' => 'doctor/documents.php',
                'icon' => '📄',
            ],
        ];
    }

    if ($role === 'admin') {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'href' => 'admin/dashboard.php',
                'icon' => '🛠️',
            ],
            'users' => [
                'label' => 'ผู้ใช้งาน',
                'href' => 'admin/users.php',
                'icon' => '👥',
            ],
            'tickets' => [
                'label' => 'Support',
                'href' => 'admin/tickets.php',
                'icon' => '🎫',
            ],
        ];
    }

    return [];
}

function topbar(string $title, string $subtitle = ''): void
{
    $user = current_user();

    ?>
    <div class="topbar">
        <div>
            <h1><?= e($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($user): ?>
            <div class="topbar-user">
                <?= e($user['name'] ?? 'User') ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function stat_card(string $label, string $value, string $badge = ''): void
{
    ?>
    <div class="stat-card" data-animate>
        <span><?= e($label) ?></span>
        <strong><?= e($value) ?></strong>
        <?php if ($badge !== ''): ?>
            <em><?= e($badge) ?></em>
        <?php endif; ?>
    </div>
    <?php
}

function demo_patients(): array
{
    return [
        ['id'=>1,'hn'=>'HN0001','password'=>'123456','full_name'=>'สมชาย ใจดี','gender'=>'ชาย','age'=>58,'phone'=>'081-234-5678','disease'=>'Type 2 Diabetes Mellitus','risk_level'=>'Medium','risk_score'=>62,'next_appointment'=>'12 มิ.ย. 2026','address'=>'อ.เมือง จ.ขอนแก่น','care_area'=>'OPD','department'=>'อายุรกรรม','ward'=>'OPD เบาหวาน','hospital'=>'โรงพยาบาลขอนแก่น','surgery_status'=>'-','high_watch'=>0],
        ['id'=>2,'hn'=>'HN0002','password'=>'123456','full_name'=>'สมหญิง สุขใจ','gender'=>'หญิง','age'=>64,'phone'=>'082-111-2233','disease'=>'Hypertension / CKD stage 3','risk_level'=>'High','risk_score'=>81,'next_appointment'=>'14 มิ.ย. 2026','address'=>'อ.บ้านไผ่ จ.ขอนแก่น','care_area'=>'IPD','department'=>'อายุรกรรมโรคไต','ward'=>'อายุรกรรมหญิง 5A','hospital'=>'โรงพยาบาลศรีนครินทร์','surgery_status'=>'รอประเมิน','high_watch'=>1],
        ['id'=>3,'hn'=>'HN0003','password'=>'123456','full_name'=>'อนันต์ แสงทอง','gender'=>'ชาย','age'=>42,'phone'=>'083-456-7890','disease'=>'Acute appendicitis','risk_level'=>'Medium','risk_score'=>54,'next_appointment'=>'วันนี้ 15:30','address'=>'เขตลาดกระบัง กรุงเทพฯ','care_area'=>'ผ่าตัด','department'=>'ศัลยกรรมทั่วไป','ward'=>'OR 2','hospital'=>'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร','surgery_status'=>'กำลังผ่าตัด','high_watch'=>0],
        ['id'=>4,'hn'=>'HN0004','password'=>'123456','full_name'=>'ปรียา วงศ์สวัสดิ์','gender'=>'หญิง','age'=>36,'phone'=>'084-777-9000','disease'=>'Gallstone','risk_level'=>'Low','risk_score'=>28,'next_appointment'=>'18 มิ.ย. 2026','address'=>'เขตราชเทวี กรุงเทพฯ','care_area'=>'คิวผ่าตัด','department'=>'ศัลยกรรมทางเดินอาหาร','ward'=>'Surgical Queue','hospital'=>'โรงพยาบาลราชวิถี','surgery_status'=>'คิวผ่าตัด 18 มิ.ย. 2026','high_watch'=>0],
        ['id'=>5,'hn'=>'HN0005','password'=>'123456','full_name'=>'วิชัย มั่นคง','gender'=>'ชาย','age'=>71,'phone'=>'085-222-4411','disease'=>'COPD exacerbation','risk_level'=>'High','risk_score'=>88,'next_appointment'=>'ติดตามทุก 4 ชม.','address'=>'เขตปทุมวัน กรุงเทพฯ','care_area'=>'ICU','department'=>'เวชบำบัดวิกฤต','ward'=>'ICU เตียง 7','hospital'=>'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย','surgery_status'=>'-','high_watch'=>1],
        ['id'=>6,'hn'=>'HN0006','password'=>'123456','full_name'=>'กมลชนก รัตนสุข','gender'=>'หญิง','age'=>29,'phone'=>'086-333-5566','disease'=>'Pregnancy with GDM','risk_level'=>'Medium','risk_score'=>60,'next_appointment'=>'20 มิ.ย. 2026','address'=>'อ.น้ำพอง จ.ขอนแก่น','care_area'=>'OPD','department'=>'สูติกรรม','ward'=>'ANC Clinic','hospital'=>'โรงพยาบาลขอนแก่น','surgery_status'=>'-','high_watch'=>0],
        ['id'=>7,'hn'=>'HN0007','password'=>'123456','full_name'=>'ธนกร เพชรดี','gender'=>'ชาย','age'=>55,'phone'=>'087-666-1200','disease'=>'NSTEMI post PCI','risk_level'=>'High','risk_score'=>79,'next_appointment'=>'พรุ่งนี้ 09:00','address'=>'อ.เมือง จ.ขอนแก่น','care_area'=>'IPD','department'=>'อายุรกรรมหัวใจ','ward'=>'CCU Stepdown','hospital'=>'โรงพยาบาลศรีนครินทร์','surgery_status'=>'-','high_watch'=>1],
        ['id'=>8,'hn'=>'HN0008','password'=>'123456','full_name'=>'รัชนี พูลผล','gender'=>'หญิง','age'=>49,'phone'=>'088-989-3311','disease'=>'Breast mass workup','risk_level'=>'Medium','risk_score'=>48,'next_appointment'=>'22 มิ.ย. 2026','address'=>'กรุงเทพมหานคร','care_area'=>'คิวผ่าตัด','department'=>'ศัลยกรรมเต้านม','ward'=>'Surgical Queue','hospital'=>'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย','surgery_status'=>'รอคิวผ่าตัด','high_watch'=>0],
        ['id'=>9,'hn'=>'HN0009','password'=>'123456','full_name'=>'ศุภชัย คำดี','gender'=>'ชาย','age'=>33,'phone'=>'089-100-2000','disease'=>'Trauma observation','risk_level'=>'High','risk_score'=>74,'next_appointment'=>'ติดตามทุก 2 ชม.','address'=>'เขตลาดกระบัง กรุงเทพฯ','care_area'=>'คนไข้เฝ้าระวังสูง','department'=>'เวชศาสตร์ฉุกเฉิน','ward'=>'Observation Unit','hospital'=>'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร','surgery_status'=>'รอ CT','high_watch'=>1],
        ['id'=>10,'hn'=>'HN0010','password'=>'123456','full_name'=>'มลฤดี ศรีสุข','gender'=>'หญิง','age'=>61,'phone'=>'080-444-8181','disease'=>'Stroke rehabilitation','risk_level'=>'Medium','risk_score'=>57,'next_appointment'=>'25 มิ.ย. 2026','address'=>'จ.ขอนแก่น','care_area'=>'IPD','department'=>'เวชศาสตร์ฟื้นฟู','ward'=>'Rehab Ward','hospital'=>'โรงพยาบาลขอนแก่น','surgery_status'=>'-','high_watch'=>0],
    ];
}

function demo_patient(?string $hn = null): array
{
    if ($hn === null || $hn === '') {
        $hn = $_GET['hn'] ?? ($_SESSION['user']['hn'] ?? 'HN0001');
    }

    foreach (demo_patients() as $patient) {
        if (strcasecmp((string) $patient['hn'], (string) $hn) === 0) {
            return $patient;
        }
    }

    return demo_patients()[0];
}

function demo_doctors(): array
{
    return [
        ['id'=>1,'username'=>'doctor1','password'=>'123456','full_name'=>'นพ.กิตติ ภัทรเวช','license_no'=>'MD-1026588','department'=>'อายุรกรรม','hospital'=>'โรงพยาบาลขอนแก่น'],
        ['id'=>2,'username'=>'doctor2','password'=>'123456','full_name'=>'พญ.ณิชา ศรีแพทย์','license_no'=>'MD-2047712','department'=>'ศัลยกรรมทั่วไป','hospital'=>'โรงพยาบาลศรีนครินทร์'],
        ['id'=>3,'username'=>'doctor3','password'=>'123456','full_name'=>'นพ.ธนดล วัฒนกุล','license_no'=>'MD-3091188','department'=>'เวชบำบัดวิกฤต','hospital'=>'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร'],
    ];
}

function demo_doctor(?string $username = null): array
{
    if ($username === null || $username === '') {
        $username = $_SESSION['user']['username'] ?? 'doctor1';
    }

    foreach (demo_doctors() as $doctor) {
        if (strcasecmp((string) $doctor['username'], (string) $username) === 0) {
            return $doctor;
        }
    }

    return demo_doctors()[0];
}

function demo_departments(): array
{
    return [
        'อายุรกรรม',
        'อายุรกรรมหัวใจ',
        'อายุรกรรมโรคไต',
        'ศัลยกรรมทั่วไป',
        'ศัลยกรรมทางเดินอาหาร',
        'ศัลยกรรมเต้านม',
        'สูติกรรม',
        'เวชศาสตร์ฉุกเฉิน',
        'เวชบำบัดวิกฤต',
        'เวชศาสตร์ฟื้นฟู',
    ];
}

function demo_hospitals(): array
{
    return [
        'โรงพยาบาลขอนแก่น',
        'โรงพยาบาลศรีนครินทร์',
        'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร',
        'โรงพยาบาลราชวิถี',
        'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย',
    ];
}

function demo_patient_flow_summary(array $patients = []): array
{
    if (empty($patients)) {
        $patients = demo_patients();
    }

    $keys = ['OPD','IPD','ICU','ผ่าตัด','คิวผ่าตัด','คนไข้เฝ้าระวังสูง'];
    $summary = array_fill_keys($keys, 0);

    foreach ($patients as $patient) {
        $area = (string) ($patient['care_area'] ?? 'OPD');
        if (isset($summary[$area])) {
            $summary[$area]++;
        }
        if (!empty($patient['high_watch']) || str_contains((string) ($patient['risk_level'] ?? ''), 'High') || str_contains((string) ($patient['risk_level'] ?? ''), 'สูง')) {
            $summary['คนไข้เฝ้าระวังสูง']++;
        }
    }

    return $summary;
}


function usemed_care_type_map(): array
{
    return [
        'OPD' => ['label' => 'OPD', 'area' => 'OPD', 'icon' => '🏥', 'tone' => 'blue'],
        'IPD' => ['label' => 'IPD', 'area' => 'IPD', 'icon' => '🛏️', 'tone' => 'green'],
        'ICU' => ['label' => 'ICU', 'area' => 'ICU', 'icon' => '🚨', 'tone' => 'red'],
        'SURGERY' => ['label' => 'ผ่าตัด', 'area' => 'ผ่าตัด', 'icon' => '🔪', 'tone' => 'orange'],
        'SURGERY_QUEUE' => ['label' => 'คิวผ่าตัด', 'area' => 'คิวผ่าตัด', 'icon' => '📋', 'tone' => 'orange'],
        'HIGH_WATCH' => ['label' => 'คนไข้เฝ้าระวังสูง', 'area' => 'คนไข้เฝ้าระวังสูง', 'icon' => '⚠️', 'tone' => 'red'],
    ];
}

function usemed_care_type_key_from_label(string $label): string
{
    $label = trim($label);
    foreach (usemed_care_type_map() as $key => $meta) {
        if ($label === $key || $label === $meta['label'] || $label === $meta['area']) {
            return $key;
        }
    }
    return 'OPD';
}

function usemed_care_type_meta(string $type): array
{
    $type = strtoupper(trim($type));
    $map = usemed_care_type_map();
    return $map[$type] ?? $map[usemed_care_type_key_from_label($type)] ?? $map['OPD'];
}

function usemed_days_between(string $start, ?string $end = null): int
{
    try {
        $a = new DateTime($start);
        $b = $end ? new DateTime($end) : new DateTime('2026-05-28');
        return max(1, (int) $a->diff($b)->format('%a') + 1);
    } catch (Throwable $e) {
        return 1;
    }
}

function usemed_patient_followup_details(array $patient): array
{
    $hn = (string) ($patient['hn'] ?? 'HN0001');
    $area = (string) ($patient['care_area'] ?? 'OPD');
    $defaults = [
        'admission_date' => '2026-05-27',
        'expected_discharge_date' => '2026-06-01',
        'discharge_date' => '',
        'length_of_stay' => '1 วัน',
        'last_round_date' => '2026-05-28 09:00',
        'bed_status' => (string) ($patient['ward'] ?? '-'),
        'discharge_status' => 'ยังไม่จำหน่าย',
        'additional_medication' => 'ยังไม่มีคำสั่งยาเพิ่ม',
        'followup_plan' => 'ติดตามอาการตามแผนแพทย์เจ้าของไข้',
        'discharge_plan' => 'ประเมินอาการซ้ำก่อนจำหน่าย',
        'daily_note' => 'อาการทั่วไปคงที่ รอประเมินต่อเนื่อง',
        'operation_name' => '-',
        'operation_date' => '',
        'operation_status' => (string) ($patient['surgery_status'] ?? '-'),
        'operation_size' => '-',
        'icu_day' => '',
        'icu_daily_note' => '-',
        'ventilator_status' => '-',
        'vasopressor_status' => '-',
        'fluid_balance' => '-',
        'line_tube_status' => '-',
        'monitoring_frequency' => 'ประเมินทุก 8 ชม.',
        'escalation_plan' => 'แจ้งแพทย์เวรทันทีหากสัญญาณชีพผิดปกติ',
        'round_focus' => 'ยา อาการ ผลตรวจ และแผนจำหน่าย',
    ];

    $map = [
        'HN0001' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'OPD','last_round_date'=>'2026-05-28 10:20','additional_medication'=>'Metformin เดิม / นัดติดตาม HbA1c','followup_plan'=>'OPD เบาหวาน นัดติดตาม 12 มิ.ย. 2026','daily_note'=>'มาตรวจตามนัด อาการคงที่ ให้คำแนะนำอาหารและยา','round_focus'=>'คุมระดับน้ำตาลและทบทวนการใช้ยา'],
        'HN0002' => ['admission_date'=>'2026-05-24','expected_discharge_date'=>'2026-06-02','length_of_stay'=>'5 วัน','last_round_date'=>'2026-05-28 08:45','additional_medication'=>'เพิ่ม Amlodipine 5 mg OD และปรับ Lasix ตาม I/O','followup_plan'=>'ติดตาม Cr/eGFR, BP, urine output ทุกวัน','discharge_plan'=>'คาดจำหน่ายเมื่อ BP คุมได้และ renal function คงที่','daily_note'=>'BP ยังสูงเป็นช่วง ๆ ปัสสาวะดีขึ้น ไม่มีหอบเหนื่อย','round_focus'=>'ความดัน ไต ยาเพิ่ม และแผนจำหน่าย'],
        'HN0003' => ['admission_date'=>'2026-05-28','expected_discharge_date'=>'2026-05-31','length_of_stay'=>'1 วัน','last_round_date'=>'2026-05-28 13:00','additional_medication'=>'Ceftriaxone + Metronidazole, analgesic PRN','operation_name'=>'Appendectomy','operation_date'=>'2026-05-28','operation_status'=>'กำลังผ่าตัด/รอดู Post-op','operation_size'=>'ผ่าตัดเล็ก-กลาง','followup_plan'=>'ติดตาม pain score, fever, wound, bowel movement','discharge_plan'=>'คาดจำหน่ายหลังรับประทานอาหารได้ ไม่มีไข้','daily_note'=>'เตรียมผ่าตัดครบ Consent เรียบร้อย','round_focus'=>'Post-op complication และแผลผ่าตัด'],
        'HN0004' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'รอคิว','last_round_date'=>'2026-05-28 11:10','additional_medication'=>'ให้ยาแก้ปวด PRN / งด NSAIDs ก่อนผ่าตัดตามแพทย์สั่ง','operation_name'=>'Laparoscopic cholecystectomy','operation_date'=>'2026-06-18','operation_status'=>'รอคิวผ่าตัด','operation_size'=>'ผ่าตัดกลาง','followup_plan'=>'Pre-op lab, CXR, EKG, anesthesia consult','discharge_plan'=>'วางแผน admit ก่อนผ่าตัด 1 วัน','daily_note'=>'รอผล pre-op และยืนยันคิว OR','round_focus'=>'ความพร้อมก่อนผ่าตัดและเอกสาร Consent'],
        'HN0005' => ['admission_date'=>'2026-05-22','expected_discharge_date'=>'ยังประเมินไม่ได้','length_of_stay'=>'7 วัน','last_round_date'=>'2026-05-28 07:30','additional_medication'=>'ปรับยาพ่นขยายหลอดลม / IV steroid / antibiotic ตาม culture','icu_day'=>'ICU day 7','icu_daily_note'=>'หอบลดลงแต่ยังต้องเฝ้าระวัง CO2 retention ใกล้ชิด','ventilator_status'=>'HFNC 50 LPM FiO2 0.45 ยังไม่ใส่ท่อ','vasopressor_status'=>'ไม่ใช้ vasopressor','fluid_balance'=>'+450 ml/24h','line_tube_status'=>'PIV x2, Foley catheter','followup_plan'=>'ABG, CXR, SpO2 continuous, wean oxygen ตามอาการ','discharge_plan'=>'ย้ายออก ICU เมื่อ O2 requirement ลดลงและ ABG ดีขึ้น','daily_note'=>'SpO2 94-96% บน HFNC รู้สึกตัวดี ไม่มี shock','monitoring_frequency'=>'ประเมินทุก 1 ชม.','escalation_plan'=>'หาก SpO2 < 90%, ซึมลง หรือ ABG แย่ แจ้ง intensivist ทันที','round_focus'=>'Respiratory support, infection control, fluid balance'],
        'HN0006' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'OPD','last_round_date'=>'2026-05-28 09:40','additional_medication'=>'ปรับโภชนบำบัด / SMBG / พิจารณา insulin หากน้ำตาลสูงต่อเนื่อง','followup_plan'=>'ANC + GDM clinic นัด 20 มิ.ย. 2026','daily_note'=>'ติดตามน้ำตาลระหว่างตั้งครรภ์ ให้ความรู้เรื่องอาหาร','round_focus'=>'FBS/PP glucose และ fetal wellbeing'],
        'HN0007' => ['admission_date'=>'2026-05-26','expected_discharge_date'=>'2026-06-03','length_of_stay'=>'3 วัน','last_round_date'=>'2026-05-28 08:20','additional_medication'=>'DAPT + Statin high intensity / ปรับ beta-blocker ตาม HR/BP','followup_plan'=>'ติดตาม chest pain, EKG, troponin trend, rehab plan','discharge_plan'=>'คาดจำหน่ายหลังอาการคงที่และวางแผน cardiac rehab','daily_note'=>'ไม่มีเจ็บหน้าอกซ้ำ สัญญาณชีพคงที่หลัง PCI','round_focus'=>'DAPT adherence, recurrent chest pain, rehab'],
        'HN0008' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'รอคิว','last_round_date'=>'2026-05-28 12:10','additional_medication'=>'ยาแก้ปวด PRN / นัด review imaging ก่อนผ่าตัด','operation_name'=>'Excision breast mass / biopsy','operation_date'=>'2026-06-22','operation_status'=>'รอคิวผ่าตัด','operation_size'=>'ผ่าตัดเล็ก','followup_plan'=>'รอผล imaging + anesthesia clearance','discharge_plan'=>'Day surgery หากไม่มีภาวะแทรกซ้อน','daily_note'=>'รอยืนยันคิว OR และเอกสาร consent','round_focus'=>'ผล imaging, consent, วันผ่าตัด'],
        'HN0009' => ['admission_date'=>'2026-05-28','expected_discharge_date'=>'2026-05-30','length_of_stay'=>'1 วัน','last_round_date'=>'2026-05-28 14:00','additional_medication'=>'Analgesic PRN / neuro observation protocol','followup_plan'=>'Neuro sign ทุก 2 ชม., รอ CT brain, observe bleeding','discharge_plan'=>'จำหน่ายได้หาก CT ปกติและอาการคงที่ครบ 24-48 ชม.','daily_note'=>'รู้สึกตัวดี ปวดศีรษะเล็กน้อย ไม่มีอาเจียนพุ่ง','monitoring_frequency'=>'ประเมินทุก 2 ชม.','escalation_plan'=>'หาก GCS ลดลง ปวดศีรษะมาก ชัก หรืออาเจียน แจ้ง ER/ศัลย์ประสาททันที','round_focus'=>'Neurological deterioration และผล CT'],
        'HN0010' => ['admission_date'=>'2026-05-25','expected_discharge_date'=>'2026-06-05','length_of_stay'=>'4 วัน','last_round_date'=>'2026-05-28 10:00','additional_medication'=>'Antiplatelet + Statin / DVT prophylaxis ตามข้อบ่งชี้','followup_plan'=>'กายภาพ daily, swallow assessment, BP control','discharge_plan'=>'วางแผนส่งต่อเวชศาสตร์ฟื้นฟู/บ้านเมื่อ ADL ดีขึ้น','daily_note'=>'อ่อนแรงดีขึ้นเล็กน้อย รับประทานอาหารอ่อนได้','round_focus'=>'Rehab goal, aspiration risk, discharge planning'],
    ];

    $details = array_merge($defaults, $map[$hn] ?? []);

    if (($details['length_of_stay'] ?? '') === '' && !empty($details['admission_date'])) {
        $details['length_of_stay'] = usemed_days_between((string) $details['admission_date']) . ' วัน';
    }

    foreach ($details as $key => $value) {
        if (isset($patient[$key]) && $patient[$key] !== null && $patient[$key] !== '') {
            $details[$key] = (string) $patient[$key];
        }
    }

    if ($area === 'OPD') {
        $details['bed_status'] = 'OPD / Clinic';
        $details['discharge_status'] = 'กลับบ้านหลังตรวจ';
    }

    return $details;
}

function usemed_get_care_patients(string $type): array
{
    $type = usemed_care_type_key_from_label($type);
    if (db_is_connected()) {
        $rows = usemed_db_care_patients($type);
        if (!empty($rows)) {
            return $rows;
        }
    }
    return usemed_demo_care_patients($type);
}

function usemed_demo_care_patients(string $type): array
{
    $meta = usemed_care_type_meta($type);
    $type = usemed_care_type_key_from_label($type);
    $items = [];
    foreach (demo_patients() as $patient) {
        $area = (string) ($patient['care_area'] ?? 'OPD');
        $risk = (string) ($patient['risk_level'] ?? '');
        $include = false;
        if ($type === 'HIGH_WATCH') {
            $include = !empty($patient['high_watch']) || $area === 'คนไข้เฝ้าระวังสูง' || str_contains($risk, 'High') || str_contains($risk, 'สูง');
        } else {
            $include = $area === $meta['area'];
        }
        if ($include) {
            $patient['followup'] = usemed_patient_followup_details($patient);
            $items[] = $patient;
        }
    }
    return $items;
}

function usemed_db_care_patients(string $type): array
{
    if (!db_is_connected()) {
        return [];
    }

    usemed_ensure_extended_schema();
    $meta = usemed_care_type_meta($type);
    $columns = usemed_table_columns('patients');
    $where = [];
    $params = [];

    if ($type === 'HIGH_WATCH') {
        if (in_array('high_watch', $columns, true)) {
            $where[] = 'high_watch = 1';
        }
        if (in_array('care_area', $columns, true)) {
            $where[] = 'care_area = :high_area';
            $params['high_area'] = 'คนไข้เฝ้าระวังสูง';
        }
        if (in_array('risk_level', $columns, true)) {
            $where[] = "risk_level IN ('High', 'สูง')";
        }
    } elseif (in_array('care_area', $columns, true)) {
        $where[] = 'care_area = :area';
        $params['area'] = $meta['area'];
    }

    if (empty($where)) {
        return [];
    }

    $sql = 'SELECT * FROM patients WHERE (' . implode(' OR ', $where) . ') ORDER BY ward ASC, full_name ASC, id ASC';
    $rows = db_fetch_all($sql, $params);
    foreach ($rows as &$row) {
        $row['followup'] = usemed_patient_followup_details($row);
    }
    unset($row);
    return $rows;
}

function demo_visits(?string $hn = null): array
{
    $patient = demo_patient($hn);
    $doctor = demo_doctor();
    $risk = (string) ($patient['risk_level'] ?? 'Medium');
    $score = (int) ($patient['risk_score'] ?? 62);

    $saved = $_SESSION['demo_saved_visits'] ?? [];
    $savedItems = [];
    if (is_array($saved)) {
        foreach ($saved as $item) {
            if ($hn === null || $hn === '' || strcasecmp((string) ($item['hn'] ?? ''), (string) $patient['hn']) === 0) {
                $savedItems[] = $item;
            }
        }
    }

    $items = [
        [
            'id' => 1,
            'date' => '27 พ.ค. 2026',
            'visit_date' => '2026-05-27',
            'title' => 'ตรวจติดตาม / ประเมินอาการล่าสุด',
            'doctor' => $doctor['full_name'],
            'doctor_name' => $doctor['full_name'],
            'diagnosis' => $patient['disease'] ?? 'Follow up',
            'treatment_plan' => 'ติดตามอาการ ปรับแผนยา ให้คำแนะนำการดูแลตัวเอง และนัดตรวจซ้ำตามความเหมาะสม',
            'summary' => 'สถานะ: ' . ($patient['care_area'] ?? 'OPD') . ' | แผนก: ' . ($patient['department'] ?? '-'),
            'chief_complaint' => 'มาติดตามอาการและทบทวนแผนการรักษา',
            'present_illness' => 'ผู้ป่วยมาตามนัด อาการโดยรวมคงที่ ไม่มีอาการฉุกเฉินใหม่',
            'review_of_systems' => 'ไม่มีไข้ ไม่มีเจ็บหน้าอก ไม่มีหอบเหนื่อยเฉียบพลัน',
            'past_history' => $patient['disease'] ?? '-',
            'allergy_history' => 'ไม่มีประวัติแพ้ยาที่ทราบ',
            'current_medications' => 'ยาประจำตามโรคเดิม',
            'family_history' => 'ไม่มีประวัติสำคัญเพิ่มเติม',
            'social_history' => 'ประเมินการดื่มสุรา สูบบุหรี่ และการดูแลตนเองแล้ว',
            'physical_exam' => 'รู้สึกตัวดี สัญญาณชีพคงที่ ตรวจร่างกายไม่พบภาวะฉุกเฉิน',
            'assessment' => 'โรคเดิมอยู่ระหว่างติดตาม ประเมินความเสี่ยงและปรับแผนรักษาตามอาการ',
            'procedure_note' => '-',
            'medication_orders' => 'ให้ยาตามแผนเดิม/ปรับตามผลตรวจ',
            'disposition' => 'นัดติดตาม',
            'triage_level' => 'ไม่เร่งด่วน',
            'risk' => $risk,
            'risk_level' => $risk,
            'risk_score' => $score,
            'hn' => $patient['hn'],
            'full_name' => $patient['full_name'],
            'visit_type' => ($patient['care_area'] ?? 'OPD') === 'IPD' ? 'IPD' : 'OPD',
            'visit_reason' => 'ติดตามอาการและประเมินผลการรักษาล่าสุด',
            'care_area' => $patient['care_area'] ?? 'OPD',
            'hospital' => $patient['hospital'] ?? 'โรงพยาบาลขอนแก่น',
            'payment_method' => 'บัตร 30 บาท / UC',
            'insurance_detail' => 'สิทธิหลักประกันสุขภาพแห่งชาติ',
            'blood_group' => 'O+',
            'weight_kg' => 68,
            'height_cm' => 165,
            'temperature' => 36.8,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 98,
            'alcohol_use' => 'ไม่ดื่ม',
            'smoking_status' => 'ไม่สูบ',
            'has_surgery' => 'ไม่มี',
            'surgery_type' => '-',
            'surgery_note' => '-',
            'has_menstruation' => ($patient['gender'] ?? '') === 'หญิง' ? 'มี/สอบถามแล้ว' : 'ไม่เกี่ยวข้อง',
            'last_menstrual_period' => ($patient['gender'] ?? '') === 'หญิง' ? '2026-05-01' : null,
            'investigations' => 'ตรวจเลือด, ตรวจปัสสาวะ, X-ray',
            'lab_results' => 'CBC, FBS, HbA1c, Lipid profile ส่งตรวจ/ติดตามผล',
            'urine_results' => 'Urinalysis ไม่มีภาวะฉุกเฉินเด่นชัด',
            'xray_results' => 'X-ray ตามข้อบ่งชี้ทางคลินิก',
            'mri_results' => '-',
            'imaging_results' => 'ผลภาพถ่ายทางการแพทย์ประกอบการวินิจฉัย',
            'doctor_education' => 'ให้ความรู้เรื่องการใช้ยา การสังเกตอาการผิดปกติ การมาตามนัด และช่องทางติดต่อโรงพยาบาล',
            'next_appointment_detail' => 'นัดหมายเพิ่มเติมตามแผนรักษา หากมีไข้ หอบ เจ็บหน้าอก หรืออาการแย่ลงให้พบแพทย์ทันที',
            'followup_date' => '2026-06-12',
            'systolic' => 148,
            'diastolic' => 92,
            'pulse' => 78,
            'glucose' => 142,
            'hba1c' => 7.8,
            'bmi' => 27.4,
            'cholesterol' => 218,
        ],
        [
            'id' => 2,
            'date' => '10 เม.ย. 2026',
            'visit_date' => '2026-04-10',
            'title' => 'ติดตามผล Lab / รับยา',
            'doctor' => 'พญ.ณิชา ศรีแพทย์',
            'doctor_name' => 'พญ.ณิชา ศรีแพทย์',
            'diagnosis' => 'Follow up ' . ($patient['disease'] ?? ''),
            'treatment_plan' => 'รับยาเดิม ติดตามผลตรวจ และประเมินอาการซ้ำ',
            'summary' => 'อาการทั่วไปคงที่ นัดติดตามตามแผนการรักษา',
            'risk' => $risk === 'High' ? 'Medium' : 'Low',
            'risk_level' => $risk === 'High' ? 'Medium' : 'Low',
            'risk_score' => max(20, $score - 14),
            'hn' => $patient['hn'],
            'full_name' => $patient['full_name'],
            'visit_type' => 'Follow up',
            'visit_reason' => 'ติดตามผลตรวจเดิมและรับยา',
            'care_area' => 'OPD',
            'hospital' => $patient['hospital'] ?? 'โรงพยาบาลขอนแก่น',
            'payment_method' => 'ประกันสังคม',
            'insurance_detail' => 'ใช้สิทธิประกันสังคมตามโรงพยาบาลที่ระบุ',
            'blood_group' => 'O+',
            'weight_kg' => 68,
            'height_cm' => 165,
            'temperature' => 36.7,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 98,
            'alcohol_use' => 'ดื่มเป็นครั้งคราว',
            'smoking_status' => 'ไม่สูบ',
            'has_surgery' => 'ไม่มี',
            'surgery_type' => '-',
            'surgery_note' => '-',
            'has_menstruation' => ($patient['gender'] ?? '') === 'หญิง' ? 'มี/สอบถามแล้ว' : 'ไม่เกี่ยวข้อง',
            'last_menstrual_period' => ($patient['gender'] ?? '') === 'หญิง' ? '2026-04-03' : null,
            'investigations' => 'ตรวจเลือด, ตรวจปัสสาวะ',
            'lab_results' => 'ตรวจเลือดติดตามโรคประจำตัว',
            'urine_results' => 'ตรวจปัสสาวะตามแผน',
            'xray_results' => '-',
            'mri_results' => '-',
            'imaging_results' => '-',
            'doctor_education' => 'ทบทวนการรับประทานยา อาหาร การออกกำลังกาย และการมาตามนัด',
            'next_appointment_detail' => 'นัดติดตามผลตรวจครั้งถัดไป',
            'followup_date' => '2026-05-27',
            'systolic' => 136,
            'diastolic' => 84,
            'pulse' => 76,
            'glucose' => 126,
            'hba1c' => 7.1,
            'bmi' => 26.8,
            'cholesterol' => 202,
        ],
    ];

    return array_merge($savedItems, $items);
}

function demo_documents(?string $hn = null): array
{
    $patient = demo_patient($hn);
    return [
        ['id'=>1,'title'=>'สรุปการรักษา - ' . $patient['hn'],'date'=>'27 พ.ค. 2026','type'=>'PDF','document_type'=>'PDF','status'=>'พร้อมเปิดดู'],
        ['id'=>2,'title'=>'ผลตรวจเลือด','date'=>'10 เม.ย. 2026','type'=>'PDF','document_type'=>'PDF','status'=>'พร้อมเปิดดู'],
        ['id'=>3,'title'=>'ใบนัดหมาย','date'=>(string) ($patient['next_appointment'] ?? '12 มิ.ย. 2026'),'type'=>'PDF','document_type'=>'PDF','status'=>'พร้อมเปิดดู'],
    ];
}

function demo_referrals(): array
{
    $items = $_SESSION['demo_referrals'] ?? [];
    if (is_array($items) && !empty($items)) {
        return $items;
    }

    return [
        ['id'=>1,'hn'=>'HN0002','patient_name'=>'สมหญิง สุขใจ','from_department'=>'อายุรกรรมโรคไต','to_department'=>'เวชบำบัดวิกฤต','to_doctor'=>'นพ.ธนดล วัฒนกุล','to_hospital'=>'โรงพยาบาลศรีนครินทร์','urgency'=>'ด่วน','reason'=>'ไตวายเฉียบพลันร่วมกับความดันสูง ต้องประเมิน ICU','status'=>'รอรับเคส','created_at'=>'2026-05-28 10:30:00'],
        ['id'=>2,'hn'=>'HN0004','patient_name'=>'ปรียา วงศ์สวัสดิ์','from_department'=>'ศัลยกรรมทั่วไป','to_department'=>'ศัลยกรรมทางเดินอาหาร','to_doctor'=>'พญ.ณิชา ศรีแพทย์','to_hospital'=>'โรงพยาบาลราชวิถี','urgency'=>'ปกติ','reason'=>'ส่งต่อเพื่อวางแผนผ่าตัดถุงน้ำดี','status'=>'นัดหมายแล้ว','created_at'=>'2026-05-28 11:15:00'],
    ];
}

function demo_tickets(): array
{
    return [
        ['id'=>1,'user_role'=>'patient','user_name'=>'สมชาย ใจดี','subject'=>'วันนัดไม่ตรงกับเอกสาร','message'=>'ขอให้ตรวจสอบวันนัดหมายในระบบ','status'=>'open'],
        ['id'=>2,'user_role'=>'doctor','user_name'=>'นพ.กิตติ ภัทรเวช','subject'=>'เปิดเอกสาร PDF ไม่ได้','message'=>'ไม่สามารถเปิดไฟล์เอกสารผู้ป่วยได้','status'=>'closed'],
    ];
}

function demo_visit_types(): array
{
    return [
        'OPD', 'IPD', 'Follow up', 'Emergency', 'Refer',
        'Pre-op', 'Post-op', 'Telemedicine', 'Home care', 'Procedure clinic'
    ];
}

function demo_care_areas(): array
{
    return ['OPD', 'IPD', 'ICU', 'ผ่าตัด', 'คิวผ่าตัด', 'คนไข้เฝ้าระวังสูง'];
}

function demo_payment_methods(): array
{
    return ['เงินสด', 'ประกันส่วนตัว', 'บัตร 30 บาท / UC', 'บัตรประกันสังคม', 'ราชการ', 'รัฐวิสาหกิจ'];
}

function demo_blood_groups(): array
{
    return ['ไม่ทราบ', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

function demo_investigation_options(): array
{
    return [
        'CBC' => 'Complete blood count',
        'Blood chemistry' => 'FBS, BUN/Cr, Electrolyte, LFT',
        'HbA1c / Lipid' => 'เบาหวานและไขมัน',
        'Coagulation' => 'PT, INR, aPTT ก่อนผ่าตัด/เลือดออกง่าย',
        'Cardiac marker' => 'Troponin, CK-MB ตามข้อบ่งชี้',
        'ตรวจปัสสาวะ' => 'Urinalysis / Urine protein / Pregnancy test',
        'Pregnancy test' => 'UPT / β-hCG',
        'X-ray' => 'Chest X-ray / Plain film ตามข้อบ่งชี้',
        'CT Scan' => 'CT brain/chest/abdomen ตามข้อบ่งชี้',
        'MRI' => 'MRI brain/spine/joint ตามข้อบ่งชี้',
        'Ultrasound' => 'Ultrasound abdomen/OB/soft tissue',
        'EKG' => 'คลื่นไฟฟ้าหัวใจ',
        'Echo' => 'Echocardiogram',
        'Endoscopy' => 'EGD / Colonoscopy / Bronchoscopy',
        'Pathology' => 'ชิ้นเนื้อ/เซลล์วิทยา',
        'Culture' => 'เพาะเชื้อ/ความไวต่อยา',
        'Other' => 'ตรวจอื่น ๆ ตามข้อบ่งชี้',
    ];
}

function demo_emr_dispositions(): array
{
    return ['กลับบ้าน', 'นัดติดตาม', 'Admit IPD', 'ส่ง ER', 'ส่งต่อแผนกอื่น', 'ส่งต่อโรงพยาบาลอื่น', 'เข้าห้องผ่าตัด', 'ICU', 'เสียชีวิต'];
}

function demo_triage_levels(): array
{
    return ['ไม่เร่งด่วน', 'เร่งด่วนเล็กน้อย', 'เร่งด่วน', 'ฉุกเฉิน', 'วิกฤต'];
}

function demo_allergy_types(): array
{
    return ['ไม่มีประวัติแพ้', 'แพ้ยา', 'แพ้อาหาร', 'แพ้วัคซีน', 'แพ้สารทึบรังสี/Contrast', 'แพ้อื่น ๆ'];
}

function usemed_visit_field(array $visit, string $key, string $default = '-'): string
{
    $value = $visit[$key] ?? $default;
    if ($value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function usemed_table_columns(string $table): array
{
    if (!db_is_connected()) {
        return [];
    }

    $rows = db_fetch_all('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
    $columns = [];
    foreach ($rows as $row) {
        if (!empty($row['Field'])) {
            $columns[] = (string) $row['Field'];
        }
    }
    return $columns;
}

function usemed_insert_available(string $table, array $data): bool
{
    $columns = usemed_table_columns($table);
    if (empty($columns)) {
        return false;
    }

    $filtered = [];
    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    if (empty($filtered)) {
        return false;
    }

    $names = array_keys($filtered);
    $sql = 'INSERT INTO `' . str_replace('`', '', $table) . '` (`' . implode('`,`', $names) . '`) VALUES (:' . implode(',:', $names) . ')';
    return db_execute($sql, $filtered);
}

function usemed_ensure_extended_schema(): void
{
    if (!db_is_connected()) {
        return;
    }

    db_execute("ALTER TABLE patients ADD COLUMN care_area VARCHAR(80) DEFAULT 'OPD'");
    db_execute("ALTER TABLE patients ADD COLUMN hospital VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN ward VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN surgery_status VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN high_watch TINYINT(1) DEFAULT 0");
    db_execute("ALTER TABLE patients ADD COLUMN blood_group VARCHAR(20) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN payment_method VARCHAR(100) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN insurance_detail VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN department VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN risk_level VARCHAR(50) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN risk_score INT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN admission_date DATE DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN expected_discharge_date VARCHAR(50) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN discharge_date DATE DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN additional_medication TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN operation_name VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN operation_date DATE DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN operation_status VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN operation_size VARCHAR(100) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN icu_day VARCHAR(80) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN icu_daily_note TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN ventilator_status VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN vasopressor_status VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN fluid_balance VARCHAR(100) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN line_tube_status VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN followup_plan TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN discharge_plan TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN daily_note TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN monitoring_frequency VARCHAR(120) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN escalation_plan TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN last_round_date DATETIME DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN email VARCHAR(255) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN id_card VARCHAR(30) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN birth_date DATE DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN allergy_history TEXT DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN registration_source VARCHAR(80) DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN registration_status VARCHAR(80) DEFAULT 'active'");
    db_execute("ALTER TABLE patients ADD COLUMN consent_accepted_at DATETIME DEFAULT NULL");
    db_execute("ALTER TABLE patients ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

    db_execute("ALTER TABLE doctors ADD COLUMN hospital VARCHAR(255) DEFAULT NULL");

    $visitColumns = [
        "visit_type VARCHAR(80) DEFAULT NULL",
        "visit_reason TEXT DEFAULT NULL",
        "care_area VARCHAR(80) DEFAULT NULL",
        "hospital VARCHAR(255) DEFAULT NULL",
        "payment_method VARCHAR(100) DEFAULT NULL",
        "insurance_detail VARCHAR(255) DEFAULT NULL",
        "blood_group VARCHAR(20) DEFAULT NULL",
        "weight_kg DECIMAL(6,2) DEFAULT NULL",
        "height_cm DECIMAL(6,2) DEFAULT NULL",
        "temperature DECIMAL(4,1) DEFAULT NULL",
        "respiratory_rate INT DEFAULT NULL",
        "oxygen_saturation INT DEFAULT NULL",
        "alcohol_use VARCHAR(80) DEFAULT NULL",
        "smoking_status VARCHAR(80) DEFAULT NULL",
        "has_surgery VARCHAR(30) DEFAULT NULL",
        "surgery_type VARCHAR(80) DEFAULT NULL",
        "surgery_note TEXT DEFAULT NULL",
        "has_menstruation VARCHAR(50) DEFAULT NULL",
        "last_menstrual_period DATE DEFAULT NULL",
        "investigations TEXT DEFAULT NULL",
        "lab_results TEXT DEFAULT NULL",
        "urine_results TEXT DEFAULT NULL",
        "xray_results TEXT DEFAULT NULL",
        "mri_results TEXT DEFAULT NULL",
        "imaging_results TEXT DEFAULT NULL",
        "doctor_education TEXT DEFAULT NULL",
        "next_appointment_detail TEXT DEFAULT NULL",
        "followup_date DATE DEFAULT NULL",
        "chief_complaint TEXT DEFAULT NULL",
        "present_illness TEXT DEFAULT NULL",
        "review_of_systems TEXT DEFAULT NULL",
        "past_history TEXT DEFAULT NULL",
        "past_surgical_history TEXT DEFAULT NULL",
        "allergy_type VARCHAR(120) DEFAULT NULL",
        "allergy_history TEXT DEFAULT NULL",
        "current_medications TEXT DEFAULT NULL",
        "family_history TEXT DEFAULT NULL",
        "social_history TEXT DEFAULT NULL",
        "immunization_history TEXT DEFAULT NULL",
        "pregnancy_status VARCHAR(120) DEFAULT NULL",
        "physical_exam TEXT DEFAULT NULL",
        "physical_exam_general TEXT DEFAULT NULL",
        "physical_exam_heent TEXT DEFAULT NULL",
        "physical_exam_chest_lung TEXT DEFAULT NULL",
        "physical_exam_cvs TEXT DEFAULT NULL",
        "physical_exam_abdomen TEXT DEFAULT NULL",
        "physical_exam_neuro TEXT DEFAULT NULL",
        "physical_exam_extremity TEXT DEFAULT NULL",
        "physical_exam_skin TEXT DEFAULT NULL",
        "provisional_diagnosis TEXT DEFAULT NULL",
        "differential_diagnosis TEXT DEFAULT NULL",
        "final_diagnosis TEXT DEFAULT NULL",
        "icd10_code VARCHAR(50) DEFAULT NULL",
        "assessment TEXT DEFAULT NULL",
        "procedure_name VARCHAR(255) DEFAULT NULL",
        "procedure_note TEXT DEFAULT NULL",
        "anesthesia_type VARCHAR(120) DEFAULT NULL",
        "medication_orders TEXT DEFAULT NULL",
        "nursing_instructions TEXT DEFAULT NULL",
        "consult_request TEXT DEFAULT NULL",
        "disposition VARCHAR(120) DEFAULT NULL",
        "admission_ward VARCHAR(255) DEFAULT NULL",
        "triage_level VARCHAR(80) DEFAULT NULL",
        "red_flags TEXT DEFAULT NULL",
        "consent_status VARCHAR(120) DEFAULT NULL"
    ];

    foreach ($visitColumns as $definition) {
        $name = trim(strtok($definition, ' '));
        db_execute("ALTER TABLE visits ADD COLUMN {$definition}");
    }

    db_execute("ALTER TABLE support_tickets ADD COLUMN problem_type VARCHAR(100) DEFAULT NULL");
    db_execute("ALTER TABLE support_tickets ADD COLUMN menu_path VARCHAR(255) DEFAULT NULL");

    db_execute("CREATE TABLE IF NOT EXISTS referrals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT DEFAULT NULL,
        doctor_id INT DEFAULT NULL,
        from_department VARCHAR(255) DEFAULT NULL,
        to_department VARCHAR(255) NOT NULL,
        to_doctor VARCHAR(255) DEFAULT NULL,
        to_hospital VARCHAR(255) NOT NULL,
        urgency VARCHAR(50) DEFAULT 'ปกติ',
        reason TEXT NOT NULL,
        status VARCHAR(80) DEFAULT 'รอรับเคส',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_referrals_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
        CONSTRAINT fk_referrals_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function usemed_seed_demo_data(): void
{
    if (!db_is_connected()) {
        return;
    }

    usemed_ensure_extended_schema();

    foreach (demo_patients() as $p) {
        $details = usemed_patient_followup_details($p);
        $patientData = [
            'hn' => $p['hn'],
            'password' => $p['password'] ?? '123456',
            'full_name' => $p['full_name'],
            'gender' => $p['gender'] ?? null,
            'age' => (int) ($p['age'] ?? 0),
            'phone' => $p['phone'] ?? null,
            'disease' => $p['disease'] ?? null,
            'address' => $p['address'] ?? null,
            'care_area' => $p['care_area'] ?? 'OPD',
            'hospital' => $p['hospital'] ?? null,
            'ward' => $p['ward'] ?? null,
            'surgery_status' => $p['surgery_status'] ?? null,
            'high_watch' => !empty($p['high_watch']) ? 1 : 0,
            'department' => $p['department'] ?? null,
            'risk_level' => $p['risk_level'] ?? null,
            'risk_score' => $p['risk_score'] ?? null,
            'admission_date' => ($details['admission_date'] ?? '') !== '' ? $details['admission_date'] : null,
            'expected_discharge_date' => $details['expected_discharge_date'] ?? null,
            'discharge_date' => ($details['discharge_date'] ?? '') !== '' ? $details['discharge_date'] : null,
            'additional_medication' => $details['additional_medication'] ?? null,
            'operation_name' => $details['operation_name'] ?? null,
            'operation_date' => ($details['operation_date'] ?? '') !== '' ? $details['operation_date'] : null,
            'operation_status' => $details['operation_status'] ?? null,
            'operation_size' => $details['operation_size'] ?? null,
            'icu_day' => $details['icu_day'] ?? null,
            'icu_daily_note' => $details['icu_daily_note'] ?? null,
            'ventilator_status' => $details['ventilator_status'] ?? null,
            'vasopressor_status' => $details['vasopressor_status'] ?? null,
            'fluid_balance' => $details['fluid_balance'] ?? null,
            'line_tube_status' => $details['line_tube_status'] ?? null,
            'followup_plan' => $details['followup_plan'] ?? null,
            'discharge_plan' => $details['discharge_plan'] ?? null,
            'daily_note' => $details['daily_note'] ?? null,
            'monitoring_frequency' => $details['monitoring_frequency'] ?? null,
            'escalation_plan' => $details['escalation_plan'] ?? null,
            'last_round_date' => str_replace(' ', ' ', (string) ($details['last_round_date'] ?? '')) ?: null,
        ];

        $columns = usemed_table_columns('patients');
        $filtered = [];
        foreach ($patientData as $key => $value) {
            if (in_array($key, $columns, true)) {
                $filtered[$key] = $value;
            }
        }
        if (!empty($filtered)) {
            $names = array_keys($filtered);
            $updateParts = [];
            foreach ($names as $name) {
                if ($name !== 'hn') {
                    $updateParts[] = '`' . $name . '` = VALUES(`' . $name . '`)';
                }
            }
            $sql = 'INSERT INTO patients (`' . implode('`,`', $names) . '`) VALUES (:' . implode(',:', $names) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);
            db_execute($sql, $filtered);
        }
    }

    foreach (demo_doctors() as $d) {
        db_execute(
            'INSERT INTO doctors (username, password, full_name, license_no, department, hospital)
             VALUES (:username, :password, :full_name, :license_no, :department, :hospital)
             ON DUPLICATE KEY UPDATE
                password = VALUES(password), full_name = VALUES(full_name), license_no = VALUES(license_no),
                department = VALUES(department), hospital = VALUES(hospital)',
            [
                'username' => $d['username'],
                'password' => $d['password'] ?? '123456',
                'full_name' => $d['full_name'],
                'license_no' => $d['license_no'] ?? null,
                'department' => $d['department'] ?? null,
                'hospital' => $d['hospital'] ?? null,
            ]
        );
    }
}

function render_empty_state(string $title, string $message): void
{
    ?>
    <div class="empty-state">
        <div class="empty-icon">🗂️</div>
        <h3><?= e($title) ?></h3>
        <p><?= e($message) ?></p>
    </div>
    <?php
}