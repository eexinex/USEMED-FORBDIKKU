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
            <h2 style="font-size: 24px; color: var(--ink); margin: 0 0 8px;">ทีม Absolute Zero</h2>
            <p style="color: var(--muted); margin: 0 0 24px;">รายชื่อสมาชิกผู้พัฒนาและออกแบบระบบ USE MED</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                <div style="padding: 12px 16px; background: var(--bg); border-radius: 12px; border: 1px solid var(--line);">
                    <strong style="color: var(--ink); display: block; font-size: 16px;">ธัญญชล จำปาศักดิ์</strong>
                </div>
                <div style="padding: 12px 16px; background: var(--bg); border-radius: 12px; border: 1px solid var(--line);">
                    <strong style="color: var(--ink); display: block; font-size: 16px;">สิงห์ชยพณ เกลี้ยงมะ</strong>
                </div>
                <div style="padding: 12px 16px; background: var(--bg); border-radius: 12px; border: 1px solid var(--line);">
                    <strong style="color: var(--ink); display: block; font-size: 16px;">นราวิชญ์ สันดอน</strong>
                </div>
                <div style="padding: 12px 16px; background: var(--bg); border-radius: 12px; border: 1px solid var(--line);">
                    <strong style="color: var(--ink); display: block; font-size: 16px;">ณัฏฐกิตติ์ พรหมวิศิษฎ์</strong>
                </div>
                <div style="padding: 12px 16px; background: var(--bg); border-radius: 12px; border: 1px solid var(--line);">
                    <strong style="color: var(--ink); display: block; font-size: 16px;">ภูวิศ จำนงสิริศักดิ์</strong>
                </div>
            </div>

            <h3 style="font-size: 18px; color: var(--primary); margin: 32px 0 16px;">ที่ปรึกษาและผู้เชี่ยวชาญด้านการแพทย์</h3>
            <div style="display: grid; gap: 16px;">
                <div style="padding: 16px; background: var(--bg2); border-radius: 12px; border: 1px solid var(--line); display: flex; flex-direction: column; gap: 4px;">
                    <strong style="color: var(--primary-dark); font-size: 16px;">เฌอลินญ์ รังสิริธีรกานต์</strong>
                    <span style="color: var(--muted); font-size: 14px;">คณะแพทยศาสตร์โรงพยาบาลรามาธิบดี สาขาฉุกเฉินการแพทย์</span>
                </div>
                <div style="padding: 16px; background: var(--bg2); border-radius: 12px; border: 1px solid var(--line); display: flex; flex-direction: column; gap: 4px;">
                    <strong style="color: var(--primary-dark); font-size: 16px;">จิดาภา เทียนวรรณ</strong>
                    <span style="color: var(--muted); font-size: 14px;">คณะแพทยศาสตร์โรงพยาบาลรามาธิบดี สาขาแพทยศาสตร์</span>
                </div>
                <div style="padding: 16px; background: var(--bg2); border-radius: 12px; border: 1px solid var(--line); display: flex; flex-direction: column; gap: 4px;">
                    <strong style="color: var(--primary-dark); font-size: 16px;">นพ.ณัฐกิตติ์ ภิรมย์นาค</strong>
                    <span style="color: var(--muted); font-size: 14px;">โรงพยาบาลศรีนครินทร์</span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 32px; padding: 16px; background: var(--bg); border-radius: 12px; color: var(--muted); font-size: 14px; text-align: center; border: 1px solid var(--line);">
            © <?= date('Y') ?> USE MED. Created by <strong>Absolute Zero</strong>. All rights reserved.
        </div>
        
        <div style="margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a class="btn btn-primary" href="index.php">กลับหน้าแรก</a>
            <a class="btn secondary" href="support.php">ติดต่อ Support</a>
        </div>
    </div>
</div>

<?php page_end(); ?>
