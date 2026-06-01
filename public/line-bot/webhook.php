<?php
// public/line-bot/webhook.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/config.php';

/*
|--------------------------------------------------------------------------
| LINE BOT CONFIG
|--------------------------------------------------------------------------
| เอาค่าจาก LINE Developers มาใส่ตรงนี้
|--------------------------------------------------------------------------
*/

$channelAccessToken = '5taXu7T7hvtyzofhGmBfCXOugPjVtP84uc5SFXEgkCbrsV7UVkgGJVdZ0fr5tb9/ZQeXkvkBcCx/vzyAR9ToYFvGOI+twJyO50HyD5OKvR3zbX6nKk47+AeJgiklbKwx+onGVhFWiGk4VvFIXsEKjgdB04t89/1O/w1cDnyilFU=';
$channelSecret = 'P2c363a59c3073358813c6f6eb7c6534a';

/*
|--------------------------------------------------------------------------
| READ REQUEST
|--------------------------------------------------------------------------
*/

$body = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';

/*
|--------------------------------------------------------------------------
| SIGNATURE CHECK
|--------------------------------------------------------------------------
| ถ้ายังไม่ได้ใส่ channel secret จะข้ามการตรวจ signature เพื่อให้ทดสอบง่าย
|--------------------------------------------------------------------------
*/

if ($channelSecret !== 'PUT_LINE_CHANNEL_SECRET_HERE' && trim($channelSecret) !== '') {
    $hash = hash_hmac('sha256', $body, $channelSecret, true);
    $expectedSignature = base64_encode($hash);

    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(403);
        echo 'Invalid signature';
        exit;
    }
}

$data = json_decode($body, true);

if (!is_array($data)) {
    http_response_code(200);
    echo 'OK';
    exit;
}

$events = $data['events'] ?? [];

foreach ($events as $event) {
    handle_line_event($event, $channelAccessToken);
}

http_response_code(200);
echo 'OK';
exit;

/*
|--------------------------------------------------------------------------
| EVENT HANDLER
|--------------------------------------------------------------------------
*/

function handle_line_event(array $event, string $token): void
{
    $type = $event['type'] ?? '';
    $replyToken = $event['replyToken'] ?? '';

    if ($replyToken === '') {
        return;
    }

    if ($type === 'follow') {
        reply_text($replyToken, build_welcome_message(), $token);
        return;
    }

    if ($type === 'message') {
        $message = $event['message'] ?? [];
        $messageType = $message['type'] ?? '';

        if ($messageType === 'text') {
            $text = trim((string) ($message['text'] ?? ''));
            reply_text($replyToken, build_reply_message($text), $token);
            return;
        }

        reply_text(
            $replyToken,
            "ขณะนี้ USE MED Bot รองรับข้อความตัวอักษรก่อนนะครับ\nพิมพ์ เมนู เพื่อดูตัวเลือก",
            $token
        );

        return;
    }

    if ($type === 'postback') {
        $postback = $event['postback']['data'] ?? '';
        reply_text($replyToken, build_reply_message($postback), $token);
        return;
    }

    reply_text($replyToken, build_menu_message(), $token);
}

/*
|--------------------------------------------------------------------------
| MESSAGE BUILDER
|--------------------------------------------------------------------------
*/

function build_welcome_message(): string
{
    return
        "ยินดีต้อนรับสู่ USE MED 🏥\n\n" .
        "ระบบผู้ป่วยและเอกสารสุขภาพ\n\n" .
        "พิมพ์คำสั่งได้เลย:\n" .
        "1) เมนู\n" .
        "2) ประวัติ\n" .
        "3) เอกสาร\n" .
        "4) เข้าสู่ระบบ\n" .
        "5) แจ้งปัญหา";
}

function build_menu_message(): string
{
    return
        "USE MED Menu 🏥\n\n" .
        "👤 Patient Portal\n" .
        app_url('patient/login.php') . "\n\n" .
        "🕒 Timeline\n" .
        app_url('patient/timeline.php') . "\n\n" .
        "📄 Documents\n" .
        app_url('patient/documents.php') . "\n\n" .
        "🛟 Support\n" .
        app_url('support.php');
}

