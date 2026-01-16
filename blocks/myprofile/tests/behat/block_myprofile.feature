@block @block_myprofile
Feature: The logged in user block allows users to view their profile information
  In order to enable the logged in user block
  As a user
  I can add the logged in user block and configure it to show my information

  Scenario: Configure the logged in user block to show / hide the users country
    Given the following "users" exist:
      | username | firstname | lastname | email                | country   |
      | teacher1 | Teacher   | One      | teacher1@example.com | AU        |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display country       | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "Australia" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display country | Yes |
    And I press "Save changes"
    And I should see "Australia" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users city
    Given the following "users" exist:
      | username | firstname | lastname | email                | city  |
      | teacher1 | Teacher   | One      | teacher1@example.com | Perth |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display city          | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "Perth" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display city | Yes |
    And I press "Save changes"
    And I should see "Perth" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users email
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display email         | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "teacher1@example.com" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display email | Yes |
    And I press "Save changes"
    And I should see "teacher1@example.com" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users phone
    Given the following "users" exist:
      | username | firstname | lastname | email                | phone1   |
      | teacher1 | Teacher   | One      | teacher1@example.com | 555-5555 |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display phone         | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "555-5555" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display phone | Yes |
    And I press "Save changes"
    And I should see "555-5555" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users mobile phone
    Given the following "users" exist:
      | username | firstname | lastname | email                | phone2   |
      | teacher1 | Teacher   | One      | teacher1@example.com | 555-5555 |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display mobile phone | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "555-5555" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display mobile phone | Yes |
    And I press "Save changes"
    And I should see "555-5555" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users Institution
    Given the following "users" exist:
      | username | firstname | lastname | email                | institution   |
      | teacher1 | Teacher   | One      | teacher1@example.com | myinstitution |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display institution | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "myinstitution" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display institution | Yes |
    And I press "Save changes"
    And I should see "myinstitution" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users address
    Given the following "users" exist:
      | username | firstname | lastname | email                | address   |
      | teacher1 | Teacher   | One      | teacher1@example.com | myaddress |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display address | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "myaddress" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display address | Yes |
    And I press "Save changes"
    And I should see "myaddress" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users first access
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display first access | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "First access:" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display first access | Yes |
    And I press "Save changes"
    And I should see "First access:" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users last access
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display last access | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "Last access:" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display last access | Yes |
    And I press "Save changes"
    And I should see "Last access:" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users current login
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display current login | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "Log in:" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display current login | Yes |
    And I press "Save changes"
    And I should see "Log in:" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users last ip
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display last IP | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "IP:" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display last IP | Yes |
    And I press "Save changes"
    And I should see "IP:" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users idnumber
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | One      | teacher1@example.com | ID12345  |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display ID number | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "ID number:" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display ID number | Yes |
    And I press "Save changes"
    And I should see "ID number:" in the "Logged in user" "block"

  Scenario: Configure the logged in user block to show / hide the users last login
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And I log in as "teacher1"
    And I turn editing mode on
    When I add the "Logged in user" block
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display last login | No |
    And I press "Save changes"
    Then I should see "Teacher One" in the "Logged in user" "block"
    And I should not see "Last login:" in the "Logged in user" "block"
    And I configure the "Logged in user" block
    And I set the following fields to these values:
      | Display last login | Yes |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I should see "Last login:" in the "Logged in user" "block"




<div class="myprofile-container">
  <div class="rightb-card">
    <div class="myprofile-header d-flex justify-content-between align-items-center">
      <h5 class="myprofile-title">
        <i class="bi bi-person-circle mr-2"></i> My Profile
      </h5>
      <a href="{{editprofileurl}}" class="edit-profile-link"> <i class="fa fa-edit"></i></a>
    </div>

    <div class="myprofile-body">
      <div class="profile-image me-3">
        {{{ userpicture }}}
      </div>
      <div class="profile-info">
        <h4 class="user-name ellipsis ellipsis-1">{{ userfullname }}</h4>
        <p class="user-email ellipsis ellipsis-1">{{ designation }}</p>
        {{#userlocation}}
        <p class="user-location ellipsis ellipsis-1">
          <i class="bi bi-geo-alt-fill text-primary"></i> {{ userlocation }}
        </p>
        {{/userlocation}}
      </div>
    </div>

<div class="myprofile-stats d-flex align-items-center justify-content-center">
  <div class="stat text-center mx-3">
   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide orange-icon-c lucide-user w-4 h-4"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
    <h5 class="mt-1 mb-0">{{attendance}}</h5>
    <p class="mb-0">Attendance</p>
  </div>

  <div class="divider mx-2" style="width:1px; height:40px; background:#ddd;"></div>

  <div class="stat text-center mx-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-target orange-icon-c w-4 h-4"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
    <h5 class="mt-1 mb-0">{{mypoints}}</h5>
    <p class="mb-0">Points</p>
  </div>

  <div class="divider mx-2" style="width:1px; height:40px; background:#ddd;"></div>

  <div class="stat text-center mx-3">
   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trophy orange-icon-c w-4 h-4"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path></svg>
    <h5 class="mt-1 mb-0">{{myrank}}</h5>
    <p class="mb-0">Rank</p>
  </div>
</div>

  </div>
</div>

