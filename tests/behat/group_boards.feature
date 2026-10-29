@mod @mod_whiteboard
Feature: Each group works on a board of its own
  In order to let groups work on their own boards
  As a teacher
  I need to add a board for each group to the whiteboard

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
      | student2 | Student   | 2        |
      | student3 | Student   | 3        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
      | Group B | C1     | GB       |
    And the following "group members" exist:
      | user     | group |
      | student1 | GA    |
      | student2 | GB    |
    And the following "mod_whiteboard > services" exist:
      | name          | links                      |
      | Example board | https://example.com/board/ |

  Scenario: Students see the board of their group in separate groups
    Given I log in as "teacher1"
    And I add a "whiteboard" activity to course "Course 1" section "1" and I fill the form with:
      | Name       | Ideas                                               |
      | Board link | https://example.com/board/all                       |
      | Group mode | Separate groups                                     |
      | Group A    | <iframe src="https://example.com/board/a"></iframe> |
      | Group B    | https://example.com/board/b                         |
    When I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    Then the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/a"
    And the "title" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "Ideas (Group A)"
    And I am on the "Ideas" "whiteboard activity" page logged in as "student2"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/b"
    And I am on the "Ideas" "whiteboard activity" page logged in as "student3"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/all"
    And I am on the "Ideas" "whiteboard activity" page logged in as "teacher1"
    And I select "Group B" from the "Separate groups" singleselect
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/b"
    And I select "All participants" from the "Separate groups" singleselect
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/all"

  Scenario: Students can look at the boards of other groups in visible groups
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 2         |
    And I am on the "Ideas" "whiteboard activity editing" page logged in as "teacher1"
    And I set the following fields to these values:
      | Group A | https://example.com/board/a |
      | Group B | https://example.com/board/b |
    And I press "Save and display"
    When I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    Then the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/a"
    And I select "Group B" from the "Visible groups" singleselect
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/b"

  Scenario: Board links of groups must be from a supported service
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 1         |
    And I am on the "Ideas" "whiteboard activity editing" page logged in as "teacher1"
    When I set the field "Group A" to "https://example.org/board/a"
    And I press "Save and display"
    Then I should see "Paste the link of a board, or its embed code, from one of these services"
    And I set the field "Group A" to ""
    And I press "Save and display"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/all"
