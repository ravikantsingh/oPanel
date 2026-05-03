<?php
// /opt/panel/www/views/components/modals.php

// 1. Domains & Web
include 'views/modals/addDomainModal.php';
include 'views/modals/changePhpModal.php';
include 'views/modals/softwareCenterModal.php';
include 'views/modals/phpSettingsModal.php';
include 'views/modals/installSslModal.php';
include 'views/modals/installWpModal.php';
include 'views/modals/nodeJsModal.php';

// 2. Users, Databases & FTP
include 'views/modals/addUserModal.php';
include 'views/modals/addDbModal.php';
include 'views/modals/changeDbPassModal.php';
include 'views/modals/ftpModal.php';

// 3. File Management & Git
include 'views/modals/fileManagerModal.php';
include 'views/modals/rotateFmPassModal.php';
include 'views/modals/gitModal.php';

// 4. Security & Network
include 'views/modals/firewallModal.php';
include 'views/modals/dnsRecordModal.php';
include 'views/modals/wafRulesModal.php';
include 'views/modals/fail2banStatusModal.php';

// 5. Backups & Cron
include 'views/modals/backupWebModal.php';
include 'views/modals/backupDbModal.php';
include 'views/modals/scheduleBackupModal.php';
include 'views/modals/uploadBackupModal.php';
include 'views/modals/addCronModal.php';

// 6. System & Logs
include 'views/modals/logModal.php';
include 'views/modals/taskLogModal.php';
include 'views/modals/connectionInfoModal.php';
include 'views/modals/adminProfileModal.php';
include 'views/modals/systemSettingsModal.php';
include 'views/modals/pmaSettingsModal.php';
include 'views/modals/brandingModal.php';

//7. Mail
include 'views/modals/mailBoxModal.php';
?>