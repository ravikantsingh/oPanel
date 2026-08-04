<?php
// /opt/panel/www/pma/config.inc.php
declare(strict_types=1);

$cfg['blowfish_secret'] = 'x8y9z0A1b2C3d4E5f6G7h8I9j0K1l2M3'; 

$i = 0;
$i++;

/* === ENTERPRISE SIGNON CONFIGURATION === */
$cfg['Servers'][$i]['auth_type'] = 'signon';
$cfg['Servers'][$i]['SignonSession'] = 'SignonSession';
$cfg['Servers'][$i]['SignonURL'] = 'signon.php';

/* Server parameters */
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;

// The tree will expand perfectly because MySQL is enforcing isolation!
$cfg['NavigationTreeEnableGrouping'] = false;

$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';
?>