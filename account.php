<?php
/*********************************************************************
    account.php

    Discord-only support portal: self-service password/email accounts are
    intentionally disabled. Discord is the only customer identity provider.
*********************************************************************/
require 'client.inc.php';

if (!$thisclient || !$thisclient->isValid() || $thisclient->isGuest())
    Http::redirect('login.php');

Http::redirect('tickets.php');
