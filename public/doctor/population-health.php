<?php
// public/doctor/population-health.php
// USE MED - AI Population Health Management Command Center

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_once __DIR__ . '/../../backend/shared/ai_engine.php';

require_login('doctor');
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
usemed_ensure_extended_schema();
usemed_seed_demo_data();
usemed_ai_ensure_population_schema();

function phm_text(array $row, string $key, string $fallback = '-'): string
{
    $value = $row[$key] ?? null;
    if ($value === null || $value === '') {
        return $fallback;
    }
    return (string) $value;
}

function phm_float($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    return is_numeric($value) ? (float) $value : null;
}

function phm_fast_assessment(array $p): array
{
    $score = (int) ($p['risk_score'] ?? 0);
    $age = (int) ($p['age'] ?? 0);
    $area = (string) ($p['care_area'] ?? 'OPD');
    $disease = mb_strtolower((string) ($p['disease'] ?? ''), 'UTF-8');

    if ($score <= 0) {
        $score = 20;
        if ($age >= 75) { $score += 18; }
        elseif ($age >= 60) { $score += 10; }
        if (str_contains($disease, 'diabetes') || str_contains($disease, 'gdm') || str_contains($disease, 'เบาหวาน')) { $score += 12; }
        if (str_contains($disease, 'hypertension') || str_contains($disease, 'ความดัน')) { $score += 10; }
        if (str_contains($disease, 'ckd') || str_contains($disease, 'ไต')) { $score += 12; }
        if (str_contains($disease, 'stroke') || str_contains($disease, 'nstemi') || str_contains($disease, 'copd')) { $score += 14; }
        if ($area === 'ICU') { $score += 24; }
        elseif ($area === 'IPD') { $score += 14; }
        if (!empty($p['high_watch'])) { $score += 18; }
    }

    $score = max(0, min(100, $score));
    $recommendation = function_exists('usemed_population_recommendation')
        ? usemed_population_recommendation(array_merge($p, ['risk_score' => $score]))
        : ['priority' => 'P3', 'level' => 'ติดตามตามรอบ', 'reasons' => [], 'actions' => []];

    $tags = [];
    if (str_contains($disease, 'diabetes') || str_contains($disease, 'gdm') || str_contains($disease, 'เบาหวาน')) { $tags[] = 'เบาหวาน'; }
    if (str_contains($disease, 'hypertension') || str_contains($disease, 'ความดัน')) { $tags[] = 'ความดัน'; }
    if (str_contains($disease, 'ckd') || str_contains($disease, 'ไต')) { $tags[] = 'ไต'; }

    $priority = (string) ($recommendation['priority'] ?? 'P3');
    $sla = $priority === 'P1' ? 'วันนี้' : ($priority === 'P2' ? 'ภายใน 3 วัน' : 'ตามนัด');
    $badge = $priority === 'P1' ? 'red' : ($priority === 'P2' ? 'orange' : 'green');
    $reasons = [];
    foreach ((array) ($recommendation['reasons'] ?? []) as $reason) {
        $reasons[] = [
            'type' => 'overview',
            'text' => (string) $reason,
            'source_feature' => 'patients',
            'source_value' => '',
            'source_table' => 'patients',
            'weight' => 0,
        ];
    }

    return [
        'score' => $score,
        'priority' => $priority,
        'level' => (string) ($recommendation['level'] ?? 'ติดตามตามรอบ'),
        'sla' => $sla,
        'badge' => $badge,
        'reasons_detail' => $reasons,
        'actions' => array_values(array_unique((array) ($recommendation['actions'] ?? []))),
        'features' => [
            'age' => $age,
            'gender' => (string) ($p['gender'] ?? ''),
            'disease_text' => (string) ($p['disease'] ?? ''),
            'disease_key' => $disease,
            'care_area' => $area,
            'high_watch' => !empty($p['high_watch']),
            'patient_risk_score' => $score,
            'systolic' => phm_float($p['systolic'] ?? null),
            'diastolic' => phm_float($p['diastolic'] ?? null),
            'hba1c' => phm_float($p['hba1c'] ?? null),
            'bmi' => phm_float($p['bmi'] ?? null),
            'bp_high_recent_count' => 0,
            'recent_visit_180d' => 0,
            'smoking_status' => (string) ($p['smoking_status'] ?? ''),
            'alcohol_use' => (string) ($p['alcohol_use'] ?? ''),
        ],
        'cohort_tags' => array_values(array_unique($tags)),
        'trajectory_status' => $priority === 'P1' ? 'ต้องติดตามเร่งด่วน' : ($priority === 'P2' ? 'ควรติดตามระยะสั้น' : 'คงที่/ติดตามตามรอบ'),
        'model_version' => 'usemed-pop-health-fast-v1',
    ];
}

