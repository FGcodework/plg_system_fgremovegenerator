<p align="center">
  <img src="assets/logo.png" alt="FG Remove Generator logo" width="128" height="128">
</p>

<h1 align="center">FG Remove Generator</h1>

<p align="center">
  <img src="https://img.shields.io/github/v/tag/FGcodework/plg_system_fgremovegenerator?label=version&color=ff6b4a" alt="Version">
  <img src="https://img.shields.io/badge/Joomla-5%20%7C%206-1a6877?logo=joomla&logoColor=white&color=blue" alt="Joomla 5/6">
  <img src="https://img.shields.io/badge/PHP-8.1+-777bb4?logo=php&logoColor=white&color=purple" alt="PHP 8.1+">
  <a href="https://extensions.joomla.org/extension/site-management/seo-a-metadata/fg-remove-generator/"><img src="https://img.shields.io/badge/Joomla!%20Extensions%20Directory%E2%84%A2-RemoveGenerator-blue" alt="JED"></a>
  <img src="https://img.shields.io/badge/license-GPL--2.0+-green" alt="License">
  <img src="https://img.shields.io/github/downloads/FGcodework/plg_system_fgremovegenerator/total" alt="Downloads">
  <a href="https://ko-fi.com/FGcodework"><img src="https://img.shields.io/badge/support-Ko--fi-F16061.svg?logo=ko-fi&logoColor=white" alt="Support on Ko-fi"></a>
</p>

A native Joomla 5/6 system plugin that removes the Joomla **generator meta tag**
(`<meta name="generator" content="Joomla! - Open Source Content Management">`) and,
optionally, common **fingerprinting HTTP response headers**:

- `X-Powered-By` (sent by PHP)
- `X-Generator` (sometimes sent by templates/extensions)

## Why

Fingerprinting headers and meta tags make it trivial for automated scanners to
identify your CMS/PHP version and target known vulnerabilities. Removing them
is a small, low-risk hardening step (**security through obscurity is not a
substitute for keeping Joomla/PHP updated**, but it does raise the bar for
casual automated scanning).

> **Note:** this plugin deliberately does **not** touch `X-Content-Type-Options`.
> That header (`nosniff`) is a genuine security control, not a fingerprinting
> leak — removing it would *reduce* security rather than improve privacy.

## Features

- Remove the generator meta tag completely, or replace it with custom text
- Optional: also remove/replace the generator meta tag in the administrator backend (the HTTP headers below are always removed everywhere when enabled, regardless of this setting)
- Optional, independent toggles for `X-Powered-By`, `X-Generator`
- Headers are removed on `onBeforeRespond` — right before Joomla sends the HTTP response, so nothing set later by a component, plugin, or template slips through
- PSR-4, `SubscriberInterface`, DI container (`services/provider.php`)
- Uses concrete, typed Joomla event classes (`BeforeCompileHeadEvent`, `BeforeRespondEvent`) instead of the generic `EventInterface` — requires Joomla 5.0+, not compatible with Joomla 4
- English + Slovak (sk-SK) language files

## Installation

1. Download the latest release ZIP from the [Releases](https://github.com/FGcodework/plg_system_fgremovegenerator/releases) page.
2. In Joomla admin: **System → Install → Extensions**, upload the ZIP.
3. Enable the plugin: **System → Manage → Plugins → System - FG Remove Generator**.
4. Configure mode and header toggles as needed.

## Updates

This extension ships with a Joomla update server (`updates.xml`) pointing at
the `master` branch of this repository, so new versions appear under
**System → Update → Extensions** once installed.

## Support this project

This plugin is free, open source, and always will be — no feature is locked behind a paywall. If it's saved you a scanner-fingerprinting headache, you can leave a one-off tip on Ko-fi. Entirely optional either way.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/FGcodework)

## License

GNU General Public License version 2 or later. See [LICENSE](LICENSE).
