@mod @mod_whiteboard
Feature: Groups and users work on boards of their own
  In order to let groups or single students work on their own boards
  As a teacher
  I need to add boards for groups and users on the Boards page of the whiteboard

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

  @javascript
  Scenario: Teachers add boards for groups on the Boards page
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 1         |
    And I am on the "Ideas" "whiteboard activity editing" page logged in as "teacher1"
    And I navigate to "Boards" in current page administration
    And I should see "No group has a board of its own yet."
    When I press "Add a group board"
    And I set the following fields to these values:
      | Group      | Group A                                             |
      | Board link | <iframe src="https://example.com/board/a"></iframe> |
    And I press "Save and add another"
    And I set the following fields to these values:
      | Group      | Group B                     |
      | Board link | https://example.com/board/b |
    And I press "Save"
    Then the following should exist in the "mod_whiteboard-boards" table:
      | Group   | Board link                  |
      | Group A | https://example.com/board/a |
      | Group B | https://example.com/board/b |
    And the "Add a group board" "button" should be disabled

  Scenario: Students see the board of their group in separate groups
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 1         |
    And the following "mod_whiteboard > boards" exist:
      | whiteboard | group | boardurl                    |
      | Ideas      | GA    | https://example.com/board/a |
      | Ideas      | GB    | https://example.com/board/b |
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
    And the following "mod_whiteboard > boards" exist:
      | whiteboard | group | boardurl                    |
      | Ideas      | GA    | https://example.com/board/a |
      | Ideas      | GB    | https://example.com/board/b |
    When I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    Then the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/a"
    And I select "Group B" from the "Visible groups" singleselect
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/b"

  @javascript
  Scenario: Teachers add boards for users on the Boards page
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 1         |
    And I am on the "Ideas" "whiteboard activity editing" page logged in as "teacher1"
    And I navigate to "Boards" in current page administration
    When I select "User boards" from the "Boards" singleselect
    And I press "Add a user board"
    And I set the following fields to these values:
      | User       | Student 1                    |
      | Board link | https://example.com/board/s1 |
    And I press "Save and add another"
    And I set the following fields to these values:
      | User       | Student 3                    |
      | Board link | https://example.com/board/s3 |
    And I press "Save"
    Then the following should exist in the "mod_whiteboard-boards" table:
      | User      | Board link                   |
      | Student 1 | https://example.com/board/s1 |
      | Student 3 | https://example.com/board/s3 |

  Scenario: A user board replaces the board of the group and of the whiteboard
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 1         |
    And the following "mod_whiteboard > boards" exist:
      | whiteboard | group | boardurl                    |
      | Ideas      | GA    | https://example.com/board/a |
    And the following "mod_whiteboard > boards" exist:
      | whiteboard | user     | boardurl                     |
      | Ideas      | student1 | https://example.com/board/s1 |
      | Ideas      | student3 | https://example.com/board/s3 |
    When I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    Then the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/s1"
    And "Separate groups" "select" should not exist
    And I am on the "Ideas" "whiteboard activity" page logged in as "student3"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/s3"
    And I am on the "Ideas" "whiteboard activity" page logged in as "student2"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/all"

  Scenario: Teachers change and delete boards
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      | groupmode |
      | whiteboard | C1     | Ideas | https://example.com/board/all | 1         |
    And the following "mod_whiteboard > boards" exist:
      | whiteboard | group | boardurl                    |
      | Ideas      | GA    | https://example.com/board/a |
    And the following "mod_whiteboard > boards" exist:
      | whiteboard | user     | boardurl                     |
      | Ideas      | student2 | https://example.com/board/s2 |
    And I am on the "Ideas" "whiteboard activity" page logged in as "teacher1"
    And I navigate to "Boards" in current page administration
    When I click on "Edit" "link" in the "Group A" "table_row"
    And I set the field "Board link" to "https://example.org/board/a"
    And I press "Save"
    Then I should see "Paste the link of a board, or its embed code, from one of these services"
    And I set the field "Board link" to "https://example.com/board/a2"
    And I press "Save"
    And I should see "https://example.com/board/a2" in the "Group A" "table_row"
    And I select "User boards" from the "Boards" singleselect
    And I click on "Delete" "link" in the "Student 2" "table_row"
    And I should see "Delete the board of Student 2 from this whiteboard?"
    And I press "Continue"
    And I should see "No user has a board of their own yet."
    And I am on the "Ideas" "whiteboard activity" page logged in as "student2"
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/all"

  Scenario: Group boards need group mode
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                      |
      | whiteboard | C1     | Ideas | https://example.com/board/all |
    When I am on the "Ideas" "whiteboard activity" page logged in as "teacher1"
    And I navigate to "Boards" in current page administration
    Then I should see "No user has a board of their own yet."
    And I select "Group boards" from the "Boards" singleselect
    And I should see "Group boards are only shown in group mode."
