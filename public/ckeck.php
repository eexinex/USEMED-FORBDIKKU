<?php
// Backward-compatible redirect for a common typo.

declare(strict_types=1);

header('Location: /check.php', true, 302);
exit;
