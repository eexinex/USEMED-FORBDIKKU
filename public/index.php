<?php
// public/index.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/shared/layout.php';

function file_ok(string $path): bool
{
    return is_file(__DIR__ . '/' . $path);
}

$patientLink = file_ok('patient/login.php') ? 'patient/login.php' : 'patient/portal.php';
$doctorLink = file_ok('doctor/login.php') ? 'doctor/login.php' : 'doctor/dashboard.php';

page_start('USE MED', 'guest');
?>

<div style="max-width: 900px; margin: 0 auto; padding: 40px 20px;">
    
    <header style="text-align: center; margin-bottom: 40px;">
        <div style="width: 72px; height: 72px; margin: 0 auto 16px; border-radius: 22px; display: grid; place-items: center; color: white; font-size: 36px; font-weight: 800; background: var(--primary);">＋</div>
        <h1 style="margin: 0; font-size: 48px; letter-spacing: 2px; color: var(--ink);">USE MED</h1>
        <p style="margin: 16px auto 0; max-width: 600px; font-size: 18px; color: var(--muted);">
            ระบบจัดการข้อมูลสุขภาพสำหรับผู้ป่วยและแพทย์<br>
            รองรับการบันทึกข้อมูลจริง เอกสาร ไทม์ไลน์ และ AI Risk Module
        </p>
    </header>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
        
        <a href="<?= htmlspecialchars($patientLink, ENT_QUOTES, 'UTF-8') ?>" class="card" style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px; transition: transform 0.2s; border: 1px solid var(--line);">
            <div>
                <div style="font-size: 40px; margin-bottom: 16px;">👤</div>
                <h2 style="margin: 0 0 12px; font-size: 28px; color: var(--ink);">สำหรับคนไข้</h2>
                <p style="margin: 0; color: var(--muted);">
                    เข้าสู่ระบบเพื่อดูข้อมูลส่วนตัว เอกสารทางการแพทย์ ไทม์ไลน์การรักษา และรายละเอียดการเข้ารับบริการ
                </p>
            </div>
            <div style="margin-top: 24px;">
                <span class="btn btn-primary" style="display: inline-block; width: 100%; text-align: center;">เข้าสู่หน้าคนไข้</span>
            </div>
        </a>

        <a href="<?= htmlspecialchars($doctorLink, ENT_QUOTES, 'UTF-8') ?>" class="card" style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px; transition: transform 0.2s; border: 1px solid var(--line);">
            <div>
                <div style="font-size: 40px; margin-bottom: 16px;">🩺</div>
                <h2 style="margin: 0 0 12px; font-size: 28px; color: var(--ink);">สำหรับบุคลากร</h2>
                <p style="margin: 0; color: var(--muted);">
                    เข้าสู่ระบบเพื่อค้นหาผู้ป่วย ลงทะเบียน เพิ่มการรักษา อัปโหลดเอกสาร ส่งต่อ และจัดการข้อมูลทางการแพทย์
                </p>
            </div>
            <div style="margin-top: 24px;">
                <span class="btn btn-primary" style="display: inline-block; width: 100%; text-align: center; background: var(--ink); border-color: var(--ink);">เข้าสู่หน้าบุคลากร</span>
            </div>
        </a>

    </div>

    <footer style="margin-top: 60px; text-align: center; font-size: 14px; color: var(--muted);">
        <p>© <?= date('Y') ?> USE MED. Created by <strong>ทีม KKU Is not here</strong>. All rights reserved.</p>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 12px;">
            <?php if (file_ok('about.php')): ?><a href="about.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">About</a><?php endif; ?>
            <?php if (file_ok('support.php')): ?><a href="support.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">Support</a><?php endif; ?>
            <?php if (file_ok('admin/login.php')): ?><a href="admin/login.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">Admin</a><?php endif; ?>
        </div>
    </footer>

</div>

<?php page_end(); ?>