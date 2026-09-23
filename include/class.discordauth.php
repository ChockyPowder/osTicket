<?php
/**
 * Discord-only client authentication for the ChockyPowder osTicket fork.
 *
 * Required constants in include/ost-config.php:
 *   DISCORD_CLIENT_ID
 *   DISCORD_CLIENT_SECRET
 *   DISCORD_GUILD_ID
 *   DISCORD_REDIRECT_URI
 *
 * OAuth scopes: identify + guilds.members.read.
 */
class DiscordAuthenticationBackend extends ExternalUserAuthenticationBackend {
    static $id = 'discord';
    static $service_name = 'Discord';
    static $fa_icon = 'comments';

    private function configured() {
        return defined('DISCORD_CLIENT_ID')
            && defined('DISCORD_CLIENT_SECRET')
            && defined('DISCORD_GUILD_ID')
            && defined('DISCORD_REDIRECT_URI')
            && DISCORD_CLIENT_ID && DISCORD_CLIENT_SECRET
            && DISCORD_GUILD_ID && DISCORD_REDIRECT_URI;
    }

    function renderExternalLink() {
        $service = __('Login with Discord');
        ?>
        <a class="external-sign-in discord-sign-in"
           title="<?php echo Format::htmlchars($service); ?>"
           href="<?php echo ROOT_PATH; ?>login.php?do=ext&amp;bk=<?php echo urlencode($this->getBkId()); ?>">
            <div class="external-auth-box">
                <span class="external-auth-icon">
                    <i class="icon-<?php echo static::$fa_icon; ?> icon-large icon-fixed-with"></i>
                </span>
                <span class="external-auth-name">
                    <?php echo Format::htmlchars($service); ?>
                </span>
            </div>
        </a>
        <?php
    }

    function triggerAuth() {
        if (!$this->configured())
            throw new AccessDenied(__('Discord login has not been configured by the administrator.'));

        if (isset($_GET['code']))
            return $this->handleCallback();

        $state = bin2hex(random_bytes(32));
        $_SESSION['discord_oauth_state'] = $state;

        $params = array(
            'client_id' => DISCORD_CLIENT_ID,
            'redirect_uri' => DISCORD_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'identify guilds.members.read',
            'state' => $state,
            'prompt' => 'consent',
        );

        Http::redirect(
            'https://discord.com/oauth2/authorize?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986)
        );
    }

    private function handleCallback() {
        $state = isset($_GET['state']) ? (string) $_GET['state'] : '';
        $expected = isset($_SESSION['discord_oauth_state'])
            ? (string) $_SESSION['discord_oauth_state'] : '';

        unset($_SESSION['discord_oauth_state']);

        if (!$state || !$expected || !hash_equals($expected, $state))
            throw new AccessDenied(__('Invalid Discord login session. Please try again.'));

        if (isset($_GET['error']))
            throw new AccessDenied(__('Discord login was cancelled.'));

        $code = isset($_GET['code']) ? (string) $_GET['code'] : '';
        if (!$code)
            throw new AccessDenied(__('Discord did not return an authorization code.'));

        $token = $this->requestJson(
            'https://discord.com/api/v10/oauth2/token',
            array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => DISCORD_REDIRECT_URI,
            ),
            true
        );

        if (empty($token['access_token']))
            throw new AccessDenied(__('Unable to authenticate with Discord.'));

        $access = $token['access_token'];
        $discordUser = $this->requestJson(
            'https://discord.com/api/v10/users/@me',
            null, false, $access
        );

        if (empty($discordUser['id']))
            throw new AccessDenied(__('Unable to identify your Discord account.'));

        $member = $this->requestJson(
            'https://discord.com/api/v10/users/@me/guilds/'.rawurlencode(DISCORD_GUILD_ID).'/member',
            null, false, $access, true
        );

        if (!$member || empty($member['user']['id']))
            throw new AccessDenied(__('You must be a member of our Discord server to use this support site.'));

        $discordId = preg_replace('/[^0-9]/', '', (string) $discordUser['id']);
        if (!$discordId)
            throw new AccessDenied(__('Invalid Discord account.'));

        $emailDomain = defined('DISCORD_INTERNAL_EMAIL_DOMAIN')
            ? DISCORD_INTERNAL_EMAIL_DOMAIN : 'invalid.example';
        $email = 'discord-'.$discordId.'@'.$emailDomain;

        $name = !empty($discordUser['global_name'])
            ? $discordUser['global_name']
            : (!empty($discordUser['username'])
                ? $discordUser['username'] : 'Discord User '.$discordId);

        $user = User::fromVars(array('email' => $email, 'name' => $name));
        if (!$user)
            throw new AccessDenied(__('Unable to create your support account.'));

        $acct = $user->getAccount();
        if (!$acct) {
            $errors = array();
            $acct = ClientAccount::register($user, array(
                'backend' => $this->getBkId(),
                'username' => 'discord-'.$discordId,
            ), $errors);
            if (!$acct || $errors)
                throw new AccessDenied(__('Unable to create your support account.'));
        } else {
            $acct->set('backend', $this->getBkId());
            $acct->set('username', 'discord-'.$discordId);
            $acct->save();
        }

        $_SESSION['discord_user_id'] = $discordId;

        $client = new ClientSession(new EndUser($user));
        if (!$this->login($client, $this))
            throw new AccessDenied(__('Unable to start your support session.'));

        unset($access, $token);
        Http::redirect(ROOT_PATH.'tickets.php');
    }

    private function requestJson($url, $data=null, $basicAuth=false, $bearer=null, $allow404=false) {
        if (!function_exists('curl_init'))
            throw new AccessDenied(__('The server is missing the PHP cURL extension.'));

        $ch = curl_init($url);
        $headers = array('Accept: application/json');

        if ($data !== null) {
            $body = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if ($basicAuth)
            curl_setopt($ch, CURLOPT_USERPWD, DISCORD_CLIENT_ID.':'.DISCORD_CLIENT_SECRET);
        elseif ($bearer)
            $headers[] = 'Authorization: Bearer '.$bearer;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error)
            throw new AccessDenied(__('Unable to contact Discord. Please try again.'));

        if ($allow404 && $status === 404)
            return null;

        if ($status < 200 || $status >= 300)
            throw new AccessDenied(__('Discord authentication could not be completed.'));

        $json = json_decode($response, true);
        if (!is_array($json))
            throw new AccessDenied(__('Discord returned an invalid response.'));

        return $json;
    }
}

UserAuthenticationBackend::register('DiscordAuthenticationBackend');
?>
