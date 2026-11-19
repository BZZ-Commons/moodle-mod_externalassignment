@mod @mod_externalassignment @javascript
Feature: Students only see their own grade
  In order to protect the privacy of the students
  As a student
  I can see my own grade but not the grades of the other students, and I get no link to the grading pages

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity           | course | name         | externalname | externallink                       | externalgrademax | manualgrademax |
      | externalassignment | C1     | Privacy test | m999-privacy | https://www.example.com/assignment | 100              | 50             |
    And I am on the "Privacy test" "externalassignment activity" page logged in as teacher1
    And I click on "Show all" "link"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grading (max. 50)" to "37"
    And I press "Save changes"
    And I log out

  Scenario: A student sees the own grade
    When I am on the "Privacy test" "externalassignment activity" page logged in as student1
    Then I should see "37.00"

  Scenario: A student without a grade doesn't see the grade of another student
    When I am on the "Privacy test" "externalassignment activity" page logged in as student2
    Then I should not see "37.00"
    And I should not see "Student 1"

  Scenario: A student has no Submissions tab
    When I am on the "Privacy test" "externalassignment activity" page logged in as student2
    Then "Submissions" "link" should not exist