function phm_cached_ml_assessment(array $p): ?array
{
    if (!db_is_connected()) {
        return null;
    }

    $patientId = (int) ($p['id'] ?? 0);
    $hn = (string) ($p['hn'] ?? '');
    $params = [];
    $where = [];
    if ($patientId > 0) {
        $where[] = 'patient_id = :patient_id';
        $params['patient_id'] = $patientId;
    }
    if ($hn !== '') {
        $where[] = 'hn = :hn';
        $params['hn'] = $hn;
    }
    if (!$where) {
        return null;
    }

    $score = db_fetch_one(
        'SELECT * FROM ai_population_scores WHERE ' . implode(' OR ', $where) . ' ORDER BY calculated_at DESC LIMIT 1',
        $params
    );
    if (!$score || !str_starts_with((string) ($score['model_version'] ?? ''), 'usemed-xgb')) {
        return null;
    }

    $reasons = db_fetch_all(
        'SELECT reason_type, reason_text, source_feature, source_value, source_table, contribution
         FROM ai_population_reasons
         WHERE patient_id = :patient_id OR hn = :hn
         ORDER BY contribution DESC, id ASC
         LIMIT 8',
        ['patient_id' => (int) ($score['patient_id'] ?? 0), 'hn' => (string) ($score['hn'] ?? '')]
    );
    $reasonRows = [];
    foreach ($reasons as $reason) {
        $reasonRows[] = [
            'type' => (string) ($reason['reason_type'] ?? 'ml_factor'),
            'text' => (string) ($reason['reason_text'] ?? ''),
            'source_feature' => (string) ($reason['source_feature'] ?? 'ml_model'),
            'source_value' => (string) ($reason['source_value'] ?? ''),
            'source_table' => (string) ($reason['source_table'] ?? 'ml_prediction'),
            'weight' => (int) ($reason['contribution'] ?? 0),
        ];
    }

    $priority = (string) ($score['priority_level'] ?? 'P3');
    $featureSnapshot = json_decode((string) ($score['feature_snapshot'] ?? '{}'), true);
    $actions = array_values(array_filter(array_map('trim', explode("\n", (string) ($score['recommendation_summary'] ?? '')))));
    $tags = array_values(array_filter(array_map('trim', explode(',', (string) ($score['cohort_tags'] ?? '')))));

    return [
        'score' => (int) ($score['risk_score'] ?? 0),
        'priority' => $priority,
        'level' => (string) ($score['priority_label'] ?? $priority),
        'sla' => (string) ($score['recommended_sla'] ?? ''),
        'badge' => $priority === 'P1' ? 'red' : ($priority === 'P2' ? 'orange' : 'green'),
        'reasons_detail' => $reasonRows,
        'actions' => $actions,
        'features' => is_array($featureSnapshot) ? $featureSnapshot : [],
        'cohort_tags' => $tags,
        'trajectory_status' => (string) ($score['trajectory_status'] ?? ''),
        'model_version' => (string) ($score['model_version'] ?? 'usemed-xgb-agent-v1'),
    ];
}

function phm_assessment(array $p): array
{
    static $cache = [];
    $key = (string) ($p['id'] ?? $p['hn'] ?? md5(json_encode($p)));
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $cachedMl = phm_cached_ml_assessment($p);
    if ($cachedMl) {
        return $cache[$key] = $cachedMl;
    }

    if (!isset($_GET['deep_ai']) || (string) $_GET['deep_ai'] !== '1') {
        return $cache[$key] = phm_fast_assessment($p);
    }

    $persist = isset($_GET['refresh_ai']) && (string) $_GET['refresh_ai'] === '1';
    $ai = usemed_ai_score_patient($p, $persist);
    $cache[$key] = [
        'score' => (int) ($ai['score'] ?? 0),
        'priority' => (string) ($ai['priority'] ?? 'P3'),
        'level' => (string) ($ai['level'] ?? 'ติดตามตามรอบ'),
        'sla' => (string) ($ai['sla'] ?? 'ตามนัด'),
        'badge' => (string) ($ai['badge'] ?? 'green'),
        'reasons_detail' => (array) ($ai['reasons'] ?? []),
        'actions' => array_values(array_unique((array) ($ai['actions'] ?? []))),
        'features' => (array) ($ai['features'] ?? []),
        'cohort_tags' => (array) ($ai['cohort_tags'] ?? []),
        'trajectory_status' => (string) ($ai['trajectory_status'] ?? 'คงที่/ติดตามตามรอบ'),
        'model_version' => (string) ($ai['model_version'] ?? 'usemed-pop-health-rule-v1'),
    ];
    return $cache[$key];
}

