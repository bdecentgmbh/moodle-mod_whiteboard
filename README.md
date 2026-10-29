# Whiteboard

[![Moodle plugin CI](https://github.com/bdecentgmbh/moodle-mod_whiteboard/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/bdecentgmbh/moodle-mod_whiteboard/actions/workflows/moodle-ci.yml)

Whiteboard is a Moodle activity that brings a board of an online whiteboard service, such as Miro,
TaskCards or Collaboard, into a course. Students find the board where they find everything else, and
teachers add it by pasting its link.

## Features

- **Paste a link or an embed code**: teachers copy what the whiteboard service gives them; the
  whiteboard finds the service and keeps the link.
- **Any whiteboard service**: administrators add, enable and disable services themselves, without
  changes to the plugin. A new site starts with Miro, TaskCards, Collaboard and Conceptboard.
- **In the page or in a new tab**: boards are shown in the activity page, or open in a new tab for
  services that do not allow other websites to show their boards.
- **Theatre mode**: nothing but the board, its name and buttons to open it in a new tab and to exit.
- **Boards for groups and single students**: on the *Boards* page of a whiteboard, teachers give
  groups or single students a board of their own, much like the overrides of an assignment. In
  group mode, teachers switch between the groups' boards with Moodle's group selector.
- **Import and export** of services as JSON, to share them between sites.
- **Activity completion** when students view the whiteboard, and the description on the course page.
- **Backup and restore**, duplicating and importing.
- **Activities overview** (Moodle 5).

The boards stay in the whiteboard service: the whiteboard needs no service of its own, and the only
personal data it stores are the links of user boards.

## Requirements

Moodle 4.5 to 5.3. Teachers need an account with a whiteboard service.

## Installation

1. Download the plugin or clone it into `mod/whiteboard` (`public/mod/whiteboard` from Moodle 5.1).
2. Go to *Site administration > Notifications* to complete the installation.

The services are managed in *Site administration > Plugins > Activity modules > Whiteboard*.

## Documentation

- [Teacher guide](docs/teacher-guide.md): adding a whiteboard, what students see
- [Student guide](docs/student-guide.md)
- [Administrator guide](docs/admin-guide.md): installation, upgrading, whiteboard services and which
  are known to work, privacy
- [Developer guide](docs/developer-guide.md): how the whiteboard works, tests and releases

With the Documentation Center (`tool_mdocs`) installed, the guides are also offered on the whiteboard
pages.

## Support

Issues and pull requests: https://github.com/bdecentgmbh/moodle-mod_whiteboard

Whether a whiteboard service lets other websites show its boards is up to the service and can change
at any time. Please tell us when a service from the administrator guide behaves differently.

## License

Copyright (C) 2022 onwards bdecent gmbh <https://bdecent.de>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU
General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not,
see <https://www.gnu.org/licenses/>.
