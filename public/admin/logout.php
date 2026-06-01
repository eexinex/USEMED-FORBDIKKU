<?php
// public/admin/logout.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

logout_user();

flash_set('success', 'ออกจากระบบ Admin เรียบร้อยแล้ว');

redirect_to('admin/login.php');