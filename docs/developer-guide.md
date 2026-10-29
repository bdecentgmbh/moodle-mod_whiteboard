---
exclude: true
---

# Whiteboard — Developer guide

How the whiteboard is built, for people changing it or testing it.

## How it works

The whiteboard has no code per whiteboard service. A service is a record in `whiteboard_service`;
an activity stores only the link of its board (`whiteboard.boardurl`).

| Part | Where | What it does |
|---|---|---|
| Services | `classes/service.php` | Finding the service of a link, link parameters, validation, import and export, the services of a new site (`defaults()`), the links of version 1.0 boards (`legacy_board_url()`) |
| Activity form | `mod_form.php` | Accepts a link or an embed code; refuses links that no enabled service matches |
| Saving | `lib.php` | `whiteboard_add_instance()` and `whiteboard_update_instance()` keep only the link of an embed code |
| View page | `view.php`, `templates/whiteboard.mustache` | Shows the board in an iframe, or a button for services shown in a new tab |
| Admin pages | `services.php`, `service.php`, `import.php`, `classes/form/` | The list of services (settings page `modsettingwhiteboard`), adding and editing a service, importing JSON |

### Links

A board link belongs to a service if scheme and host match one of the service's links (case
insensitive) and its path starts with the path of that link (case sensitive). Service links must be
`https://`, a host name and a `/`, so `https://miro.com/` never matches `https://miro.com.example.org/`.
Board links must be `https://` and unchanged by `clean_param(PARAM_URL)`, which refuses quotes,
spaces and other characters that could break out of the `src` attribute; the template escapes them
anyway.

Only boards of enabled services are shown, so disabling a service hides its boards. The link
parameters of a service are added before the fragment, and only those the link does not have.

The iframe allows `fullscreen; clipboard-read; clipboard-write` for every service.

### Upgrade from 1.0

Version 1.0 had `boardtype` (`miro` or `conceptboard`) and `boardid`, and showed
`https://miro.com/app/live-embed/<id>` or `https://app.conceptboard.com/board/<id>`. The upgrade step
turns them into these links, adds the services of a new site (keeping Miro and Conceptboard disabled
if they were) and removes the old settings. Restoring a 1.0 backup converts the fields the same way.

## Tests

- PHPUnit: `tests/service_test.php` (link matching, including links that try to pass for a
  service; parameters; validation; import and export; 1.0 links), `tests/lib_test.php`,
  `tests/backup_restore_test.php` (also a 1.0 backup).
- Behat: `tests/behat/`. The scenarios use a service for `https://example.com/board/`, so that no
  real whiteboard service has to load. The data generator creates services:
  `the following "mod_whiteboard > services" exist`.

## Releases

Push a tag `v<release>` on main, for example `v1.1`. The workflow `.github/workflows/moodle-release.yml`
checks that the tag matches `$plugin->release`, that `CHANGES.md` has a section `## <release>
(<version>)` and that Moodle Plugin CI passed on the tagged commit. It then publishes the version to
the Moodle Marketplace (secret `MOODLE_MARKETPLACE_TOKEN`) and creates the GitHub release with the ZIP.
