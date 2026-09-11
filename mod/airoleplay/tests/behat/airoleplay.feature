# Behat acceptance tests for mod_airoleplay
# @package mod_airoleplay

@mod @mod_airoleplay
Feature: AI Roleplay scenario management and learner workflow
  In order to practice professional communication skills
  As a learner or L&D administrator
  I need to create, assign, and complete AI roleplay scenarios

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                  |
      | teacher1  | Teacher   | One      | teacher1@example.com   |
      | student1  | Student   | One      | student1@example.com   |
      | manager1  | Manager   | One      | manager1@example.com   |
    And the following "courses" exist:
      | fullname       | shortname |
      | Test Course    | TC        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC     | editingteacher |
      | student1 | TC     | student        |
      | manager1 | TC     | teacher        |
    And the following "activities" exist:
      | activity   | course | name                    | learning_objective              | difficulty | industry | pass_score |
      | airoleplay | TC     | Customer Service Roleplay | Handle customer complaints professionally | medium | support | 70 |

  # ----------------------------------------------------------------
  # Scenario: View roleplay activity
  # ----------------------------------------------------------------
  @javascript
  Scenario: Learner can view AI roleplay scenario
    Given I log in as "student1"
    And I am on "Test Course" course homepage
    When I click on "Customer Service Roleplay" "link"
    Then I should see "Customer Service Roleplay"
    And I should see "Start Roleplay"
    And I should see "medium"
    And I should see "70%"

  # ----------------------------------------------------------------
  # Scenario: Teacher creates scenario
  # ----------------------------------------------------------------
  @javascript
  Scenario: Teacher creates a new AI roleplay scenario
    Given I log in as "teacher1"
    And I am on "Test Course" course homepage
    And I turn editing mode on
    When I add a "AI Roleplay" activity to course "Test Course" section "1"
    And I set the following fields to these values:
      | Scenario title      | Sales Objection Handling               |
      | Industry / Domain   | Sales                                  |
      | Difficulty level    | Hard                                   |
      | Simulation type     | Chat only (text)                       |
      | Learning objective  | Handle pricing objections confidently  |
      | Pass score (%)      | 75                                     |
    And I press "Save and display"
    Then I should see "Sales Objection Handling"
    And I should see "hard"
    And I should see "75%"

  # ----------------------------------------------------------------
  # Scenario: Learner cannot start if max attempts reached
  # ----------------------------------------------------------------
  @javascript
  Scenario: Start button is disabled when max attempts reached
    Given the following "activities" exist:
      | activity   | course | name           | max_attempts | pass_score |
      | airoleplay | TC     | Limited Attempts | 1          | 70         |
    And I log in as "student1"
    # ... (simulate 1 completed attempt via generator)
    And I am on "Test Course" course homepage
    When I click on "Limited Attempts" "link"
    Then I should see "No attempts remaining"
    And the "Start Roleplay" "button" should be disabled

  # ----------------------------------------------------------------
  # Scenario: Manager can view team reports
  # ----------------------------------------------------------------
  @javascript
  Scenario: Manager views team performance report
    Given I log in as "manager1"
    And I am on "Test Course" course homepage
    When I click on "Customer Service Roleplay" "link"
    Then I should see "View Reports"
    When I click on "View Reports" "link"
    Then I should see "Analytics Report"
    And I should see "Total Sessions"
    And I should see "Unique Learners"
    And I should see "Avg Score"

  # ----------------------------------------------------------------
  # Scenario: Report CSV export
  # ----------------------------------------------------------------
  @javascript
  Scenario: L&D can export session report to CSV
    Given I log in as "teacher1"
    And I am on the "Customer Service Roleplay" "airoleplay" activity page
    And I follow "View Reports"
    When I follow "Export CSV"
    Then I should get a file with the ".csv" extension

  # ----------------------------------------------------------------
  # Scenario: Admin configures AI provider
  # ----------------------------------------------------------------
  Scenario: Admin configures Ollama as primary AI provider
    Given I log in as "admin"
    When I navigate to "Plugins > AI Roleplay Settings" in site administration
    Then I should see "AI Configuration"
    And I should see "Primary AI provider"
    When I set the field "Primary AI provider" to "Ollama (Self-hosted"
    And I set the field "Ollama server URL" to "http://localhost:11434"
    And I press "Save changes"
    Then I should see "Changes saved"

  # ----------------------------------------------------------------
  # Scenario: Admin maps profile fields
  # ----------------------------------------------------------------
  Scenario: Admin maps custom profile fields to platform purposes
    Given the following "custom profile fields" exist:
      | datatype | shortname  | name        |
      | text     | department | Department  |
      | text     | manager    | Manager     |
    And I log in as "admin"
    When I navigate to "Plugins > AI Roleplay Settings" in site administration
    And I set the field "Department" to "department"
    And I set the field "Manager / Reporting Manager" to "manager"
    And I press "Save changes"
    Then I should see "Changes saved"

  # ----------------------------------------------------------------
  # Scenario: Notifications are created for assignments
  # ----------------------------------------------------------------
  @javascript
  Scenario: Assignment notification appears in Moodle
    Given I log in as "teacher1"
    And I am on the "Customer Service Roleplay" "airoleplay" activity page
    # Create assignment via UI
    When I follow "Edit Scenario"
    # ... assignment creation steps
    Then a Moodle notification should exist for "student1"
