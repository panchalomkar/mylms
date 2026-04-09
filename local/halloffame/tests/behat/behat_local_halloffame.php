<?php
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Custom Behat step definitions for local_halloffame.
 *
 * @package   local_halloffame
 * @category  test
 */
class behat_local_halloffame extends behat_base {

    /**
     * @Given /^the following "local_halloffame > award" exists:$/
     */
    public function award_exists(TableNode $table): void {
        global $DB;
        foreach ($table->getHash() as $row) {
            $u = $DB->get_record('user', ['username' => $row['user']], '*', MUST_EXIST);
            $DB->insert_record('halloffame_awards', (object)[
                'userid'      => $u->id,
                'title'       => $row['title'],
                'department'  => $row['department'] ?? '',
                'category'    => $row['category']   ?? '',
                'month'       => (int)($row['month'] ?? date('n')),
                'year'        => (int)($row['year']  ?? date('Y')),
                'message'     => $row['message']     ?? '',
                'image'       => '',
                'createdby'   => 2,
                'timecreated' => time(),
            ]);
        }
    }

    /**
     * @Given /^the following "local_halloffame > submission" exists:$/
     */
    public function submission_exists(TableNode $table): void {
        global $DB;
        foreach ($table->getHash() as $row) {
            $u = $DB->get_record('user', ['username' => $row['user']], '*', MUST_EXIST);
            $DB->insert_record('halloffame_submissions', (object)[
                'userid'      => $u->id,
                'title'       => $row['title'],
                'issuer'      => $row['issuer']  ?? '',
                'issuedate'   => time(),
                'type'        => $row['type']    ?? '',
                'notes'       => $row['notes']   ?? '',
                'fileurl'     => '',
                'status'      => 'pending',
                'timecreated' => time(),
            ]);
        }
    }

    /**
     * @When /^I click on the like button for award "([^"]*)"$/
     */
    public function i_click_like_for_award(string $title): void {
        $cards = $this->findAll('css', '.hof-award-card');
        foreach ($cards as $card) {
            $t = $card->find('css', '.hof-award-title');
            if ($t && stripos($t->getText(), $title) !== false) {
                $btn = $card->find('css', '.hof-like-btn');
                if (!$btn) {
                    throw new ExpectationException("No like button on card: {$title}", $this->getSession());
                }
                $btn->click();
                $this->getSession()->wait(1500);
                return;
            }
        }
        throw new ExpectationException("Award card '{$title}' not found", $this->getSession());
    }

    /**
     * @Then /^the like count for "([^"]*)" should be "([^"]*)"$/
     */
    public function like_count_should_be(string $title, string $expected): void {
        $cards = $this->findAll('css', '.hof-award-card');
        foreach ($cards as $card) {
            $t = $card->find('css', '.hof-award-title');
            if ($t && stripos($t->getText(), $title) !== false) {
                $c = $card->find('css', '.hof-like-count');
                if (!$c) {
                    throw new ExpectationException("No count element on '{$title}'", $this->getSession());
                }
                $actual = trim($c->getText());
                if ($actual !== $expected) {
                    throw new ExpectationException(
                        "Like count for '{$title}': expected {$expected}, got {$actual}",
                        $this->getSession()
                    );
                }
                return;
            }
        }
        throw new ExpectationException("Award card '{$title}' not found", $this->getSession());
    }
}
