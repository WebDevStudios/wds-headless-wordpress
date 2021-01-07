# WDS Single Sign-on

WDS SSO includes two components, one for the client, one for the proxy. The goal of this plugin is to provide single sign-on processes for the WDS corporate network.

This plugin, when installed and activated along with the [WDS Add-on Plugin](https://github.com/WebDevStudios/sso-addon), will provide a WebDevStudios Login button on the login page.

_____________

# Installation

No need to clone the repo to install the plugin, unless you are developing the plugin itself!

See [Installation Setup](https://github.com/WebDevStudios/wds-sso/wiki/Installation-Setup)

_____________

# Changelog

## 2.0.1

- Removed vendor directory from git for automatic composer inclusion.
- Fixed PHPCS and eslintjs rules and formatting.
- Re-added distribute-zip.sh for zipfile creation with vendor libs included.

## 2.0.0

- Moved WDS specific code to the wds-sso-addon-wds plugin.
- Replaced all WDS specific code with sane defaults and/or filters.
- Make client and proxy both work on the same site
- Combined both proxy & client plugins so they're both active at once.
- Add insecure HTTP support through a filter

## 1.3.2

- Adds `wds_sso_skip_expired_session_logout` filter and `WDS_SSO_SKIP_EXPIRED_SESSION_LOGOUT` constant to allow session expiration to be disabled <sup>[#221](https://github.com/WebDevStudios/wds-sso/issues/221), [project-maintainn.com:#69](https://github.com/WebDevStudios/project-maintainn.com/issues/69)</sup>

## 1.3.1

- Fix add/remove of legacy encryption domain list

## 1.3.0

- Updated composer dependencies and added Firebase JWT library [#89](https://github.com/WebDevStudios/wds-sso/issues/89).
- Added backwards-compatible JWT support (old sites will use old in-house encode/decode).
- Make sure JWT library is loaded for the client (shared library / login screen)
- Added vendor libs to distributable client zip builder
- Move state part into main payload object
- Refined WDS Key handling/display
- Make sure SSO key save JS is enabled on proxy as well
- Remove unused hide_key function

This is a fairly major update that adds industry standard JWT encryption support.
The old (homebrew) encryption is left in place for backwards compatibility but is
now deprecated and will be removed (possibly in 1.4.0) once all client plugins have
been updated.

## 1.2.5

- Increased login time from 2 hours to 8 hours

This minor update fixes a major issue that I've been seeing among people using WDS SSO,
they login and work for 2, 3, 5 hours straight and they keep having to log back in. This
is a good thing, as the 2 hour limit was meant for a user to have limited ability if they
get removed from Google Apps, but I think 8 hours (a work day) is more practical.

## 1.2.4

- Added caching rules that can help keep `wp-login.php` from being cached in-browser.
- Plugin will no longer deactivate anytime a user reaches the site and does not use `https`, a warning will now show instead #151
- Plugin will now notice all the time if proxy is present (it shouldn't, so you should know about it all the time)
- Compatibility with plugins the set `redirect_to` to a non `https://` URL will fail SSO, so we've added a fix that will catch this and ensure it's set to something `https://`
- Fixes to issue with `redirect_to=` and WPE caching, no more failed login attempts on WPE!

## 1.2.3

- Fixed issue with redeclaration of plugin sandbox [19c70f9e](https://github.com/WebDevStudios/wds-sso/commit/19c70f9e9796d938cbb7538c31666663d498fe3e)
- Fixed issue where some CSS for the login screen was being enqueued on the frontend [ada1f0ad](https://github.com/WebDevStudios/wds-sso/commit/ada1f0ad68dd3e23261b4a79971c0b0d59c13175)

## 1.2.2

- Don't allow activation w/out HTTPS/SSL #148
- Add notice when client and proxy are installed #145
- Notice about selective roles #139
- Disable password reset for SSO Users #140

## 1.2.1

- When deactivating the plugin, you will be asked to keep or ditch WDS SSO Users #136

## 1.2.0

- This release brings in more controlled user roles, before 1.2.0, any user signed in would be automatically granted super-admin privilege #112
- [Advanced role mapping](https://github.com/WebDevStudios/wds-sso/search?utf8=%E2%9C%93&q=This+filter+makes+it+easy+%28programatically%29+to+assign&type=) to decide what users get what roles on what sites
- PHP 7.2 compatibility #128
- Easier WDS SSO KEY assignment. #127
- Some [minor fixes](https://github.com/WebDevStudios/wds-sso/milestone/9?closed=1)

## 1.1.0

- SSO Users can now edit other user's passwords and email, but not their own #103
- SSO Users can now access all sites in multisite upon login #94
- The client-side plugin, on multisite, can only be activated at the Network level now #56
- When you deactivate the client-side plugin we're sure your posts are okay and they'll just get re-assigned to `wds_author` #104

## 1.0.1

- Fix to failed login attempts on reauth. #90

## 1.0.0

* Creates Google Authenticated users as Administrators or Super Administrators upon successful authorization
* Re-assigns GA user content to a fake user upon plugin deactivation, in order to preserve content
* Multi-site compatible
* Secure key transmission using two-way data encryption

Brought to you by Team Hasselhoff @JayWood @jrfoell @kailanwyatt @PavelK27 @aubreypwd @thecxguy
