# Changes

## 1.1 (2026102900)

- Moodle 4.5 to 5.3; Moodle 4.5 is now the minimum.
- Administrators manage the whiteboard services that teachers can use, and add new ones themselves,
  under *Site administration > Plugins > Activity modules > Whiteboard*: name, beginnings of the
  board links, link parameters and whether boards are shown in the page or in a new tab. Services
  can be exported and imported as JSON (#2, #3, #4).
- New sites start with Miro, TaskCards, Collaboard and Conceptboard. Sites upgrading from 1.0 get
  Collaboard and TaskCards added, and keep Miro and Conceptboard disabled if they were.
- Teachers paste the link of a board, or its embed code, instead of choosing a type and entering a
  board ID. Existing whiteboards keep their boards.
- Miro boards are shown right away, without a preview that has to be clicked first.
- Conceptboard boards open in a new tab: Conceptboard does not allow other websites to show its
  boards (#1).
- Group mode: each group can have a board of its own, set under *Group boards* in the activity
  settings. Students see the board of their group; teachers switch between the groups with
  Moodle's group selector.
- Theatre mode shows nothing but the board, its name and buttons to open it in a new tab and to
  exit.
- Boards shown in the page have a link to open them in a new tab, a title for screen readers and the
  height of the window.
- Board links are checked and escaped before they are shown (security).
- "Display description on course page" shows the description on the course page.
- The whiteboard is a collaboration activity.
- Moodle 5: the list of whiteboards of a course leads to the course's activities overview page.
- Documentation for the Documentation Center: guides for administrators, teachers, students and
  developers.

## 1.0 (2022081800)

- First release: Miro and Conceptboard boards by board ID.