function phm_score(array $p): int
{
    return (int) (phm_assessment($p)['score'] ?? 0);
}

function phm_reason_html(array $reason): string
{
    $text = e((string) ($reason['text'] ?? '-'));
    $feature = e((string) ($reason['source_feature'] ?? ''));
    $value = e((string) ($reason['source_value'] ?? ''));
    $source = e((string) ($reason['source_table'] ?? ''));
    $weight = (int) ($reason['weight'] ?? $reason['contribution'] ?? 0);

    $meta = [];
    if ($feature !== '') { $meta[] = 'feature: ' . $feature; }
    if ($value !== '' && $value !== '-') { $meta[] = 'value: ' . $value; }
    if ($source !== '') { $meta[] = 'source: ' . $source; }
    if ($weight > 0) { $meta[] = '+' . $weight; }

    return '<li><span>' . $text . '</span>' . (!empty($meta) ? '<small>' . e(implode(' · ', $meta)) . '</small>' : '') . '</li>';
}

function phm_days_in_hospital(array $p): string
{
    $date = $p['admission_date'] ?? null;
    if (!$date) {
        return '-';
    }
    try {
        $start = new DateTime((string) $date);
        $now = new DateTime('today');
        return (string) max(0, (int) $start->diff($now)->format('%a')) . ' วัน';
    } catch (Throwable $e) {
        return '-';
    }
}

function phm_default_owner(array $p, array $a): string
{
    $tags = (array) ($a['cohort_tags'] ?? []);
    $area = (string) ($p['care_area'] ?? 'OPD');
    if ($area === 'ICU') { return 'ทีม ICU / แพทย์เจ้าของไข้'; }
    if ($area === 'IPD') { return 'ทีม Ward / Case manager'; }
    if (in_array('เบาหวาน', $tags, true) || in_array('ความดัน', $tags, true)) { return 'ทีม NCD / พยาบาลติดตาม'; }
    return 'แพทย์เจ้าของไข้';
}

function phm_task_for_patient(array $p): ?array
{
    if (!db_is_connected() || empty($p['id'])) {
        return null;
    }
    return db_fetch_one(
        "SELECT * FROM followup_tasks WHERE patient_id = :patient_id ORDER BY ARRAY_POSITION(ARRAY['รอติดตาม','โทรแล้ว','นัดแล้ว','Lab แล้ว','ส่งต่อแล้ว','ปิดเคสแล้ว'], status), due_date ASC, id DESC LIMIT 1",
        ['patient_id' => (int) $p['id']]
    );
}

function phm_due_text(?array $task, array $a): string
{
    if ($task && !empty($task['due_date'])) {
        return (string) $task['due_date'];
    }
    return (string) ($a['sla'] ?? 'ตามนัด');
}

function phm_status_text(?array $task): string
{
    if ($task && !empty($task['status'])) {
        return (string) $task['status'];
    }
    return 'รอดำเนินการ';
}

