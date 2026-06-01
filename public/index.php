<?php
// public/index.php
declare(strict_types=1);

function file_ok(string $path): bool
{
    return is_file(__DIR__ . '/' . $path);
}

$patientLink = file_ok('patient/login.php') ? 'patient/login.php' : 'patient/portal.php';
$doctorLink  = file_ok('doctor/login.php') ? 'doctor/login.php' : 'doctor/dashboard.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USE MED</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, Tahoma, sans-serif;
            color: #12312d;
            background:
                radial-gradient(circle at 12% 8%, rgba(20, 184, 166, .22), transparent 34%),
                radial-gradient(circle at 88% 14%, rgba(59, 130, 246, .12), transparent 30%),
                linear-gradient(135deg, #eefdfa 0%, #f8fbff 52%, #edf7f5 100%);
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 32px 20px 20px;
        }

        .hero {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            text-align: center;
            padding: 22px 0 26px;
        }

        .logo {
            width: 82px;
            height: 82px;
            margin: 0 auto 18px;
            border-radius: 28px;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 44px;
            font-weight: 800;
            background: linear-gradient(135deg, #0f766e, #10b981);
            box-shadow: 0 20px 50px rgba(15, 118, 110, .28);
        }

        h1 {
            margin: 0;
            font-size: clamp(42px, 6vw, 76px);
            line-height: 1;
            letter-spacing: 5px;
            color: #102522;
        }

        .subtitle {
            margin: 18px auto 0;
            max-width: 760px;
            font-size: 19px;
            line-height: 1.8;
            color: #5f756f;
        }

        .features {
            margin: 22px auto 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .72);
            border: 1px solid rgba(15, 118, 110, .12);
            color: #31544e;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .main {
            flex: 1;
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            display: flex;
            align-items: center;
        }

        .role-grid {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, minmax(280px, 1fr));
            gap: 30px;
        }

        .role-card {
            position: relative;
            overflow: hidden;
            min-height: 360px;
            padding: 42px;
            border-radius: 38px;
            text-decoration: none;
            color: #102522;
            background: rgba(255, 255, 255, .90);
            border: 1px solid rgba(255, 255, 255, .78);
            box-shadow:
                0 28px 80px rgba(15, 23, 42, .12),
                inset 0 1px 0 rgba(255, 255, 255, .7);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .role-card::before {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            right: -80px;
            top: -80px;
            border-radius: 50%;
            background: rgba(15, 118, 110, .09);
        }

        .role-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 38px 100px rgba(15, 23, 42, .18);
            border-color: rgba(15, 118, 110, .20);
        }

        .role-icon {
            width: 96px;
            height: 96px;
            border-radius: 30px;
            display: grid;
            place-items: center;
            font-size: 48px;
            margin-bottom: 30px;
        }

        .patient .role-icon {
            background: linear-gradient(135deg, #ecfeff, #dffbf6);
        }

        .doctor .role-icon {
            background: linear-gradient(135deg, #f0fdf4, #e2fbe9);
        }

        .role-card h2 {
            margin: 0 0 16px;
            font-size: clamp(42px, 4vw, 58px);
            line-height: 1.05;
            letter-spacing: .5px;
        }

        .role-card p {
            margin: 0;
            max-width: 480px;
            color: #637a74;
            font-size: 18px;
            line-height: 1.85;
        }

        .cta {
            margin-top: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            min-width: 190px;
            height: 54px;
            padding: 0 26px;
            border-radius: 999px;
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            box-shadow: 0 14px 30px rgba(15, 118, 110, .22);
        }

        .patient .cta {
            background: linear-gradient(135deg, #0f766e, #0d9488);
        }

        .doctor .cta {
            background: linear-gradient(135deg, #16a34a, #0f766e);
        }

        .footer {
            width: 100%;
            max-width: 1180px;
            margin: 30px auto 0;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            color: #78908a;
            font-size: 14px;
        }

        .footer strong {
            color: #31544e;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .footer-links a {
            text-decoration: none;
            color: #0f766e;
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(15, 118, 110, .14);
            padding: 9px 14px;
            border-radius: 999px;
            font-weight: 800;
            transition: .18s ease;
        }

        .footer-links a:hover {
            background: #0f766e;
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 860px) {
            .page {
                padding: 24px 14px 18px;
            }

            .main {
                align-items: flex-start;
            }

            .role-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .role-card {
                min-height: 300px;
                padding: 30px;
                border-radius: 30px;
            }

            .footer {
                flex-direction: column;
                justify-content: center;
                text-align: center;
            }

            .footer-links {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
<div class="page">

    <header class="hero">
        <div class="logo">＋</div>

        <h1>USE MED</h1>

        <p class="subtitle">
            ระบบจัดการข้อมูลสุขภาพสำหรับผู้ป่วยและแพทย์
            รองรับการบันทึกข้อมูลจริง เอกสาร ไทม์ไลน์ และ AI Risk Module
        </p>

        <div class="features">
            <span class="chip">บันทึกข้อมูลจริง</span>
            <span class="chip">ดูเอกสารและ Timeline</span>
            <span class="chip">รองรับ LINE Bot</span>
            <span class="chip">AI Risk Module</span>
        </div>
    </header>

    <main class="main">
        <section class="role-grid">

            <a class="role-card patient" href="<?= htmlspecialchars($patientLink, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <div class="role-icon">👤</div>
                    <h2>คนไข้</h2>
                    <p>
                        เข้าสู่ระบบเพื่อดูข้อมูลส่วนตัว เอกสารทางการแพทย์
                        ไทม์ไลน์การรักษา และรายละเอียดการเข้ารับบริการ
                    </p>
                </div>

                <div class="cta">เข้าสู่หน้าคนไข้</div>
            </a>

            <a class="role-card doctor" href="<?= htmlspecialchars($doctorLink, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <div class="role-icon">🩺</div>
                    <h2>หมอ</h2>
                    <p>
                        เข้าสู่ระบบเพื่อค้นหาผู้ป่วย ลงทะเบียน เพิ่มการรักษา
                        อัปโหลดเอกสาร ส่งต่อ และจัดการข้อมูลทางการแพทย์
                    </p>
                </div>

                <div class="cta">เข้าสู่หน้าหมอ</div>
            </a>

        </section>
    </main>

    <footer class="footer">
        <div>
            © <?= date('Y') ?> USE MED. Created by <strong>ธัญญชล จำปาศักดิ์</strong>. All rights reserved.
        </div>

        <div class="footer-links">
            <?php if (file_ok('about.php')): ?>
                <a href="about.php">About</a>
            <?php endif; ?>

            <?php if (file_ok('support.php')): ?>
                <a href="support.php">Support</a>
            <?php endif; ?>

            <?php if (file_ok('admin/login.php')): ?>
                <a href="admin/login.php">Admin</a>
            <?php endif; ?>

            <?php if (file_ok('check.php')): ?>
                <a href="check.php">ตรวจระบบ</a>
            <?php endif; ?>
        </div>
    </footer>

</div>
</body>
</html>