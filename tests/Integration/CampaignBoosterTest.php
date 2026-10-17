<?php

namespace Tests\Integration;

use App\Campaigns\CampaignPage;
use App\Database\Connection;
use Tests\TestCase;
use PDO;

class CampaignBoosterTest extends TestCase
{
    private PDO $pdo;
    protected function setUp(): void
    {
        $this->pdo = Connection::make();
        $this->pdo->beginTransaction();
    }
    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }
    public function testCampaignBoosterUpdatesDraftWithoutChangingPublicOrDownsell(): void
    {
        $this->pdo->exec("INSERT INTO campaigns (workspace_id, title, ticket_price) VALUES (1, 'Campanha teste Booster', 0.5)");
        $campaign = CampaignPage::campaign($this->pdo, 1, (int)$this->pdo->lastInsertId());
        $initial = CampaignPage::boosterInput(['booster_name'=>'Booster Ouro', 'booster_multiplier'=>200, 'booster_price'=>9.9]);
        CampaignPage::saveBoosterDraft($this->pdo, $campaign, $initial);
        CampaignPage::publishOffer($this->pdo, $campaign, 'scratch');
        $alternative = CampaignPage::offer($this->pdo, $campaign, 'downsell');
        $updated = CampaignPage::boosterInput(['booster_name'=>'Booster Diamante 200X', 'booster_multiplier'=>500, 'booster_price'=>19.9]);
        CampaignPage::saveBoosterDraft($this->pdo, $campaign, $updated);
        $offer = CampaignPage::offer($this->pdo, $campaign);
        $draft = json_decode($offer['draft_config'], true);
        $published = json_decode($offer['published_config'], true);
        $this->assertEquals('Booster Diamante 500X', $draft['title']);
        $this->assertEquals(500, $draft['quota_count']);
        $this->assertEquals(19.9, $draft['price']);
        $this->assertEquals('Campanha teste Booster', $draft['subtitle']);
        $this->assertEquals('Booster Diamante 500X', $draft['theme']['headline_highlight']);
        $this->assertEquals('Booster Diamante 500X', $draft['theme']['wheel_highlight']);
        $this->assertEquals('500X', array_values(array_filter($draft['theme']['wheel_segments'], fn($s) => $s['is_win']))[0]['label']);
        $this->assertEquals('Booster Ouro 200X', $published['title']);
        $this->assertEquals(9.9, $published['price']);
        $this->assertEquals($alternative['price'], CampaignPage::offer($this->pdo, $campaign, 'downsell')['price']);
        $this->assertEquals(0.5, (float)CampaignPage::campaign($this->pdo, 1, $campaign['id'])['ticket_price']);
    }
    public function testBoosterInputRejectsInvalidFinancialAndMultiplierValues(): void
    {
        foreach ([['',200,10], ['Booster',0,10], ['Booster',1.5,10], ['Booster',200,-1], ['Booster',200,'invalid']] as [$name,$times,$price]) {
            $rejected = false;
            try { CampaignPage::boosterInput(['booster_name'=>$name,'booster_multiplier'=>$times,'booster_price'=>$price]); }
            catch (\InvalidArgumentException $e) { $rejected = true; }
            $this->assertTrue($rejected, 'Invalid Booster data must be rejected before saving.');
        }
        $this->assertEquals(null, CampaignPage::boosterInput(['title'=>'Legacy campaign client']));
    }
}