function phm_matches_filters(array $p, array $a, array $filters): bool
{
    $disease = mb_strtolower((string) ($p['disease'] ?? ''), 'UTF-8');
    $gender = (string) ($p['gender'] ?? '');
    $area = (string) ($p['care_area'] ?? 'OPD');
    $hospital = (string) ($p['hospital'] ?? '');
    $department = (string) ($p['department'] ?? '');
    $payment = (string) ($p['payment_method'] ?? '');
    $age = (int) ($p['age'] ?? 0);
    $bmi = phm_float($p['bmi'] ?? ($a['features']['bmi'] ?? null));
    $score = (int) ($a['score'] ?? 0);
    $tags = (array) ($a['cohort_tags'] ?? []);

    $cohort = $filters['cohort'];
    if ($cohort === 'diabetes' && !(str_contains($disease, 'diabetes') || str_contains($disease, 'เบาหวาน') || in_array('เบาหวาน', $tags, true))) { return false; }
    if ($cohort === 'hypertension' && !(str_contains($disease, 'hypertension') || str_contains($disease, 'ความดัน') || in_array('ความดัน', $tags, true))) { return false; }
    if ($cohort === 'dmht' && !(in_array('เบาหวาน', $tags, true) && in_array('ความดัน', $tags, true))) { return false; }
    if ($cohort === 'elderly' && $age < 60) { return false; }
    if ($cohort === 'bmi' && ($bmi === null || $bmi < 25)) { return false; }
    if ($cohort === 'high' && $score < 70 && empty($p['high_watch'])) { return false; }
    if ($cohort === 'ipdicu' && !in_array($area, ['IPD', 'ICU'], true)) { return false; }
    if ($cohort === 'missed' && (int)($p['missed_appointment_count'] ?? 0) <= 0) { return false; }

    if ($filters['age'] === '0-39' && !($age < 40)) { return false; }
    if ($filters['age'] === '40-59' && !($age >= 40 && $age <= 59)) { return false; }
    if ($filters['age'] === '60+' && !($age >= 60)) { return false; }

    if ($filters['gender'] !== 'all' && $gender !== $filters['gender']) { return false; }
    if ($filters['area'] !== 'all' && $area !== $filters['area']) { return false; }
    if ($filters['hospital'] !== 'all' && $hospital !== $filters['hospital']) { return false; }
    if ($filters['department'] !== 'all' && $department !== $filters['department']) { return false; }
    if ($filters['payment'] !== 'all' && $payment !== $filters['payment']) { return false; }
    if (($filters['priority'] ?? 'all') !== 'all' && (string)($a['priority'] ?? '') !== (string)$filters['priority']) { return false; }

    return true;
}

function phm_avg(array $values): string
{
    $values = array_values(array_filter($values, fn($v) => $v !== null && $v !== ''));
    if (!$values) {
        return '-';
    }
    return (string) round(array_sum(array_map('floatval', $values)) / count($values), 1);
}

function phm_percent(int $num, int $den): string
{
    if ($den <= 0) {
        return '0%';
    }
    return (string) round(($num / $den) * 100) . '%';
}

function phm_sparkline_svg(array $points, string $tone = 'purple'): string
{
    $points = array_values(array_map('floatval', $points));
    if (!$points) { $points = [1, 1, 1, 1]; }
    $min = min($points);
    $max = max($points);
    $range = max(1.0, $max - $min);
    $w = 180;
    $h = 46;
    $pad = 5;
    $n = max(1, count($points) - 1);
    $pairs = [];
    foreach ($points as $i => $v) {
        $x = $pad + ($i / $n) * ($w - $pad * 2);
        $y = $h - $pad - (($v - $min) / $range) * ($h - $pad * 2);
        $pairs[] = round($x, 1) . ',' . round($y, 1);
    }
    $colors = [
        'purple' => ['#7c3aed', 'rgba(124,58,237,.12)'],
        'pink' => ['#ec4899', 'rgba(236,72,153,.12)'],
        'green' => ['#10b981', 'rgba(16,185,129,.12)'],
        'blue' => ['#2563eb', 'rgba(37,99,235,.12)'],
        'teal' => ['#0891b2', 'rgba(8,145,178,.12)'],
    ];
    [$stroke, $fill] = $colors[$tone] ?? $colors['purple'];
    $area = '5,46 ' . implode(' ', $pairs) . ' 175,46';
    return '<svg class="sparkline" viewBox="0 0 180 46" aria-hidden="true" focusable="false">'
        . '<polyline points="' . e($area) . '" fill="' . e($fill) . '" stroke="none"></polyline>'
        . '<polyline points="' . e(implode(' ', $pairs)) . '" fill="none" stroke="' . e($stroke) . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>'
        . '</svg>';
}