function build_reply_message(string $text): string
{
    $key = mb_strtolower(trim($text), 'UTF-8');

    if ($key === '' || in_array($key, ['เมนู', 'menu', 'help', 'ช่วยเหลือ'], true)) {
        return build_menu_message();
    }

    if (str_contains($key, 'เข้าสู่ระบบ') || str_contains($key, 'login') || str_contains($key, 'portal')) {
        return
            "เข้าสู่ระบบ USE MED 👤\n\n" .
            "Patient Portal:\n" .
            app_url('patient/login.php') . "\n\n" .
            "Demo Login:\n" .
            "HN: HN0001\n" .
            "Password: 123456";
    }

    if (str_contains($key, 'ประวัติ') || str_contains($key, 'timeline') || str_contains($key, 'ไทม์ไลน์')) {
        return
            "ดูประวัติการรักษา / Timeline 🕒\n\n" .
            app_url('patient/timeline.php') . "\n\n" .
            "กรุณาเข้าสู่ระบบผู้ป่วยก่อนเปิดดูข้อมูล";
    }

    if (str_contains($key, 'เอกสาร') || str_contains($key, 'document') || str_contains($key, 'pdf')) {
        return
            "ดูเอกสารสุขภาพ 📄\n\n" .
            app_url('patient/documents.php') . "\n\n" .
            "เช่น ผลตรวจ ใบนัด และสรุปการรักษา";
    }

    if (str_contains($key, 'หมอ') || str_contains($key, 'แพทย์') || str_contains($key, 'doctor')) {
        return
            "Doctor Portal 👨‍⚕️\n\n" .
            app_url('doctor/login.php') . "\n\n" .
            "Demo Doctor:\n" .
            "Username: doctor1\n" .
            "Password: 123456";
    }

    if (str_contains($key, 'admin') || str_contains($key, 'ผู้ดูแล')) {
        return
            "Admin Dashboard 🛠️\n\n" .
            app_url('admin/login.php') . "\n\n" .
            "Demo Admin:\n" .
            "Username: admin\n" .
            "Password: admin123";
    }

    if (str_contains($key, 'แจ้งปัญหา') || str_contains($key, 'support') || str_contains($key, 'ช่วย')) {
        return
            "แจ้งปัญหาการใช้งาน 🛟\n\n" .
            app_url('support.php') . "\n\n" .
            "ใช้สำหรับแจ้งเอกสารเปิดไม่ได้ ข้อมูลไม่ถูกต้อง หรือเข้าสู่ระบบไม่ได้";
    }

    if (str_contains($key, 'นัด') || str_contains($key, 'appointment')) {
        return
            "ข้อมูลนัดหมาย 📅\n\n" .
            "ดูได้จาก Patient Portal หรือหน้าเอกสารใบนัด\n\n" .
            app_url('patient/portal.php');
    }

    if (str_contains($key, 'ai') || str_contains($key, 'risk') || str_contains($key, 'ความเสี่ยง')) {
        return
            "AI Risk 🧠\n\n" .
            "ระบบประเมินความเสี่ยงเบื้องต้นจากข้อมูลสุขภาพ เช่น BP, Glucose, HbA1c และ BMI\n\n" .
            "ดูข้อมูลล่าสุดได้ที่ Patient Portal:\n" .
            app_url('patient/portal.php');
    }

    return
        "ยังไม่พบคำสั่งนี้ในระบบ USE MED\n\n" .
        "พิมพ์ เมนู เพื่อดูตัวเลือกทั้งหมด";
}

/*
|--------------------------------------------------------------------------
| LINE API
|--------------------------------------------------------------------------
*/

function reply_text(string $replyToken, string $message, string $token): void
{
    if ($token === 'PUT_LINE_CHANNEL_ACCESS_TOKEN_HERE' || trim($token) === '') {
        error_log('LINE token is not configured.');
        return;
    }

    $payload = [
        'replyToken' => $replyToken,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message,
            ],
        ],
    ];

    line_post_json(
        'https://api.line.me/v2/bot/message/reply',
        $payload,
        $token
    );
}

function line_post_json(string $url, array $payload, string $token): void
{
    if (!function_exists('curl_init')) {
        error_log('PHP cURL is not enabled.');
        return;
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        error_log('LINE API Error: ' . $error);
        return;
    }

    if ((int) $status < 200 || (int) $status >= 300) {
        error_log('LINE API HTTP ' . $status . ': ' . (string) $response);
    }
}