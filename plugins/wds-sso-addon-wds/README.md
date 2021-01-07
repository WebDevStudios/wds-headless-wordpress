# WebDevStudios configuration for WDS SSO

This Add-On accompanies the main [WDS-SSO Plugin](https://github.com/WebDevStudios/sso). The main plugin is required to be installed and activated in order for this one to do anything. When both are activated Single Sign-On logins are provided using your WDS google account via the WDS-SSO proxy server. It is a low configuration setup - the only thing that will be required is the WDS SSO KEY from 1Password. You will be prompted to add it upon initial activation.

# Changelog

## 1.0.1

- Changed textdomain and folder to wds-sso-addon-wds.
- Fixed PHPCS and eslintjs rules and formatting.
- Requires WDS-SSO Plugin version 2.0.1 (enforced via composer).
- Updated proxy address to sso.webdevstudios.com (WPEngine).

## 1.0.0

- Initial release
- Contains all of the WDS specific code that used to be in the main plugin.
- Requires WDS-SSO Plugin version 2.0.0 or higher.