function phm_cohort_stats(string $key, string $label, array $patients): array
{
    $items = [];
    foreach ($patients as $p) {
        $a = phm_assessment($p);
        $tags = (array) ($a['cohort_tags'] ?? []);
        $d = mb_strtolower((string) ($p['disease'] ?? ''), 'UTF-8');
        $bmi = phm_float($p['bmi'] ?? ($a['features']['bmi'] ?? null));
        $age = (int) ($p['age'] ?? 0);
        $include = match ($key) {
            'elderly' => $age >= 60,
            'bmi30' => $bmi !== null && $bmi >= 30,
            'male' => ($p['gender'] ?? '') === 'ชาย',
            'female' => ($p['gender'] ?? '') === 'หญิง',
            'smoke' => str_contains(mb_strtolower((string)($a['features']['smoking_status'] ?? ''), 'UTF-8'), 'สูบ'),
            'drink' => str_contains(mb_strtolower((string)($a['features']['alcohol_use'] ?? ''), 'UTF-8'), 'ดื่ม'),
            'diabetes' => str_contains($d, 'diabetes') || str_contains($d, 'เบาหวาน') || in_array('เบาหวาน', $tags, true),
            'hypertension' => str_contains($d, 'hypertension') || str_contains($d, 'ความดัน') || in_array('ความดัน', $tags, true),
            'dmht' => in_array('เบาหวาน', $tags, true) && in_array('ความดัน', $tags, true),
            'missed' => (int)($p['missed_appointment_count'] ?? 0) > 0,
            'a1c_high' => (float)($a['features']['hba1c'] ?? 0) >= 7,
            'bp_uncontrolled' => (int)($a['features']['bp_high_recent_count'] ?? 0) >= 2 || (float)($a['features']['systolic'] ?? 0) >= 140,
            'readmit' => (int)($a['features']['recent_visit_180d'] ?? 0) >= 3 || ($p['care_area'] ?? '') === 'IPD',
            default => true,
        };
        if ($include) { $items[] = $p; }
    }

    $count = count($items);
    $p1 = count(array_filter($items, fn($p) => phm_assessment($p)['priority'] === 'P1'));
    $hba1cValues = array_map(fn($p) => phm_assessment($p)['features']['hba1c'] ?? null, $items);
    $sbpValues = array_map(fn($p) => phm_assessment($p)['features']['systolic'] ?? null, $items);
    $dbpValues = array_map(fn($p) => phm_assessment($p)['features']['diastolic'] ?? null, $items);
    $missed = count(array_filter($items, fn($p) => (int)($p['missed_appointment_count'] ?? 0) > 0));

    return [
        'key' => $key,
        'label' => $label,
        'count' => $count,
        'p1' => $p1,
        'p1_percent' => phm_percent($p1, $count),
        'hba1c_avg' => phm_avg($hba1cValues),
        'bp_avg' => phm_avg($sbpValues) . '/' . phm_avg($dbpValues),
        'missed' => phm_percent($missed, $count),
        'recommendation' => match ($key) {
            'bmi30' => 'จัดคลินิกลดน้ำหนัก + NCD follow-up และติดตาม BMI รายเดือน',
            'diabetes', 'a1c_high' => 'เร่งทบทวน HbA1c/FBS, adherence และปรับ education เบาหวาน',
            'hypertension', 'bp_uncontrolled' => 'จัด BP follow-up, home BP log และ medication review',
            'elderly' => 'เพิ่มการโทรติดตามและประเมิน fall/adherence/ผู้ดูแล',
            'missed' => 'ตั้งระบบโทรเตือนนัดและติดตามผู้ป่วยขาดนัด',
            'readmit' => 'ทบทวน discharge plan และ readmission prevention',
            default => 'ติดตามตามแผน cohort และดูผู้ป่วย P1 ก่อน',
        },
    ];
}

$patients = demo_patients();
$dbTotalPatients = null;
if (db_is_connected()) {
    $countRow = db_fetch_one('SELECT COUNT(*) AS total FROM patients');
    $dbTotalPatients = isset($countRow['total']) ? (int) $countRow['total'] : null;
    $rows = db_fetch_all('SELECT * FROM patients ORDER BY high_watch DESC, risk_score DESC, id ASC LIMIT 500');
    if ($rows) {
        $patients = $rows;
    }
}

