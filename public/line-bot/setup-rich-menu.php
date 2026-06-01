<?php
// public/line-bot/setup-rich-menu.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/config.php';

/*
|--------------------------------------------------------------------------
| LINE CONFIG
|--------------------------------------------------------------------------
| เอา Channel access token จาก LINE Developers มาใส่ตรงนี้
|--------------------------------------------------------------------------
*/

$channelAccessToken = '5taXu7T7hvtyzofhGmBfCXOugPjVtP84uc5SFXEgkCbrsV7UVkgGJVdZ0fr5tb9/ZQeXkvkBcCx/vzyAR9ToYFvGOI+twJyO50HyD5OKvR3zbX6nKk47+AeJgiklbKwx+onGVhFWiGk4VvFIXsEKjgdB04t89/1O/w1cDnyilFU=';

/*
|--------------------------------------------------------------------------
| FILE CONFIG
|--------------------------------------------------------------------------
*/

$imagePath = __DIR__ . '/richmenu-usemed.png';

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function line_api_request(
    string $method,
    string $url,
    string $token,
    mixed $payload = null,
    array $extraHeaders = []
): array {
    $headers = array_merge([
        'Authorization: Bearer ' . $token,
    ], $extraHeaders);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $json = json_decode((string) $body, true);

    return [
        'status' => $status,
        'body' => $body,
        'json' => is_array($json) ? $json : null,
        'error' => $error,
    ];
}

function show_result(string $title, array $result): void
{
    echo '<div class="card">';
    echo '<h2>' . e($title) . '</h2>';
    echo '<p><strong>Status:</strong> ' . e((string) $result['status']) . '</p>';

    if (!empty($result['error'])) {
        echo '<p style="color:#be123c;"><strong>Error:</strong> ' . e($result['error']) . '</p>';
    }

    echo '<pre style="white-space:pre-wrap;background:#f8fafc;border:1px solid #dcebe7;border-radius:16px;padding:14px;overflow:auto;">';
    echo e((string) $result['body']);
    echo '</pre>';
    echo '</div>';
}

/*
|--------------------------------------------------------------------------
| RICH MENU OBJECT
|--------------------------------------------------------------------------
*/

$richMenu = [
    'size' => [
        'width' => 2500,
        'height' => 1686,
    ],
    'selected' => true,
    'name' => 'USE MED Rich Menu',
    'chatBarText' => 'USE MED',
    'areas' => [
        [
            'bounds' => [
                'x' => 0,
                'y' => 0,
                'width' => 833,
                'height' => 843,
            ],
            'action' => [
                'type' => 'uri',
                'label' => 'Patient Portal',
                'uri' => app_url('patient/login.php'),
            ],
        ],
        [
            'bounds' => [
                'x' => 833,
                'y' => 0,
                'width' => 834,
                'height' => 843,
            ],
            'action' => [
                'type' => 'uri',
                'label' => 'Timeline',
                'uri' => app_url('patient/timeline.php'),
            ],
        ],
        [
            'bounds' => [
                'x' => 1667,
                'y' => 0,
                'width' => 833,
                'height' => 843,
            ],
            'action' => [
                'type' => 'uri',
                'label' => 'Documents',
                'uri' => app_url('patient/documents.php'),
            ],
        ],
        [
            'bounds' => [
                'x' => 0,
                'y' => 843,
                'width' => 833,
                'height' => 843,
            ],
            'action' => [
                'type' => 'uri',
                'label' => 'Doctor',
                'uri' => app_url('doctor/login.php'),
            ],
        ],
        [
            'bounds' => [
                'x' => 833,
                'y' => 843,
                'width' => 834,
                'height' => 843,
            ],
            'action' => [
                'type' => 'uri',
                'label' => 'Support',
                'uri' => app_url('support.php'),
            ],
        ],
        [
            'bounds' => [
                'x' => 1667,
                'y' => 843,
                'width' => 833,
                'height' => 843,
            ],
            'action' => [
                'type' => 'uri',
                'label' => 'Home',
                'uri' => app_url('index.php'),
            ],
        ],
    ],
];

$results = [];
$richMenuId = null;

