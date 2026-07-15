<?php
// Extra check for role
// Check role and division
if (
    !isset($_SESSION['role'], $_SESSION['division']) ||
    $_SESSION['role'] !== 'ADMIN' ||
    $_SESSION['division'] !== 'ADMIN'
) {
    http_response_code(403);
    exit('Access Denied');
}

