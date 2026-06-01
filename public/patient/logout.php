<?php
// public/patient/logout.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

logout_user();

flash_set('success', 'ออกจากระบบผู้ป่วยเรียบร้อยแล้ว');

redirect_to('patient/login.php');