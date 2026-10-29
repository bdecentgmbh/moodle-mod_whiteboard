---
pagetypes:
  - admin-mod-whiteboard-*
  - mod-whiteboard-*
---

# Whiteboard — Administrator guide

Whiteboard (`mod_whiteboard`) is an activity that shows a board of an online whiteboard service,
such as Miro or TaskCards, in a course. The boards stay in the service; the activity keeps only
their links. You decide which services teachers can use, and you can add any service yourself,
without changes to the plugin. For using it in a course, see the
[teacher guide](teacher-guide.md).

## Requirements

Moodle 4.5 to 5.3. No other plugins are needed. Teachers need an account with the whiteboard
service they use; whether students need one depends on the service and on how the board is shared.

## Installation

1. Download the plugin or clone it into `mod/whiteboard` (`public/mod/whiteboard` from Moodle 5.1).
2. Go to *Site administration > Notifications* to complete the installation.

The whiteboard is ready to use right away, with the services listed under
[Services known to work](#services-known-to-work).

## Upgrading to 1.1

- Moodle 4.5 is now the minimum.
- Teachers paste the link of a board, or its embed code, instead of choosing a type and entering a
  board ID. Existing whiteboards get the links that version 1.0 showed, and keep working.
- The settings *Enable MIRO board type*, *Enable Concept board type* and *Default board* are
  replaced by the list of whiteboard services. Miro and Conceptboard stay disabled where you had
  disabled them. Collaboard and TaskCards are added and enabled.
- Miro boards show the board right away instead of a preview that has to be clicked first.
- Conceptboard boards open in a new tab: Conceptboard does not allow other websites to show its
  boards any more (GitHub issue #1).

## Whiteboard services

Go to *Site administration > Plugins > Activity modules > Whiteboard*. The list shows each service
with its links, how its boards are shown and how many activities use it.

A service has:

- **Name**: shown to teachers in the activity settings.
- **Links**: the beginnings of the service's board links, one per line, for example
  `https://miro.com/app/board/`. A board link belongs to the service if it starts with one of them.
  Each must start with `https://`, the address of the service and a `/`, so that no other website
  can pass for the service.
- **Link parameters**: added to every board link of the service, unless the link has them already,
  for example `autoplay=true` for Miro. Separate several with `&`.
- **Display**: *In the page* shows the board in the activity page, with a link to open it in a new
  tab below. *In a new tab* shows a button that opens the board in a new tab instead; choose it for
  services that do not allow other websites to show their boards.
- **Enabled**: teachers can only add boards of enabled services.

Disabling or deleting a service hides the boards of its activities: students see a notice instead,
teachers are asked to change the link or contact you. Enabling the service again brings them back.

### Adding a service

1. Create a test board in the service and share it the way teachers will, for example with "anyone
   with the link". Copy its link, or its embed code if the service has one.
2. Add a service with the part of the link that is the same for all boards, for example
   `https://www.taskcards.de/#/board/` for `https://www.taskcards.de/#/board/43cc9837-…`. Choose
   *In the page*.
3. Add a whiteboard with the test board in a test course, and open it in a private browser window
   where you are not signed in to the service.
4. If the board stays empty or shows an error instead of the board or a sign-in page, the service
   does not allow other websites to show its boards: change the display to *In a new tab*.

### Import and export

*Export* downloads all services as JSON; *Import* adds services from such a file to another site.
A service with the name of an existing service replaces its settings. You can also paste a single
service:

```json
{
    "name": "WBO",
    "links": ["https://wbo.example.org/boards/"],
    "params": "",
    "display": "embed",
    "enabled": true
}
```

`display` is `embed` (in the page) or `newtab`. Replace `wbo.example.org` with the address of your
own WBO.

## Services known to work

> **This list is only correct at the time of writing (October 2026).** Whiteboard services change
> their websites, plans and rules without notice, and we cannot keep track of them. A service that
> works today may stop working tomorrow, or start working. Check a service with a test board before
> you rely on it, and tell us on GitHub when something has changed.

These were checked with free accounts and boards shared with anyone who has the link, in Chrome
without being signed in to the service.

| Service | Board links teachers paste | Display | Notes |
|---|---|---|---|
| Miro | `https://miro.com/app/board/…`, or the embed code from *Share > Embed* (`https://miro.com/app/live-embed/…`) | In the page | Link parameter `autoplay=true`: without it, embedded boards first show a preview that has to be clicked. Visitors who are not signed in see the board read-only unless it is shared for editing. |
| TaskCards | `https://www.taskcards.de/#/board/…`, or the embed code | In the page | |
| Collaboard | The embed code, or its link `https://web.collaboard.app/share/…` | In the page | With a free plan, guests are not allowed: everybody has to sign in to Collaboard in the board. |
| Conceptboard | `https://app.conceptboard.com/board/…` | In a new tab | Conceptboard does not allow other websites to show its boards, also not boards shared with anyone who has the link. |
| WBO | `https://<your WBO>/boards/…` | In the page | Not included. An open-source whiteboard that you can host yourself; anyone with the link of a board can draw on it, without an account. Checked with the public demo at wbo.ophir.dev; for your organisation, host your own and add it with its address. |
| Zoom Whiteboard | — | In a new tab | Not included. Zoom does not allow other websites to show its boards. You can add it as a service *In a new tab* with the beginning of your share links. |

## Permissions

| Capability | Default roles | Allows |
|---|---|---|
| `mod/whiteboard:addinstance` | Editing teacher, Manager | Adding a whiteboard to a course |
| `mod/whiteboard:view` | Guest, Authenticated user | Viewing a whiteboard |

Managing the services needs `moodle/site:config`.

## Privacy

The whiteboard stores no personal data in Moodle. When users open a board, their browser connects to
the whiteboard service directly, which receives at least their IP address and may set cookies or ask
them to sign in. Choose services that your organisation's data protection rules allow, for example
with a data processing agreement, and disable the others.

## Backup and restore

Whiteboards are backed up and restored with their name, description and board links, also when
importing or duplicating them. The links of group boards are restored for the copies of the groups,
when the groups are restored too. The boards themselves stay in the whiteboard service and are not
backed up. Backups of version 1.0 are restored with the board link.

## Activities overview (Moodle 5)

From Moodle 5.0, the list of whiteboards of a course (`mod/whiteboard/index.php`) leads to the
course's activities overview page.