$filters = [
    'cohort' => $_GET['cohort'] ?? 'all',
    'age' => $_GET['age'] ?? 'all',
    'gender' => $_GET['gender'] ?? 'all',
    'area' => $_GET['area'] ?? 'all',
    'hospital' => $_GET['hospital'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'payment' => $_GET['payment'] ?? 'all',
    'priority' => $_GET['priority'] ?? 'all',
];

$filtered = [];
foreach ($patients as $p) {
    $a = phm_assessment($p);
    if (phm_matches_filters($p, $a, $filters)) {
        $filtered[] = $p;
    }
}

usort($filtered, function (array $a, array $b): int {
    $order = ['P1' => 1, 'P2' => 2, 'P3' => 3];
    $aa = phm_assessment($a);
    $bb = phm_assessment($b);
    return [$order[$aa['priority']] ?? 9, -$aa['score'], (string)($a['hn'] ?? '')] <=> [$order[$bb['priority']] ?? 9, -$bb['score'], (string)($b['hn'] ?? '')];
});

$totalAll = $dbTotalPatients ?? count($patients);
$total = count($filtered);
$assessments = array_map('phm_assessment', $filtered);
$p1 = count(array_filter($assessments, fn($a) => $a['priority'] === 'P1'));
$p2 = count(array_filter($assessments, fn($a) => $a['priority'] === 'P2'));
$p3 = count(array_filter($assessments, fn($a) => $a['priority'] === 'P3'));
$avgRisk = $total ? round(array_sum(array_map(fn($a) => (int) $a['score'], $assessments)) / $total, 1) : 0;
$ipdIcu = count(array_filter($filtered, fn($p) => in_array(($p['care_area'] ?? 'OPD'), ['IPD', 'ICU'], true)));
$needCall = count(array_filter($filtered, fn($p) => in_array(phm_assessment($p)['priority'], ['P1', 'P2'], true)));
$queueItems = array_slice($filtered, 0, 100);

page_start('Population Health', 'doctor', 'dashboard');
?>
<div style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
    
    <div class="card" style="padding: 24px; margin-bottom: 24px; border-left: 6px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="margin: 0 0 8px; font-size: 28px; color: var(--ink);">AI Population Health</h1>
                <p style="margin: 0; color: var(--muted); font-size: 16px;">ศูนย์ปฏิบัติการข้อมูลประชากรสุขภาพ จัดลำดับความสำคัญผู้ป่วยด้วย AI</p>
            </div>
            <a href="dashboard.php" class="btn secondary">กลับหน้าหลัก</a>
        </div>
    </div>

                    <div class="outcome-step <?= e($step[2]) ?>"><span><?= icon_svg($step[3]) ?></span><small><?= e($step[0]) ?></small><strong><?= e((string)$step[1]) ?></strong></div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section id="cohort-detail" class="phm-panel phm-detail-section">
        <div class="phm-panel-head"><div><span>Cohort Detail</span><h2>วิเคราะห์กลุ่มผู้ป่วยแบบละเอียด</h2></div></div>
        <div class="cohort-detail-grid">
            <?php foreach ($cohortStats as $cs): ?>
                <article>
                    <h3><?= e($cs['label']) ?></h3>
                    <div><span>จำนวน</span><strong><?= e((string)$cs['count']) ?></strong></div>
                    <div><span>P1</span><strong><?= e((string)$cs['p1']) ?> · <?= e($cs['p1_percent']) ?></strong></div>
                    <div><span>HbA1c เฉลี่ย</span><strong><?= e($cs['hba1c_avg']) ?></strong></div>
                    <div><span>BP เฉลี่ย</span><strong><?= e($cs['bp_avg']) ?></strong></div>
                    <p><?= e($cs['recommendation']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <details id="all-patients" class="phm-panel phm-accordion-panel">
        <summary>ดูรายชื่อทั้งหมด + เหตุผลจาก AI Scoring Pipeline</summary>
        <div class="table-wrap mt-1">
            <table class="table" id="phmTable">
                <thead><tr><th>Priority</th><th>ผู้ป่วย</th><th>Risk</th><th>Trajectory</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($filtered as $p): ?>
                    <?php $a = phm_assessment($p); $hn = phm_text($p, 'hn'); ?>
                    <tr>
                        <td><span class="priority-badge <?= e(strtolower($a['priority'])) ?>"><?= e($a['priority']) ?></span><br><small><?= e($a['sla']) ?></small></td>
                        <td><strong><?= e(phm_text($p, 'full_name')) ?></strong><br><span class="text-muted"><?= e($hn) ?> · <?= e(phm_text($p, 'care_area', 'OPD')) ?> · <?= e(phm_text($p, 'disease')) ?></span></td>
                        <td><strong><?= e((string)$a['score']) ?>/100</strong></td>
                        <td><?= e($a['trajectory_status']) ?></td>
                        <td><a class="btn secondary small" href="<?= e(app_url('doctor/add-treatment.php?hn=' . urlencode($hn))) ?>">ติดตาม</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>

    <div class="phm-page-footer">ข้อมูลอัปเดตล่าสุด <?= e(date('d M Y H:i')) ?> · ระบบ AI Population Health</div>
</section>

<?php page_end(); ?>
