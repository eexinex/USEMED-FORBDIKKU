<?php
// public/admin/tickets.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('admin');
usemed_ensure_extended_schema();

if (is_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'open');
    if ($id > 0 && db_is_connected()) {
        db_execute('UPDATE support_tickets SET status = :status WHERE id = :id', ['status' => $status, 'id' => $id]);
        flash_set('success', 'อัปเดตสถานะ Ticket แล้ว');
    } else {
        flash_set('success', 'Demo mode: รับทราบการอัปเดตสถานะแล้ว');
    }
    redirect_to('admin/tickets.php');
}

$tickets = demo_tickets();
if (db_is_connected()) {
    $dbTickets = db_fetch_all('SELECT * FROM support_tickets ORDER BY created_at DESC, id DESC LIMIT 100');
    if (!empty($dbTickets)) { $tickets = $dbTickets; }
}

$open = 0;
foreach ($tickets as $t) {
    if (($t['status'] ?? 'open') !== 'closed') { $open++; }
}

page_start('Support Tickets', 'admin', 'tickets');
topbar('Support / แจ้งปัญหา', 'ดูว่าผู้ใช้งานกดเข้าเมนูไหนไม่ได้ และติดตามสถานะการแก้ไข');
?>

<section class="stat-grid">
    <?php stat_card('Tickets ทั้งหมด', (string) count($tickets), 'Support'); ?>
    <?php stat_card('รอดำเนินการ', (string) $open, 'Open'); ?>
    <?php stat_card('ปิดงานแล้ว', (string) (count($tickets) - $open), 'Closed'); ?>
    <?php stat_card('DB', db_is_connected() ? 'Connected' : 'Demo', 'Mode'); ?>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div><h1>รายการแจ้งปัญหา</h1><p>รวม Support จากคนไข้ หมอ Admin และผู้ใช้งานทั่วไป</p></div>
        <div class="searchbar"><input type="search" data-table-search="adminTicketsFull" placeholder="ค้นหา ticket..."></div>
    </div>

    <?php if (empty($tickets)): ?>
        <?php render_empty_state('ยังไม่มี Ticket', 'เมื่อมีผู้ใช้แจ้งปัญหา รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap"><table class="table" id="adminTicketsFull"><thead><tr><th>#</th><th>ผู้แจ้ง</th><th>ประเภท</th><th>เมนูที่เข้าไม่ได้</th><th>หัวข้อ/รายละเอียด</th><th>สถานะ</th><th>อัปเดต</th></tr></thead><tbody>
            <?php foreach ($tickets as $ticket): ?>
                <?php $status = (string) ($ticket['status'] ?? 'open'); $badge = $status === 'closed' ? 'green' : 'orange'; ?>
                <tr>
                    <td><?= e($ticket['id'] ?? '-') ?></td>
                    <td><strong><?= e($ticket['user_name'] ?? 'ไม่ระบุ') ?></strong><br><span class="text-muted"><?= e($ticket['user_role'] ?? 'guest') ?></span></td>
                    <td><?= e($ticket['problem_type'] ?? 'ทั่วไป') ?></td>
                    <td><?= e($ticket['menu_path'] ?? '-') ?></td>
                    <td><strong><?= e($ticket['subject'] ?? '-') ?></strong><br><span class="text-muted"><?= e($ticket['message'] ?? '-') ?></span></td>
                    <td><span class="badge <?= e($badge) ?>"><?= e($status) ?></span></td>
                    <td>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="id" value="<?= e($ticket['id'] ?? 0) ?>">
                            <select name="status"><option value="open" <?= $status === 'open' ? 'selected' : '' ?>>open</option><option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>in_progress</option><option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>closed</option></select>
                            <button class="btn secondary" type="submit">บันทึก</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>

<?php page_end();
