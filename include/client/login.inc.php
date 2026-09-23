<?php
if (!defined('OSTCLIENTINC'))
    die('Access Denied');
?>
<div class="discord-login-page">
    <h1><?php echo __('Support'); ?></h1>
    <p><?php echo __('Sign in with Discord to create and manage support tickets.'); ?></p>

    <?php if (!empty($errors['err'])) { ?>
        <div id="msg_error"><?php echo Format::htmlchars($errors['err']); ?></div>
    <?php } ?>

    <?php
    $bk = UserAuthenticationBackend::getBackend('discord');
    if ($bk instanceof ExternalAuthentication) {
        echo '<div class="external-auth">';
        $bk->renderExternalLink();
        echo '</div>';
    }
    ?>

    <p class="discord-login-note">
        <?php echo __('Only members of our Discord server can use this support site.'); ?>
    </p>

    <p>
        <b><?php echo __("I'm an agent"); ?></b> —
        <a href="<?php echo ROOT_PATH; ?>scp/"><?php echo __('sign in here'); ?></a>
    </p>
</div>
