<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/shared/layout.php';

page_start('About USE MED', 'guest');
?>

<div style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    <div class="card" style="padding: 40px;">
        <div style="width: 64px; height: 64px; border-radius: 18px; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 800; margin-bottom: 24px;">＋</div>
        
        <h1 style="margin: 0; font-size: 36px; color: var(--ink);">USE MED</h1>
        <p style="margin: 12px 0 32px; font-size: 18px; color: var(--muted);">ระบบจัดการข้อมูลสุขภาพสำหรับผู้ป่วยและแพทย์</p>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--line);">
            <h2 style="font-size: 20px; color: var(--primary); margin: 0 0 16px;">Created by</h2>
            <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                <li>น่านฟ้า ธัญญชล จำปาศักดิ์</li>
                <li>ทีม KKU Is not here ชั้นปีที่ 3<br>ภาควิชาวิศวกรรมระบบการผลิต<br>สถาบันเทคโนโลยีพระจอมเกล้าเจ้าคุณทหารลาดกระบัง</li>
            </ul>
        </div>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--line);">
            <h2 style="font-size: 20px; color: var(--primary); margin: 0 0 16px;">Technology</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <span class="badge" style="background: var(--bg2); color: var(--primary-dark); padding: 6px 12px; border-radius: 99px; font-size: 14px; font-weight: bold;">PHP</span>
                <span class="badge" style="background: var(--bg2); color: var(--primary-dark); padding: 6px 12px; border-radius: 99px; font-size: 14px; font-weight: bold;">MySQL</span>
                <span class="badge" style="background: var(--bg2); color: var(--primary-dark); padding: 6px 12px; border-radius: 99px; font-size: 14px; font-weight: bold;">LINE Messaging API</span>
                <span class="badge" style="background: var(--bg2); color: var(--primary-dark); padding: 6px 12px; border-radius: 99px; font-size: 14px; font-weight: bold;">AI Risk Module</span>
            </div>
        </div>
        
        <div style="margin-top: 32px; padding: 16px; background: var(--bg); border-radius: 12px; color: var(--muted); font-size: 14px; text-align: center;">
            © <?= date('Y') ?> USE MED. Created by ธัญญชล จำปาศักดิ์ and KKU Is not here. All rights reserved.
        </div>
        
        <div style="margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a class="btn btn-primary" href="index.php">กลับหน้าแรก</a>
            <a class="btn" style="background: white; color: var(--primary); border: 1px solid var(--line);" href="support.php">ติดต่อ Support</a>
        </div>
    </div>
</div>

<?php page_end(); ?>