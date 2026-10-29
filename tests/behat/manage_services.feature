@mod @mod_whiteboard
Feature: Admins manage the whiteboard services that teachers can use
  In order to offer the whiteboard services of my organisation
  As an admin
  I need to add, disable, delete and import whiteboard services

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

  Scenario: An admin adds a service and teachers use its boards
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Whiteboard" in site administration
    And I should see "Miro" in the "mod_whiteboard-services" "table"
    When I press "Add a service"
    And I set the following fields to these values:
      | Name            | Example board              |
      | Links           | http://example.com/board/  |
      | Link parameters | mode=view                  |
    And I press "Save changes"
    Then I should see "Each link must start with https://, the address of the service and a \"/\": http://example.com/board/"
    And I set the field "Links" to "https://example.com/board/"
    And I press "Save changes"
    And I should see "Example board" in the "mod_whiteboard-services" "table"
    And I should see "Adds mode=view" in the "Example board" "table_row"
    And I log out
    And I log in as "teacher1"
    And I add a "whiteboard" activity to course "Course 1" section "1" and I fill the form with:
      | Name       | Ideas                         |
      | Board link | https://example.com/board/abc |
    And I am on the "Ideas" "whiteboard activity" page
    And the "src" attribute of "iframe.mod_whiteboard-frame" "css_element" should contain "https://example.com/board/abc?mode=view"

  Scenario: Boards of disabled services are not shown
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                                 |
      | whiteboard | C1     | Ideas | https://miro.com/app/board/uXjVPfdzxzc=/ |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > Whiteboard" in site administration
    And I should see "1" in the "Miro" "table_row"
    When I click on "Disable" "link" in the "Miro" "table_row"
    And I am on the "Ideas" "whiteboard activity" page logged in as "student1"
    Then I should see "This board cannot be shown at the moment. Please contact your teacher."
    And "iframe.mod_whiteboard-frame" "css_element" should not exist
    And I am on the "Ideas" "whiteboard activity" page logged in as "teacher1"
    And I should see "Change the board link in the settings"
    And I am on the "Ideas" "whiteboard activity editing" page
    And I should see "Supported services: Collaboard, Conceptboard, TaskCards"

  Scenario: An admin deletes a service
    Given the following "activities" exist:
      | activity   | course | name  | boardurl                           |
      | whiteboard | C1     | Ideas | https://www.taskcards.de/#/board/a |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > Whiteboard" in site administration
    When I click on "Delete" "link" in the "TaskCards" "table_row"
    Then I should see "Delete the whiteboard service \"TaskCards\"? Activities with its boards: 1."
    And I press "Continue"
    And I should see "The service has been deleted."
    And I should not see "TaskCards" in the "mod_whiteboard-services" "table"

  Scenario: An admin imports services
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Whiteboard" in site administration
    When I press "Import"
    And I set the field "Services (JSON)" to "{\"name\": \"Example board\", \"links\": [\"https://example.com/board/\"], \"display\": \"newtab\"}"
    And I press "Import"
    Then I should see "Services added: 1. Services updated: 0."
    And I should see "In a new tab" in the "Example board" "table_row"
    And I press "Import"
    And I set the field "Services (JSON)" to "[{\"name\": \"Broken\", \"links\": \"example.com\"}]"
    And I press "Import"
    And I should see "These services cannot be imported. Broken: Each link must start with https://"
