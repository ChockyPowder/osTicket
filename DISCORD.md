# Discord-only ticket portal

This fork changes the customer portal to use Discord OAuth2 instead of
osTicket username/password authentication.

## Customer flow

1. Visit the support site.
2. Click Login with Discord.
3. Discord authenticates the user.
4. The site asks Discord whether the user is a member of the configured server.
5. Members are automatically given an osTicket client account and sent to the
   ticket portal.
6. Non-members are denied access.

Customers do not need an osTicket password or email login.

## Discord application setup

Create an application in the Discord Developer Portal and configure a redirect
URI matching DISCORD_REDIRECT_URI.

The customer OAuth scopes used by this fork are:
- identify
- guilds.members.read

The second scope is used to check membership in the configured server.

## Server configuration

Add the constants from include/discord-config.example.php to
include/ost-config.php and replace the placeholder values.

Never commit DISCORD_CLIENT_SECRET.

The site uses an internal address such as
discord-123456789@example.invalid because the osTicket client schema expects
an email address. It is not used as a login credential and is not intended
for mail delivery.

## Email

Customer authentication and ticket communication are web-only. The Discord
fork does not expose customer email/password login or customer
self-registration.

For a completely mail-free deployment, leave osTicket SMTP/default-email
accounts unconfigured and disable ticket/message autoresponders and alerts in
the admin settings. The underlying osTicket mail code is intentionally left
intact so the staff/admin system is not unnecessarily forked further.

## Staff

Staff continue to use the normal /scp/ staff panel. This change only replaces
customer portal authentication.
