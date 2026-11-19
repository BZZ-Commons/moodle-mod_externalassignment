@mod @mod_externalassignment @javascript
Feature: The capabilities of the teacher roles
  In order to let assistants view the grades without changing them
  As a manager of the course
  I need non-editing teachers to be able to view all grades only, while editing teachers may also grade
  and grant extensions. (Opening the grader or override page directly is blocked by a capability
  check, covered in PHPUnit: Behat cannot assert on Moodle exception pages.)

  This is the Behat-level test for GitHub issue #52 ("Use the defined grading capabilities").

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name       | externalname | externallink                       | externalgrademax | manualgrademax |
      | externalassignment | C1     | Roles test | m999-roles   | https://www.example.com/assignment | 100              | 50             |
    And I am on the "Roles test" "externalassignment activity" page logged in as teacher1
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 50)" to "37"
    And I press "Save changes"
    And I log out

  Scenario: A non-editing teacher sees the grades of all students but can't change them
    Given I am on the "Roles test" "externalassignment activity" page logged in as teacher2
    When I click on "Submissions" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Student 1"
    And I should see "Student 2"
    And I should see "37.00" in the "Student 1" "table_row"
    And "Grade" "link" should not exist in the "Student 1" "table_row"
    And "Go" "button" should not exist
    And "selectAll" "checkbox" should not exist

  Scenario: An editing teacher sees the grades of all students and may grade and grant extensions
    Given I am on the "Roles test" "externalassignment activity" page logged in as teacher1
    When I click on "Submissions" "link" in the ".secondary-navigation" "css_element"
    Then I should see "37.00" in the "Student 1" "table_row"
    And "Grade" "link" should exist in the "Student 1" "table_row"
    And "Grade" "link" should exist in the "Student 2" "table_row"
    And "Go" "button" should exist
    And I click on "Grade" "link" in the "Student 2" "table_row"
    And I should see "Student 2"
