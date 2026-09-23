<?php
/*********************************************************************
    login.php

    Discord-only client authentication for this fork.
*********************************************************************/
require_once('client.inc.php');

if (!defined('INCLUDE_DIR'))
    die('Fatal Error');

require_once(INCLUDE_DIR.'class.client.php');
require_once(INCLUDE_DIR.'class.ticket.php');

$suggest_pwreset = false;
$inc = 'login.inc.php';

if (isset($_GET['do']) && $_GET['do'] === 'ext') {
    if (!isset($_GET['bk']) || $_GET['bk'] !== 'discord')
        $errors['err'] = __('Invalid authentication provider.');
    elseif ($bk = UserAuthenticationBackend::getBackend('discord')) {
        try {
            $bk->triggerAuth();
        } catch (AccessDenied $e) {
            $errors['err'] = $e->reason;
        }
    } else {
        $errors['err'] = __('Discord authentication is unavailable.');
    }
}

if ($thisclient && $thisclient->isValid() && !$thisclient->isGuest())
    Http::redirect('tickets.php');

if (!$nav) {
    $nav = new UserNav();
    $nav->setActiveNav('status');
}

require CLIENTINC_DIR.'header.inc.php';
require CLIENTINC_DIR.$inc;
require CLIENTINC_DIR.'footer.inc.php';
?>