if (is_post()) {
    if ($channelAccessToken === 'PUT_LINE_CHANNEL_ACCESS_TOKEN_HERE' || trim($channelAccessToken) === '') {
        $results[] = [
            'title' => 'Config Error',
            'result' => [
                'status' => 0,
                'body' => 'กรุณาใส่ LINE Channel access token ก่อน',
                'json' => null,
                'error' => '',
            ],
        ];
    } elseif (!file_exists($imagePath)) {
        $results[] = [
            'title' => 'Image Error',
            'result' => [
                'status' => 0,
                'body' => 'ไม่พบไฟล์ richmenu-usemed.png ในโฟลเดอร์ public/line-bot',
                'json' => null,
                'error' => '',
            ],
        ];
    } elseif (!function_exists('curl_init')) {
        $results[] = [
            'title' => 'Server Error',
            'result' => [
                'status' => 0,
                'body' => 'Server นี้ยังไม่เปิดใช้งาน PHP cURL',
                'json' => null,
                'error' => '',
            ],
        ];
    } else {
        $createResult = line_api_request(
            'POST',
            'https://api.line.me/v2/bot/richmenu',
            $channelAccessToken,
            json_encode($richMenu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            [
                'Content-Type: application/json',
            ]
        );

        $results[] = [
            'title' => '1. Create Rich Menu',
            'result' => $createResult,
        ];

        $richMenuId = $createResult['json']['richMenuId'] ?? null;

        if ($richMenuId) {
            $imageBinary = file_get_contents($imagePath);

            $uploadResult = line_api_request(
                'POST',
                'https://api-data.line.me/v2/bot/richmenu/' . rawurlencode($richMenuId) . '/content',
                $channelAccessToken,
                $imageBinary,
                [
                    'Content-Type: image/png',
                ]
            );

            $results[] = [
                'title' => '2. Upload Rich Menu Image',
                'result' => $uploadResult,
            ];

            $defaultResult = line_api_request(
                'POST',
                'https://api.line.me/v2/bot/user/all/richmenu/' . rawurlencode($richMenuId),
                $channelAccessToken
            );

            $results[] = [
                'title' => '3. Set Default Rich Menu',
                'result' => $defaultResult,
            ];
        }
    }
}

?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup LINE Rich Menu | USE MED</title>
    <link rel="stylesheet" href="<?= e(frontend_url('css/usemed.css')) ?>?v=100">
</head>
<body>
    <main class="guest-main">
        <section class="landing" style="padding:28px;">
            <div class="landing-nav">
                <div class="landing-brand">
                    <div class="brand-logo">UM</div>
                    <div>
                        <h1>Setup LINE Rich Menu</h1>
                        <p>USE MED LINE Bot Configuration</p>
                    </div>
                </div>

                <span class="status-pill">
                    <span class="dot"></span>
                    Setup Tool
                </span>
            </div>

            <div class="grid grid-2 mt-2">
                <div class="card">
                    <h2>สถานะไฟล์</h2>

                    <div class="document-grid mt-2">
                        <div class="document-card">
                            <div>
                                <strong>Channel Token</strong>
                                <span>
                                    <?= $channelAccessToken === 'PUT_LINE_CHANNEL_ACCESS_TOKEN_HERE' ? 'ยังไม่ได้ใส่ Token' : 'ใส่ Token แล้ว' ?>
                                </span>
                            </div>
                            <span class="badge <?= $channelAccessToken === 'PUT_LINE_CHANNEL_ACCESS_TOKEN_HERE' ? 'red' : 'green' ?>">
                                Token
                            </span>
                        </div>

                        <div class="document-card">
                            <div>
                                <strong>Rich Menu Image</strong>
                                <span>public/line-bot/richmenu-usemed.png</span>
                            </div>
                            <span class="badge <?= file_exists($imagePath) ? 'green' : 'red' ?>">
                                <?= file_exists($imagePath) ? 'Found' : 'Missing' ?>
                            </span>
                        </div>

                        <div class="document-card">
                            <div>
                                <strong>App URL</strong>
                                <span><?= e(APP_URL) ?></span>
                            </div>
                            <span class="badge blue">URL</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>วิธีใช้</h2>

                    <ol>
                        <li>ใส่ Channel access token ในไฟล์นี้</li>
                        <li>เช็กว่ามีรูป <strong>richmenu-usemed.png</strong></li>
                        <li>กดปุ่ม Setup Rich Menu</li>
                        <li>สำเร็จแล้วให้ลบไฟล์นี้ออกจาก server</li>
                    </ol>

                    <form method="post" class="mt-2">
                        <button class="btn full" type="submit">
                            Setup Rich Menu
                        </button>
                    </form>

                    <div class="note-box mt-2">
                        หลัง setup สำเร็จ แนะนำให้ลบไฟล์
                        <strong>setup-rich-menu.php</strong>
                        เพื่อไม่ให้คนอื่นมากดซ้ำ
                    </div>
                </div>
            </div>

            <?php if (!empty($results)): ?>
                <section class="grid mt-2">
                    <?php foreach ($results as $item): ?>
                        <?php show_result($item['title'], $item['result']); ?>
                    <?php endforeach; ?>
                </section>

                <?php if ($richMenuId): ?>
                    <div class="alert alert-success mt-2">
                        Rich Menu ID: <?= e($richMenuId) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>