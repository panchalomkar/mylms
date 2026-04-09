<?php
namespace local_halloffame\tests;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_halloffame\manager;

/**
 * Unit tests for local_halloffame\manager.
 *
 * Run from Moodle root:
 *   vendor/bin/phpunit --configuration local/halloffame/phpunit.xml
 *
 * @package   local_halloffame
 * @category  test
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3.0
 */
class manager_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    // ── Awards ────────────────────────────────────────────────────────────────

    public function test_create_award_returns_id(): void {
        global $DB;
        $user  = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();
        $this->setUser($admin);

        $id = manager::create_award([
            'userid'     => $user->id,
            'title'      => 'Top Performer',
            'department' => 'Sales',
            'category'   => 'Top Performer of the Month',
            'month'      => 8,
            'year'       => 2025,
            'message'    => 'Great work!',
            'image'      => '',
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $r = $DB->get_record('halloffame_awards', ['id' => $id]);
        $this->assertEquals($user->id,  $r->userid);
        $this->assertEquals('Top Performer', $r->title);
        $this->assertEquals('Sales', $r->department);
        $this->assertEquals(8,  $r->month);
        $this->assertEquals(2025, $r->year);
        $this->assertEquals($admin->id, $r->createdby);
    }

    public function test_get_awards_returns_all(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        manager::create_award(['userid'=>$u->id,'title'=>'A1','department'=>'HR','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        manager::create_award(['userid'=>$u->id,'title'=>'A2','department'=>'HR','month'=>2,'year'=>2025,'message'=>'','image'=>'']);
        $this->assertCount(2, manager::get_awards());
    }

    public function test_get_awards_filter_month(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        manager::create_award(['userid'=>$u->id,'title'=>'Jan','department'=>'HR','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        manager::create_award(['userid'=>$u->id,'title'=>'Aug','department'=>'HR','month'=>8,'year'=>2025,'message'=>'','image'=>'']);
        $r = manager::get_awards(['month'=>8]);
        $this->assertCount(1, $r);
        $this->assertEquals('Aug', $r[0]->title);
    }

    public function test_get_awards_filter_quarter(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        manager::create_award(['userid'=>$u->id,'title'=>'M1', 'department'=>'HR','month'=>1, 'year'=>2025,'message'=>'','image'=>'']);
        manager::create_award(['userid'=>$u->id,'title'=>'M4', 'department'=>'HR','month'=>4, 'year'=>2025,'message'=>'','image'=>'']);
        manager::create_award(['userid'=>$u->id,'title'=>'M12','department'=>'HR','month'=>12,'year'=>2025,'message'=>'','image'=>'']);
        $this->assertCount(1, manager::get_awards(['quarter'=>1]));
        $this->assertCount(1, manager::get_awards(['quarter'=>4]));
    }

    public function test_get_awards_filter_department(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        manager::create_award(['userid'=>$u->id,'title'=>'HR',   'department'=>'HR',  'month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        manager::create_award(['userid'=>$u->id,'title'=>'Sales','department'=>'Sales','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        $r = manager::get_awards(['department'=>'HR']);
        $this->assertCount(1, $r);
        $this->assertEquals('HR', $r[0]->title);
    }

    public function test_delete_award(): void {
        global $DB;
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $id = manager::create_award(['userid'=>$u->id,'title'=>'To Delete','department'=>'HR','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        manager::delete_award($id);
        $this->assertFalse($DB->record_exists('halloffame_awards', ['id'=>$id]));
    }

    // ── Submissions / Achievements ────────────────────────────────────────────

    public function test_submit_achievement_creates_pending(): void {
        global $DB;
        $u = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $id = manager::submit_achievement(['title'=>'PMP','issuer'=>'PMI','issuedate'=>time(),'type'=>'Project Management','notes'=>'','fileurl'=>'']);
        $r  = $DB->get_record('halloffame_submissions', ['id'=>$id]);
        $this->assertEquals($u->id, $r->userid);
        $this->assertEquals('pending', $r->status);
        $this->assertEquals('PMP', $r->title);
    }

    public function test_approve_moves_to_achievements(): void {
        global $DB;
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $sid = manager::submit_achievement(['title'=>'AWS','issuer'=>'Amazon','issuedate'=>time(),'type'=>'Technical','notes'=>'','fileurl'=>'']);
        $this->setUser($a);
        manager::approve_achievement($sid);
        $sub = $DB->get_record('halloffame_submissions', ['id'=>$sid]);
        $this->assertEquals('approved', $sub->status);
        $ach = $DB->get_record('halloffame_achievements', ['userid'=>$u->id,'title'=>'AWS']);
        $this->assertNotFalse($ach);
        $this->assertEquals(1, $ach->status);
        $this->assertEquals($a->id, $ach->approvedby);
    }

    public function test_reject_updates_status(): void {
        global $DB;
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $sid = manager::submit_achievement(['title'=>'Fake','issuer'=>'Nobody','issuedate'=>time(),'type'=>'Other','notes'=>'','fileurl'=>'']);
        $this->setUser($a);
        manager::reject_achievement($sid);
        $this->assertEquals('rejected', $DB->get_field('halloffame_submissions', 'status', ['id'=>$sid]));
        $this->assertFalse($DB->record_exists('halloffame_achievements', ['userid'=>$u->id,'title'=>'Fake']));
    }

    public function test_get_achievements_returns_approved_only(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $s1 = manager::submit_achievement(['title'=>'Approved','issuer'=>'A','issuedate'=>time(),'type'=>'Technical','notes'=>'','fileurl'=>'']);
        manager::submit_achievement(['title'=>'Pending','issuer'=>'B','issuedate'=>time(),'type'=>'Technical','notes'=>'','fileurl'=>'']);
        $this->setUser($a);
        manager::approve_achievement($s1);
        $achs = manager::get_achievements();
        $this->assertCount(1, $achs);
        $this->assertEquals('Approved', $achs[0]->title);
    }

    // ── Likes ─────────────────────────────────────────────────────────────────

    public function test_toggle_like_adds_and_removes(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $aid = manager::create_award(['userid'=>$u->id,'title'=>'L','department'=>'HR','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        $this->setUser($u);
        $r1 = manager::toggle_like($aid, 'award');
        $this->assertTrue($r1['liked']);
        $this->assertEquals(1, $r1['count']);
        $r2 = manager::toggle_like($aid, 'award');
        $this->assertFalse($r2['liked']);
        $this->assertEquals(0, $r2['count']);
    }

    public function test_multiple_users_can_like(): void {
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $a  = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $aid = manager::create_award(['userid'=>$u1->id,'title'=>'Pop','department'=>'HR','month'=>6,'year'=>2025,'message'=>'','image'=>'']);
        $this->setUser($u1);
        manager::toggle_like($aid, 'award');
        $this->setUser($u2);
        manager::toggle_like($aid, 'award');
        $this->assertEquals(2, manager::get_like_count($aid, 'award'));
    }

    public function test_user_has_liked(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $aid = manager::create_award(['userid'=>$u->id,'title'=>'H','department'=>'HR','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        $this->setUser($u);
        $this->assertFalse(manager::user_has_liked($aid, 'award'));
        manager::toggle_like($aid, 'award');
        $this->assertTrue(manager::user_has_liked($aid, 'award'));
    }

    // ── Categories & Departments ──────────────────────────────────────────────

    public function test_category_crud(): void {
        global $DB;
        $DB->delete_records('halloffame_categories');
        $id = manager::save_category('Innovation');
        $this->assertGreaterThan(0, $id);
        $cats = manager::get_categories();
        $this->assertCount(1, $cats);
        manager::save_category('Innovation Prize', $id);
        $this->assertEquals('Innovation Prize', manager::get_categories()[0]->name);
    }

    public function test_department_crud(): void {
        global $DB;
        $DB->delete_records('halloffame_departments');
        $id = manager::save_department('Engineering');
        $this->assertGreaterThan(0, $id);
        $this->assertCount(1, manager::get_departments());
        manager::save_department('Eng & Product', $id);
        $this->assertEquals('Eng & Product', manager::get_departments()[0]->name);
    }

    public function test_months_list_has_12_entries(): void {
        $m = manager::months_list();
        $this->assertCount(12, $m);
        $this->assertArrayHasKey(1,  $m);
        $this->assertArrayHasKey(12, $m);
    }
}
