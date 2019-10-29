# DM Base

Base functionality, helpers and modifications to WordPress for Delicious Media projects.

### Changes to WordPress.

- Disables comment/trackback functionality (Toggle with `DM_DISABLE_COMMENTS`; default true).
- Disables builtin search functionality (Toggle with `DM_DISABLE_SEARCH`; default false).
- Disables output of emoji styles/scripts (Toggle with `DM_DISABLE_EMOJIS`; default true) ☹️.
- Disables RSS feeds (Toggle with `DM_DISABLE_RSS`; default true).
- Prevents anonymous access to the REST API (Toggle with `DM_DISABLE_REST_ANON`; default true). By filtering `dm_allowed_anonymous_restnamespaces` individual namespaces can be whitelisted.
- Modifies plugin install screen to show our recommended plugins first (Toggle with `DM_MODIFY_PLUGINS_SCREEN`; default true).
- Removes XMLRPC functionality; X-Pingback headers; tidies up wp_head();
- Prevents enumeration of usernames via ?author=n query strings, helpful for tickybox PCI audits (toggled with `DM_PREVENT_USER_ENUM`; defaults true).

### Additional functionality

- Tracks last login times for users accounts (Toggle with `DM_LASTLOGIN`; defaults to true).
- Adds logging, fatal error handler.
- Adds an environment indicator to the adminbar.
- Adds dm_developer role.
- Ability to force enable/disable plugins by environment (or other arbitrary rules).

## Installation

Install via Composer (`composer require deliciousmedia/dm-base`), or just clone/copy the files to your mu-plugins folder.

---
Built by the team at [Delicious Media](https://www.deliciousmedia.co.uk/), a specialist WordPress development agency based in Sheffield, UK.