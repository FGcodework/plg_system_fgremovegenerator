# Changelog – plg_system_fgremovegenerator

## 1.5.4 (2026-09-24)
- Added a "Plugin" info tab with a "Support this plugin" note (Ko-fi + fgcodework.github.io links), matching plg_system_fgemailremover: the `basic` fieldset now holds only a `type="note"` field, rendered by Joomla as the default info tab.
- Merged all functional fields into a single `settings` fieldset (second tab, labeled "Settings").
- Removed the unused `FIELDSET_HEADERS` language key.
- README: added Ko-fi badge + "Support this project" section.

## 1.5.3 (2026-09-18)
- Fixed JED naming convention violation: display name changed from "FG - Remove Generator" to "System - FG Remove Generator" (en) / "Systém - FG Odstránenie Generator" (sk). JED requires plugin names in the form "{Type} - {Extension Name}", where {Type} must be the plugin group (System, Content, ...) — not a brand prefix. Matches the convention already used in plg_content_fgautolightbox ("Content - FG AutoLightbox").

## 1.5.2 (2026-08-26)
- Moved "Remove X-Powered-By header" from the `basic` fieldset into the `headers` fieldset, alongside "Remove X-Generator header". All three header toggles now live together, separate from the generator-meta-tag settings — UX/organization fix only, no behavior change.

## 1.5.1 (2026-08-26)
- **Removed the `X-AspNet-Version` toggle entirely** (field, PHP logic, language strings, README). It never applied to a PHP/Joomla stack in the first place; where such a header does appear, it's typically added by IIS, a reverse proxy, or another upstream layer *after* PHP has finished, so `header_remove()` could not reliably remove it. Keeping a toggle that may silently do nothing was misleading — the plugin now only offers controls for headers Joomla/PHP can actually influence: the generator meta tag, `X-Powered-By`, and `X-Generator`.

## 1.5.0 (2026-08-26)
- **Breaking / platform change:** dropped Joomla 4 support. `updates.xml` targetplatform narrowed from `[456]` to `[56]`.
- Switched from the generic `Joomla\Event\EventInterface` to the concrete, typed Joomla event classes `Joomla\CMS\Event\Application\BeforeCompileHeadEvent` and `Joomla\CMS\Event\Application\BeforeRespondEvent` (confirmed via the official Joomla 6.0.x API docs: both classes exist `since 5.0.0`, i.e. not available in Joomla 4). This gives explicit typed arguments (`$event->getApplication()`, `$event->getDocument()`), better static analysis, and better IDE autocomplete, at the cost of Joomla 4 compatibility.
- `onBeforeCompileHead` now reads the application/document from the event instead of `$this->getApplication()`.
- Raised `<php_minimum>` from `8.0.0` to `8.1.0` (Joomla 5/6 baseline).

## 1.4.1 (2026-08-26)
- Clarified that "Apply generator setting to Administrator" (renamed from "Apply in administrator") only controls the generator meta tag — the X-Powered-By / X-Generator / X-AspNet-Version headers are, and always were, removed everywhere (including the administrator) once their individual toggles are enabled. This is a documentation/labeling fix only; behavior is unchanged.

## 1.4.0 (2026-08-26)
- **Fix (high priority):** moved fingerprinting-header removal from `onAfterInitialise` to `onBeforeRespond`. `onAfterInitialise` fires very early in the request lifecycle — routing, the component, the template, or another plugin can all still set/re-set `X-Powered-By`, `X-Generator`, etc. *after* that point, so the plugin could not actually guarantee removal. `onBeforeRespond` fires immediately before Joomla sends the HTTP response, which is the correct place to guarantee the header is gone.
- Removed the `onAfterInitialise` event subscription entirely (no longer needed — `onBeforeRespond` replaces it, not supplements it)
- Updated README wording that previously (incorrectly) described early removal as the stronger approach

## 1.3.0 (2026-08-26)
- Rebranded into the FG series as `plg_system_fgremovegenerator`
- Namespace changed to `FG\Plugin\System\Fgremovegenerator`, class renamed to `Fgremovegenerator`
- Added `declare(strict_types=1)`; `onAfterInitialise`/`onBeforeCompileHead` now type-hinted against `Joomla\Event\EventInterface` instead of the concrete `Event` class
- Added `<updateservers>` block (points at `updates.xml` on the `master` branch of `FGcodework/plg_system_fgremovegenerator`), with `<client>site</client>` declared on the plugin's `<update>` entry
- Declared `<php_minimum>8.0.0</php_minimum>`
- Added GitHub-ready repo scaffolding: README.md with shields.io badges, LICENSE (GPL-2.0-or-later), .gitignore, assets/logo.png (navy/coral FG brand style, colors sampled from plg_fgeditorswitcher) + its generation script
- Fixed logo colors to match the actual FG brand (navy gradient #081D32→#113758, coral #FF6B4A) after the first draft used the wrong palette
- No functional/behavioral change to header or meta-tag removal logic

## 1.2.0 (2026-07-27)
- Added independent toggles for removing the `X-Generator` and `X-AspNet-Version` HTTP headers (both default off)
- Refactored `onAfterInitialise` to remove headers via a param → header map instead of separate conditionals
- Deliberately did **not** add removal of `X-Content-Type-Options` — that is a genuine security header (`nosniff`), not a fingerprinting leak; removing it would reduce security

## 1.1.0 (2026-07-27)
- Added removal of the `X-Powered-By` HTTP header (PHP) via `header_remove()` on `onAfterInitialise`
- New parameter: "Remove X-Powered-By header" (default on)

## 1.0.0 (2026-07-17)
- Initial release: native Joomla 6 system plugin (PSR-4, `SubscriberInterface`, DI container)
- Removes the Joomla generator meta tag via `onBeforeCompileHead` / `setGenerator('')`
- Optional mode: remove completely, or replace with custom text
- Optional: also apply in the administrator backend
- Languages: en-GB, sk-SK
