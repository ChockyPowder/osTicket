<?php
/*
 * Discord-only osTicket configuration
 *
 * Copy these constants into include/ost-config.php on your server.
 * Do NOT commit the real client secret.
 */

define('DISCORD_CLIENT_ID', 'YOUR_DISCORD_APPLICATION_CLIENT_ID');
define('DISCORD_CLIENT_SECRET', 'YOUR_DISCORD_APPLICATION_CLIENT_SECRET');
define('DISCORD_GUILD_ID', 'YOUR_DISCORD_SERVER_ID');
define('DISCORD_REDIRECT_URI', 'https://support.example.com/login.php?do=ext&bk=discord');
define('DISCORD_INTERNAL_EMAIL_DOMAIN', 'invalid.example');
