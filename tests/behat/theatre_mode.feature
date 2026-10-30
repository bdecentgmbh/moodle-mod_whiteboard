@mod @mod_whiteboard
Feature: Boards can be shown in theatre mode
  In order to work on a board with as much room as possible
  As a student
  I need to show nothing but the board

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "mod_whiteboard > services" exist:
      | name          | links                      |
      | Example board | https://example.com/board/ |

  Scenario: A student opens and closes theatre mode
    Given the following "activities" exist:
      | activity   | course | name  | intro          | boardurl                      |
      | whiteboard | C1     | Ideas | Add your ideas | https://example.com/board/abc |
    And I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    When I follow "Theatre mode"
    Then "body.pagelayout-popup" "css_element" should exist
    And I should see "Ideas" in the ".mod_whiteboard-theatre-bar" "css_element"
    And I should not see "Add your ideas"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/abc"
    And the "href" attribute of "Open in a new tab" "link" should contain "https://example.com/board/abc"
    And I follow "Exit theatre mode"
    And "body.pagelayout-popup" "css_element" should not exist
    And I should see "Add your ideas"

  Scenario: Boards that open in a new tab have no theatre mode
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                               |
      | whiteboard | C1     | Retro | https://app.conceptboard.com/board/abc |
    When I am on the "Retro" "whiteboard activity" page logged in as "student1"
    Then "Theatre mode" "link" should not exist
    And I should see "This board opens in Conceptboard in a new tab."
