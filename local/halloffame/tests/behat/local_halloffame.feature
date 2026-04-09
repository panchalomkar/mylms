@local @local_halloffame
Feature: Hall of Fame recognition system

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | student1 | Bob       | Student  | bob@example.com   |
      | teacher1 | Alice     | Teacher  | alice@example.com |
      | manager1 | Carol     | Manager  | carol@example.com |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |

  @javascript
  Scenario: Logged-in user can view the Hall of Fame
    Given I log in as "student1"
    When I navigate to "/local/halloffame/pages/index.php" in current site
    Then I should see "Hall of Fame"
    And I should see "Awards"
    And I should see "Achievements Gallery"

  @javascript
  Scenario: Guest cannot access Hall of Fame
    Given I am on site homepage
    When I navigate to "/local/halloffame/pages/index.php" in current site
    Then I should see "You are not logged in"

  @javascript
  Scenario: Switching to Achievements tab
    Given I log in as "student1"
    And I navigate to "/local/halloffame/pages/index.php" in current site
    When I click on "Achievements Gallery" "link"
    Then I should see "Achievements Gallery"

  @javascript
  Scenario: Admin creates an award
    Given I log in as "manager1"
    And I navigate to "/local/halloffame/pages/admin.php" in current site
    Then I should see "Admin Panel"
    When I set the field "title"   to "Top Performer of the Month"
    And  I set the field "message" to "Outstanding this month!"
    And  I select "Bob Student" from the "userid" singleselect
    And  I press "Post"
    Then I should see "Award created successfully"

  @javascript
  Scenario: User submits a certificate
    Given I log in as "student1"
    And I navigate to "/local/halloffame/pages/submit.php" in current site
    Then I should see "Upload External Certificate"
    When I set the field "title"  to "PRINCE2 Foundation"
    And  I set the field "issuer" to "AXELOS"
    And  I select "Project Management" from the "type" singleselect
    And  I press "Submit For Review"
    Then I should see "Certificate submitted for review"

  @javascript
  Scenario: Admin approves a submission
    Given the following "local_halloffame > submission" exists:
      | user     | title              | issuer | type      |
      | student1 | AWS Practitioner   | Amazon | Technical |
    And I log in as "manager1"
    And I navigate to "/local/halloffame/pages/review.php" in current site
    Then I should see "AWS Practitioner"
    When I click on "Approve" "link"
    Then I should see "Achievement approved and published"

  @javascript
  Scenario: Admin rejects a submission
    Given the following "local_halloffame > submission" exists:
      | user     | title     | issuer | type  |
      | student1 | Fake Cert | Nobody | Other |
    And I log in as "manager1"
    And I navigate to "/local/halloffame/pages/review.php" in current site
    When I click on "Reject" "link"
    And  I confirm the dialogue
    Then I should see "Submission rejected"

  @javascript
  Scenario: User can like and unlike an award
    Given the following "local_halloffame > award" exists:
      | user     | title       | department | month | year |
      | student1 | Rising Star | Sales      | 8     | 2025 |
    And I log in as "teacher1"
    And I navigate to "/local/halloffame/pages/index.php?tab=awards" in current site
    When I click on the like button for award "Rising Star"
    Then the like count for "Rising Star" should be "1"
    When I click on the like button for award "Rising Star"
    Then the like count for "Rising Star" should be "0"

  @javascript
  Scenario: Month filter narrows award results
    Given the following "local_halloffame > award" exists:
      | user     | title         | department | month | year |
      | student1 | January Award | HR         | 1     | 2025 |
    And the following "local_halloffame > award" exists:
      | user     | title        | department | month | year |
      | student1 | August Award | HR         | 8     | 2025 |
    And I log in as "teacher1"
    And I navigate to "/local/halloffame/pages/index.php?tab=awards" in current site
    When I select "August" from the "filterMonth" singleselect
    Then I should see "August Award"
    And  I should not see "January Award"

  @javascript
  Scenario: User views their own submission history
    Given I log in as "student1"
    When I navigate to "/local/halloffame/pages/my_submissions.php" in current site
    Then I should see "My Submissions"

  @javascript
  Scenario: Admin manages categories
    Given I log in as "manager1"
    And I navigate to "/local/halloffame/pages/manage_categories.php" in current site
    When I set the field "catname" to "Customer Champion"
    And  I press "Add"
    Then I should see "Category saved"
    And  I should see "Customer Champion"

  @javascript
  Scenario: Admin manages departments
    Given I log in as "manager1"
    And I navigate to "/local/halloffame/pages/manage_departments.php" in current site
    When I set the field "deptname" to "Product"
    And  I press "Add"
    Then I should see "Department saved"
    And  I should see "Product"
