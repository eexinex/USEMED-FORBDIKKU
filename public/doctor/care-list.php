<?php
// public/doctor/care-list.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$type = usemed_care_type_key_from_label((string) ($_GET['type'] ?? 'OPD'));
$meta = usemed_care_type_meta($type);
$patients = usemed_get_care_patients($type);
$allTypes = usemed_care_type_map();

$highCount = 0;
$dischargeSoon = 0;
$medUpdate = 0;
foreach ($patients as $p) {
    $f = $p['followup'] ?? usemed_patient_followup_details($p);
    $risk = (string) ($p['risk_level'] ?? '');
    if (!empty($p['high_watch']) || str_contains($risk, 'High') || str_contains($risk, 'สูง')) {
        $highCount++;
    }
    if (($f['expected_discharge_date'] ?? '') !== '' && ($f['expected_discharge_date'] ?? '') !== 'ยังประเมินไม่ได้') {
        $dischargeSoon++;
    }
    if (($f['additional_medication'] ?? '') !== '' && ($f['additional_medication'] ?? '') !== 'ยังไม่มีคำสั่งยาเพิ่ม') {
        $medUpdate++;
    }
}

function care_text(array $patient, string $key, string $default = '-'): string
{
    $f = $patient['followup'] ?? usemed_patient_followup_details($patient);
    $value = $patient[$key] ?? $f[$key] ?? $default;
    if ($value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

page_start('รายชื่อผู้ป่วย ' . $meta['label'], 'doctor', 'icu');

topbar('รายชื่อผู้ป่วย ' . $meta['icon'] . ' ' . $meta['label'], 'ดูชื่อผู้ป่วย รายละเอียด follow up แผนจำหน่าย ยา การผ่าตัด และ ICU รายวัน');
?>

<section class="care-filter-bar">
    <?php foreach ($allTypes as $key => $item): ?>
        <a class="care-filter <?= e($key === $type ? 'active' : '') ?>" href="<?= e(app_url('doctor/care-list.php?type=' . urlencode($key))) ?>">
            <span><?= e($item['icon']) ?></span>
            <strong><?= e($item['label']) ?></strong>
        </a>
    <?php endforeach; ?>
</section>

<section class="stat-grid compact-stats">
    <?php stat_card('จำนวนในรายการ', (string) count($patients), $meta['label']); ?>
    <?php stat_card('เฝ้าระวังสูง', (string) $highCount, 'High Watch'); ?>
    <?php stat_card('มีแผนจำหน่าย/คาดออก', (string) $dischargeSoon, 'Discharge'); ?>
    <?php stat_card('มีคำสั่งยาเพิ่ม', (string) $medUpdate, 'Medication'); ?>
</section>

<section class="table-card mt-2">
    <div class="topbar compact-topbar">
        <div>
            <h1><?= e($meta['label']) ?> Follow-up List</h1>
            <p>กด “บันทึก Follow up” เพื่อเพิ่มการรักษาต่อให้ผู้ป่วยคนนั้นทันที</p>
        </div>
        <div class="searchbar"><input type="search" data-table-search="carePatientTable" placeholder="ค้นหา HN / ชื่อ / ward / โรค..."></div>
    </div>

    <?php if (empty($patients)): ?>
        <?php render_empty_state('ยังไม่มีผู้ป่วยในรายการนี้', 'เมื่อมีผู้ป่วยอยู่ในกลุ่ม ' . $meta['label'] . ' รายชื่อจะแสดงตรงนี้'); ?>
    <?php else: ?>
        <div class="table-wrap care-table-wrap">
            <table class="table care-table" id="carePatientTable">
                <thead>
                    <tr>
                        <th>ผู้ป่วย</th>
                        <th>ตำแหน่ง/สถานะ</th>
                        <th>Follow up สำคัญ</th>
                        <th>วันที่/วันนอน</th>
                        <th>ยา/แผนรักษา</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $p): ?>
                        <?php
                        $f = $p['followup'] ?? usemed_patient_followup_details($p);
                        $hn = (string) ($p['hn'] ?? '');
                        $risk = (string) ($p['risk_level'] ?? ($p['high_watch'] ? 'High' : 'Medium'));
                        ?>
                        <tr>
                            <td class="patient-cell">
                                <strong><?= e($p['full_name'] ?? '-') ?></strong>
                                <span><?= e($hn) ?> · <?= e(($p['age'] ?? '-') . ' ปี') ?> · <?= e($p['gender'] ?? '-') ?></span>
                                <em><?= e($p['disease'] ?? '-') ?></em>
                                <span class="badge <?= e(badge_class($risk)) ?>"><?= e($risk) ?></span>
                            </td>
                            <td>
                                <div class="mini-stack">
                                    <strong><?= e($p['care_area'] ?? $meta['label']) ?></strong>
                                    <span><?= e($p['ward'] ?? care_text($p, 'bed_status')) ?></span>
                                    <span><?= e($p['department'] ?? '-') ?></span>
                                    <span><?= e($p['hospital'] ?? '-') ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="mini-stack">
                                    <strong><?= e(care_text($p, 'round_focus')) ?></strong>
                                    <span><?= e(care_text($p, 'daily_note')) ?></span>
                                    <?php if ($type === 'ICU'): ?>
                                        <span class="care-alert">ICU: <?= e(care_text($p, 'icu_daily_note')) ?></span>
                                    <?php elseif ($type === 'SURGERY' || $type === 'SURGERY_QUEUE'): ?>
                                        <span class="care-alert">OR: <?= e(care_text($p, 'operation_name')) ?> · <?= e(care_text($p, 'operation_status')) ?></span>
                                    <?php elseif ($type === 'HIGH_WATCH'): ?>
                                        <span class="care-alert">Monitor: <?= e(care_text($p, 'monitoring_frequency')) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="mini-stack">
                                    <span>Admit: <strong><?= e(care_text($p, 'admission_date', $type === 'OPD' ? 'OPD' : '-')) ?></strong></span>
                                    <span>อยู่ รพ.: <strong><?= e(care_text($p, 'length_of_stay')) ?></strong></span>
                                    <span>คาดออก: <strong><?= e(care_text($p, 'expected_discharge_date')) ?></strong></span>
                                    <span>Round ล่าสุด: <?= e(care_text($p, 'last_round_date')) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="mini-stack">
                                    <span><strong>ยาเพิ่ม:</strong> <?= e(care_text($p, 'additional_medication')) ?></span>
                                    <span><strong>Plan:</strong> <?= e(care_text($p, 'followup_plan')) ?></span>
                                    <span><strong>จำหน่าย:</strong> <?= e(care_text($p, 'discharge_plan')) ?></span>
                                    <?php if ($type === 'ICU'): ?>
                                        <span><strong>Vent:</strong> <?= e(care_text($p, 'ventilator_status')) ?></span>
                                        <span><strong>I/O:</strong> <?= e(care_text($p, 'fluid_balance')) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-stack">
                                    <a class="btn" href="<?= e(app_url('doctor/add-treatment.php?hn=' . urlencode($hn))) ?>">บันทึก Follow up</a>
                                    <a class="btn secondary" href="<?= e(app_url('doctor/timeline.php?hn=' . urlencode($hn))) ?>">Timeline</a>
                                    <a class="btn secondary" href="<?= e(app_url('doctor/visit-detail.php?hn=' . urlencode($hn))) ?>">Visit</a>
                                    <a class="btn secondary" href="<?= e(app_url('doctor/referral.php?hn=' . urlencode($hn))) ?>">ส่งต่อ</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="grid grid-3 mt-2">
    <?php foreach ($patients as $p): ?>
        <?php $f = $p['followup'] ?? usemed_patient_followup_details($p); $hn = (string) ($p['hn'] ?? ''); ?>
        <article class="card patient-follow-card">
            <div class="patient-card-head">
                <div>
                    <h3><?= e($p['full_name'] ?? '-') ?></h3>
                    <p><?= e($hn) ?> · <?= e($p['ward'] ?? '-') ?></p>
                </div>
                <span class="badge <?= e(badge_class((string) ($p['risk_level'] ?? 'Medium'))) ?>"><?= e($p['care_area'] ?? $meta['label']) ?></span>
            </div>

            <div class="follow-grid">
                <div><span>อยู่ รพ.</span><strong><?= e($f['length_of_stay'] ?? '-') ?></strong></div>
                <div><span>คาดออก</span><strong><?= e($f['expected_discharge_date'] ?? '-') ?></strong></div>
                <div><span>ยาเพิ่ม</span><strong><?= e($f['additional_medication'] ?? '-') ?></strong></div>
                <div><span>แผนวันนี้</span><strong><?= e($f['followup_plan'] ?? '-') ?></strong></div>
                <?php if ($type === 'ICU'): ?>
                    <div><span>ICU Day</span><strong><?= e($f['icu_day'] ?? '-') ?></strong></div>
                    <div><span>Vent/O2</span><strong><?= e($f['ventilator_status'] ?? '-') ?></strong></div>
                <?php endif; ?>
                <?php if ($type === 'SURGERY' || $type === 'SURGERY_QUEUE'): ?>
                    <div><span>ผ่าตัด</span><strong><?= e($f['operation_name'] ?? '-') ?></strong></div>
                    <div><span>วันผ่าตัด</span><strong><?= e($f['operation_date'] ?? '-') ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="action-row">
                <a class="btn" href="<?= e(app_url('doctor/add-treatment.php?hn=' . urlencode($hn))) ?>">Follow up</a>
                <a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php?hn=' . urlencode($hn))) ?>">Profile</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php page_end(); ?>
