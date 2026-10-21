#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Local administrator accounts were removed from ClientZone.
 * Configure the portal via /etc/abraflexi-client-zone/.env (SSH)
 * and manage data in AbraFlexi.
 */

fwrite(STDERR, "abraflexi-clientzone: create-admin.php is obsolete.\n");
fwrite(STDERR, "There is no in-app administrator role. Edit .env over SSH or use AbraFlexi.\n");
exit(1);
