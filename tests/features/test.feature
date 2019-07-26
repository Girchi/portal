@api
Feature: Login
 In order to login to the website
 As a non-authenticated user
 I want to be able to login

 Background:
   Given users:
     | name           | pass |
     | admin123       | 1234 |

 Scenario: non-authenticated users can see the login page.
   Given I am not logged in
   When I visit "/en/user/login"
   And I should see the text "Email"
   And I should see the text "Password"
   Then I should see the link "Register"
   And I should see the button "Log in"

#  Scenario: Registered users can login.
#    Given I am not logged in
#    When I visit "/user"
#    And I enter "test@test.com" for "Email"
#    And I enter "pass" for "Password"
#    And I press the "Log in" button
#    Then I should not see "Unrecognized username or password. Forgot your password?"
