<?php
// public/doctor/icu.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$icuPatients = [
    [
        'bed' => 'ICU-01',
        'hn' => 'HN0001',
        'name' => 'สมชาย ใจดี',
        'age' => 58,
        'status' => 'Stable',
        'bp' => '148/92',
        'pulse' => 78,
        'spo2' => 97,
        'temp' => 36.8,
        'risk' => 'Medium',
        'note' => 'ติดตามระดับน้ำตาลและความดัน',
    ],
    [
        'bed' => 'ICU-02',
        'hn' => 'HN0002',
        'name' => 'มาลี สุขใจ',
        'age' => 64,
        'status' => 'Watch',
        'bp' => '165/101',
        'pulse' => 96,
        'spo2' => 93,
        'temp' => 37.6,
        'risk' => 'High',
        'note' => 'เฝ้าระวังความดันและออกซิเจน',
    ],
    [
        'bed' => 'ICU-03',
        'hn' => 'HN0003',
        'name' => 'อนันต์ รักษ์ดี',
        'age' => 49,
        'status' => 'Improving',
        'bp' => '126/82',
        'pulse' => 72,
        'spo2' => 99,
        'temp' => 36.5,
        'risk' => 'Low',
        'note' => 'อาการดีขึ้น รอประเมินย้ายวอร์ด',
    ],
];

$totalBeds = 8;
$usedBeds = count($icuPatients);
$availableBeds = $totalBeds - $usedBeds;
$highRisk = 0;

foreach ($icuPatients as $item) {
    if (($item['risk'] ?? '') === 'High') {
        $highRisk++;
    }
}

page_start('ICU Monitor', 'doctor', 'dashboard');

topbar(
    'ICU Monitor',
    'หน้าจำลองติดตามผู้ป่วยวิกฤตและสัญญาณชีพแบบ Real-time Demo'
);
?>

<section class="stat-grid">
    <?php stat_card('เตียงทั้งหมด', (string) $totalBeds, 'ICU Beds'); ?>
    <?php stat_card('ใช้งานอยู่', (string) $usedBeds, 'Admitted'); ?>
    <?php stat_card('ว่าง', (string) $availableBeds, 'Available'); ?>
    <?php stat_card('High Risk', (string) $highRisk, 'Alert'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ภาพรวม ICU</h2>
        <p class="text-muted">
            หน้านี้เป็น Demo Dashboard สำหรับแสดงสถานะผู้ป่วยใน ICU
            สามารถต่อยอดเชื่อมข้อมูล Vital Signs จริงจากฐานข้อมูลหรืออุปกรณ์ IoT ได้
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>Monitor Status</strong>
                    <span>ระบบจำลองการติดตามสัญญาณชีพ</span>
                </div>
                <span class="badge green">Online</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>AI Alert</strong>
                    <span>คัดกรองผู้ป่วยความเสี่ยงสูง</span>
                </div>
                <span class="badge orange">Rule-based</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Doctor Access</strong>
                    <span><?= e(current_user()['name'] ?? 'Doctor') ?></span>
                </div>
                <span class="badge blue">Active</span>
            </div>
        </div>
    </div>

    <div class="risk-card">
        <div class="risk-score">
            <div>
                <span class="badge red">ICU Alert</span>

                <h2 style="margin:12px 0 6px;">
                    <?= e($highRisk) ?> High Risk Case
                </h2>

                <p class="text-muted">
                    มีผู้ป่วยที่ต้องติดตามใกล้ชิดจากข้อมูลสัญญาณชีพและ Risk Level
                </p>
            </div>

            <div class="score-circle" style="--value:<?= e($usedBeds * 12) ?>">
                <strong><?= e($usedBeds) ?></strong>
            </div>
        </div>

        <div class="mt-2">
            <div class="riskbar">
                <span style="width:<?= e(($usedBeds / $totalBeds) * 100) ?>%"></span>
            </div>
        </div>

        <ul class="factor-list">
            <li>เตียงใช้งาน <?= e($usedBeds) ?> จาก <?= e($totalBeds) ?> เตียง</li>
            <li>มีผู้ป่วย High Risk จำนวน <?= e($highRisk) ?> ราย</li>
            <li>ระบบนี้เป็นหน้าจำลองสำหรับ Hackathon Demo</li>
        </ul>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>ICU Patient List</h1>
            <p>รายการผู้ป่วยในหอผู้ป่วยวิกฤต</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="icuTable"
                placeholder="ค้นหาเตียง / HN / ชื่อ..."
            >
        </div>
    </div>

    <div class="table-wrap">
        <table class="table" id="icuTable">
            <thead>
                <tr>
                    <th>เตียง</th>
                    <th>ผู้ป่วย</th>
                    <th>BP</th>
                    <th>Pulse</th>
                    <th>SpO2</th>
                    <th>Temp</th>
                    <th>Risk</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($icuPatients as $item): ?>
                    <?php
                    $risk = $item['risk'] ?? 'Medium';
                    $badge = badge_class((string) $risk);
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($item['bed']) ?></strong><br>
                            <span class="text-muted"><?= e($item['status']) ?></span>
                        </td>
                        <td>
                            <strong><?= e($item['name']) ?></strong><br>
                            <span class="text-muted">
                                <?= e($item['hn']) ?> / <?= e($item['age']) ?> ปี
                            </span>
                        </td>
                        <td><?= e($item['bp']) ?></td>
                        <td><?= e($item['pulse']) ?> bpm</td>
                        <td><?= e($item['spo2']) ?>%</td>
                        <td><?= e($item['temp']) ?>°C</td>
                        <td>
                            <span class="badge <?= e($badge) ?>">
                                <?= e($risk) ?>
                            </span>
                        </td>
                        <td><?= e($item['note']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="grid grid-3 mt-2">
    <a class="card" href="<?= e(app_url('doctor/dashboard.php')) ?>">
        <h3>Doctor Dashboard</h3>
        <p>กลับไปหน้าภาพรวมของแพทย์</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/ai-risk.php')) ?>">
        <h3>AI Risk</h3>
        <p>ประเมินความเสี่ยงจากข้อมูลสุขภาพ</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/stat-list.php')) ?>">
        <h3>Statistics</h3>
        <p>ดูสถิติผู้ป่วย Visit และเอกสาร</p>
    </a>
</section>

<?php
page_end();