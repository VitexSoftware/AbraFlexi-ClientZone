#!/usr/bin/php
<?php

declare(strict_types=1);

namespace ClientZone;

require_once __DIR__.'/../includes/Init.php';

$login    = $argv[1] ?? 'admin';
$password = $argv[2] ?? '';

if (empty($password)) {
    fwrite(STDERR, "Usage: create-admin.php <login> <password>\n");
    exit(1);
}

$user = new User();
$user->setData([
    'login'    => $login,
    'password' => $user->encryptPassword($password),
    'email'    => $login,
]);

$id = $user->insertToSQL();

if ($id) {
    echo "Administrator account '{$login}' created with ID {$id}.\n";
    exit(0);
} else {
    fwrite(STDERR, "Failed to create administrator account '{$login}'.\n");
    exit(1);
}
