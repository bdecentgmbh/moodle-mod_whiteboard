@mod @mod_whiteboard
Feature: Teachers add whiteboards with the link or embed code of a board
  In order to work on a board with my students in the course
  As a teacher
  I need to add a whiteboard by pasting what the whiteboard service gives me

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "mod_whiteboard > services" exist:
      | name          | links                      | params    |
      | Example board | https://example.com/board/ | mode=view |

  Scenario: A teacher pastes an embed code and students see the board
    Given I log in as "teacher1"
    When I add a "whiteboard" activity to course "Course 1" section "1" and I fill the form with:
      | Name        | Ideas                                                                         |
      | Board link  | <iframe width="720" src="https://example.com/board/abc" frameborder="0"></iframe> |
      | Description | Add your ideas                                                                |
    And I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    Then the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/abc?mode=view"
    And the "title" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "Ideas"
    And I should see "Add your ideas"
    And "Open in a new tab" "link" should exist

  Scenario: Links of other services are refused
    Given I log in as "teacher1"
    When I add a "whiteboard" activity to course "Course 1" section "1" and I fill the form with:
      | Name       | Ideas                        |
      | Board link | https://example.org/board/abc |
    Then I should see "Paste the link of a board, or its embed code, from one of these services: Collaboard, Conceptboard, Example board, Miro, TaskCards"
    And I set the field "Board link" to "https://example.com/board/abc\" onload=\"alert(1)"
    And I press "Save and display"
    And I should see "Paste the link of a board, or its embed code, from one of these services"

  Scenario: Boards of services that do not allow embedding open in a new tab
    Given the following "activities" exist:
      | activity   | course | name       | boardurl                                                   |
      | whiteboard | C1     | Retro      | https://app.conceptboard.com/board/b6b6-0ccf-8z9s-qmqi-8bt5 |
    When I am on the "Retro" "whiteboard activity" page logged in as "student1"
    Then I should see "This board opens in Conceptboard in a new tab."
    And the "href" attribute of "Open the board" "link" should contain "https://app.conceptboard.com/board/b6b6-0ccf-8z9s-qmqi-8bt5"
    And "iframe.mod_whiteboard-frame" "css_element" should not exist

  Scenario: The description can be shown on the course page
    Given the following "activities" exist:
      | activity   | course | name  | intro                  | showdescription |
      | whiteboard | C1     | Ideas | Brainstorm in groups   | 1               |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Brainstorm in groups"
