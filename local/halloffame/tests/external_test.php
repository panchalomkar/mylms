<?php
namespace local_halloffame\tests;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_halloffame\external\get_awards;
use local_halloffame\external\get_achievements;
use local_halloffame\external\like_item;
use local_halloffame\external\submit_certificate;
use local_halloffame\manager;

/**
 * Tests for external (AJAX web-service) functions.
 *
 * @package   local_halloffame
 * @category  test
 */
class external_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function grantCap(string $cap, int $uid): void {
        $ctx    = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability($cap, CAP_ALLOW, $roleid, $ctx->id);
        role_assign($roleid, $uid, $ctx->id);
        accesslib_clear_all_caches_for_unit_testing();
    }

    // ── get_awards ────────────────────────────────────────────────────────────

    public function test_get_awards_returns_list(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $this->grantCap('local/halloffame:view', $a->id);
        manager::create_award(['userid'=>$u->id,'title'=>'WS Award','department'=>'Sales','month'=>3,'year'=>2025,'message'=>'','image'=>'']);
        $r = get_awards::execute([]);
        $this->assertIsArray($r);
        $this->assertCount(1, $r);
        $this->assertEquals('WS Award', $r[0]['title']);
        $this->assertArrayHasKey('likecount', $r[0]);
    }

    public function test_get_awards_filter_by_month(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $this->grantCap('local/halloffame:view', $a->id);
        manager::create_award(['userid'=>$u->id,'title'=>'March', 'department'=>'HR','month'=>3,'year'=>2025,'message'=>'','image'=>'']);
        manager::create_award(['userid'=>$u->id,'title'=>'August','department'=>'HR','month'=>8,'year'=>2025,'message'=>'','image'=>'']);
        $r = get_awards::execute(['month'=>3]);
        $this->assertCount(1, $r);
        $this->assertEquals('March', $r[0]['title']);
    }

    // ── get_achievements ──────────────────────────────────────────────────────

    public function test_get_achievements_approved_only(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $this->grantCap('local/halloffame:submit', $u->id);
        $sid = manager::submit_achievement(['title'=>'CSM','issuer'=>'ScrumAlliance','issuedate'=>time(),'type'=>'Leadership','notes'=>'','fileurl'=>'']);
        $this->setUser($a);
        $this->grantCap('local/halloffame:approve', $a->id);
        $this->grantCap('local/halloffame:view',    $a->id);
        manager::approve_achievement($sid);
        $r = get_achievements::execute([]);
        $this->assertCount(1, $r);
        $this->assertEquals('CSM', $r[0]['title']);
        $this->assertArrayHasKey('issuedatefmt', $r[0]);
    }

    // ── like_item ─────────────────────────────────────────────────────────────

    public function test_like_item_toggles_correctly(): void {
        $u = $this->getDataGenerator()->create_user();
        $a = $this->getDataGenerator()->create_user();
        $this->setUser($a);
        $this->grantCap('local/halloffame:manageawards', $a->id);
        $aid = manager::create_award(['userid'=>$u->id,'title'=>'L','department'=>'HR','month'=>1,'year'=>2025,'message'=>'','image'=>'']);
        $this->setUser($u);
        $this->grantCap('local/halloffame:view', $u->id);
        $r1 = like_item::execute($aid, 'award');
        $this->assertEquals(1, $r1['liked']);
        $this->assertEquals(1, $r1['count']);
        $r2 = like_item::execute($aid, 'award');
        $this->assertEquals(0, $r2['liked']);
        $this->assertEquals(0, $r2['count']);
    }

    public function test_like_item_invalid_type_throws(): void {
        $u = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $this->grantCap('local/halloffame:view', $u->id);
        $this->expectException(\invalid_parameter_exception::class);
        like_item::execute(1, 'notvalid');
    }

    // ── submit_certificate ────────────────────────────────────────────────────

    public function test_submit_certificate_creates_record(): void {
        global $DB;
        $u = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $this->grantCap('local/halloffame:submit', $u->id);
        $r = submit_certificate::execute('PMP', 'PMI', strtotime('2025-06-01'), 'Project Management', 'First attempt', '');
        $this->assertTrue($r['success']);
        $this->assertGreaterThan(0, $r['id']);
        $rec = $DB->get_record('halloffame_submissions', ['id'=>$r['id']]);
        $this->assertEquals('PMP', $rec->title);
        $this->assertEquals('pending', $rec->status);
    }

    public function test_submit_certificate_empty_title_throws(): void {
        $u = $this->getDataGenerator()->create_user();
        $this->setUser($u);
        $this->grantCap('local/halloffame:submit', $u->id);
        $this->expectException(\invalid_parameter_exception::class);
        submit_certificate::execute('', 'Nobody', 0, '', '', '');
    }
}
