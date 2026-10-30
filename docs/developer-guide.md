---
exclude: true
---

# Whiteboard — Developer guide

How the whiteboard is built, for people changing it or testing it.

## How it works

The whiteboard has no code per whiteboard service. A service is a record in `whiteboard_service`;
an activity stores only the link of its board (`whiteboard.boardurl`) and the links of the boards
of groups and users that teachers add on its *Boards* page (`whiteboard_board`, with either a
`groupid` or a `userid`; the other one is 0).

| Part | Where | What it does |
|---|---|---|
| Services | `classes/service.php` | Finding the service of a link, link parameters, validation, import and export, the services of a new site (`defaults()`), the links of version 1.0 boards (`legacy_board_url()`) |
| Group and user boards | `classes/boards.php`, `db/events.php` | Saving and listing the boards of groups and users, the groups and users a teacher can manage (only their own groups in separate groups without `moodle/site:accessallgroups`, as for overrides), deleting the boards of deleted groups and users and, on course reset, of all users |
| Boards page | `boards.php`, `board.php`, `classes/form/board_form.php`, `templates/boards_actions.mustache` | Lists the boards of groups or users (`mode=group` or `user`) with a menu to switch and a button to add one, like the overrides of assignments; adding, editing and deleting. Linked in the activity's secondary navigation (`whiteboard_extend_settings_navigation()`, key `mod_whiteboard_boards`) for `mod/whiteboard:manageboards` |
| Activity form | `mod_form.php` | Accepts a link or an embed code; refuses links that no enabled service matches (`service::check_input()`, also used by the board form) |
| Saving | `lib.php` | `whiteboard_add_instance()` and `whiteboard_update_instance()` keep only the link of an embed code; course reset deletes the boards of users (`reset_whiteboard_userboards`) |
| View page | `view.php`, `templates/whiteboard.mustache` | The user's own board; otherwise the core group selector in group mode and the board of the current group, or the whiteboard's board. The board in an iframe, or a button for services shown in a new tab; theatre mode (`full=1`: popup layout, no navigation footer on 5.3) |
| Privacy | `classes/privacy/provider.php` | The boards of users are personal data: exported and deleted per user and context |
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
  service; parameters; validation; import and export; 1.0 links), `tests/boards_test.php` (lists,
  choices, separate groups, deleting), `tests/lib_test.php`, `tests/backup_restore_test.php` (group
  boards in copies and duplicates, user boards only with user data, a 1.0 backup),
  `tests/privacy/provider_test.php`.
- Behat: `tests/behat/`. The scenarios use a service for `https://example.com/board/`, so that no
  real whiteboard service has to load. The data generator creates services and boards:
  `the following "mod_whiteboard > services" exist` (name, links), `the following
  "mod_whiteboard > boards" exist` (whiteboard by name, group by idnumber or user by username,
  boardurl).

## Releases

Push a tag `v<release>` on main, for example `v1.1`. The workflow `.github/workflows/moodle-release.yml`
checks that the tag matches `$plugin->release`, that `CHANGES.md` has a section `## <release>
(<version>)` and that Moodle Plugin CI passed on the tagged commit. It then publishes the version to
the Moodle Marketplace (secret `MOODLE_MARKETPLACE_TOKEN`) and creates the GitHub release with the ZIP.
