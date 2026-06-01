<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About USE MED</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Tahoma, sans-serif;
            background:
                radial-gradient(circle at 15% 10%, rgba(20, 184, 166, 0.18), transparent 32%),
                linear-gradient(135deg, #eefdfa, #f8fbff);
            color: #102522;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px;
        }

        .card {
            width: 100%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(15, 118, 110, 0.12);
            border-radius: 34px;
            padding: 42px;
            box-shadow: 0 28px 90px rgba(15, 23, 42, 0.12);
        }

        .logo {
            width: 74px;
            height: 74px;
            border-radius: 24px;
            background: linear-gradient(135deg, #0f766e, #10b981);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 38px;
            font-weight: 800;
            margin-bottom: 24px;
            box-shadow: 0 16px 36px rgba(15, 118, 110, 0.28);
        }

        h1 {
            margin: 0;
            font-size: 48px;
            letter-spacing: 2px;
        }

        .subtitle {
            margin: 12px 0 30px;
            font-size: 20px;
            line-height: 1.8;
            color: #5f756f;
        }

        .section {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #dbe8e5;
        }

        h2 {
            margin: 0 0 14px;
            font-size: 24px;
            color: #0f766e;
        }

        ul {
            margin: 0;
            padding-left: 22px;
        }

        li {
            margin: 10px 0;
            line-height: 1.8;
            font-size: 17px;
        }

        .tech {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .chip {
            padding: 10px 14px;
            border-radius: 999px;
            background: #ecfeff;
            color: #0f766e;
            font-weight: 800;
            border: 1px solid #ccefed;
        }

        .copyright {
            margin-top: 30px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
        }

        .actions {
            margin-top: 28px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 800;
            background: #0f766e;
            color: white;
        }

        .btn.secondary {
            background: white;
            color: #0f766e;
            border: 1px solid #cde7e3;
        }

        @media (max-width: 720px) {
            .card {
                padding: 30px 22px;
                border-radius: 28px;
            }

            h1 {
                font-size: 36px;
            }

            .subtitle {
                font-size: 17px;
            }
        }
    </style>
</head>
<body>

<main class="page">
    <section class="card">
        <div class="logo">＋</div>

        <h1>USE MED</h1>
        <p class="subtitle">
            ระบบจัดการข้อมูลสุขภาพสำหรับผู้ป่วยและแพทย์
        </p>

        <div class="section">
            <h2>Created by</h2>
            <ul>
                <li>น่านฟ้า ธัญญชล จำปาศักดิ์</li>
                <li>
                    ทีม KKU Is not here ชั้นปีที่ 3<br>
                    ภาควิชาวิศวกรรมระบบการผลิต<br>
                    สถาบันเทคโนโลยีพระจอมเกล้าเจ้าคุณทหารลาดกระบัง
                </li>
            </ul>
        </div>

        <div class="section">
            <h2>Technology</h2>
            <div class="tech">
                <span class="chip">PHP</span>
                <span class="chip">MySQL</span>
                <span class="chip">LINE Messaging API</span>
                <span class="chip">AI Risk Module</span>
            </div>
        </div>

        <div class="copyright">
            © 2026 USE MED. Created by ธัญญชล จำปาศักดิ์ and KKU Is not here. All rights reserved..
        </div>

        <div class="actions">
            <a class="btn" href="index.php">กลับหน้าแรก</a>
            <a class="btn secondary" href="support.php">ติดต่อ Support</a>
        </div>
    </section>
</main>

</body>
</html>