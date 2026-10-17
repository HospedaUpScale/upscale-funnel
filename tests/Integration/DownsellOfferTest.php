<?php

namespace Tests\Integration;

use App\Campaigns\CampaignPage;
use App\Database\Connection;
use Tests\TestCase;
use PDO;

class DownsellOfferTest extends TestCase
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

    public function testDownsellPublicationPreservesPrincipalAndOtherCampaign(): void
    {
        $this->pdo->exec("INSERT INTO campaigns (workspace_id, title, ticket_price) VALUES (1, 'Downsell isolation test', 20)");
        $id = (int)$this->pdo->lastInsertId();
        $campaign = CampaignPage::campaign($this->pdo, 1, $id);
        // Creating the alternative first must never make it the main offer.
        $downsell = CampaignPage::offer($this->pdo, $campaign, 'downsell');
        $principal = CampaignPage::offer($this->pdo, $campaign);
        $this->assertTrue($principal['id'] !== $downsell['id']);
        $this->assertEquals('upsell', $principal['offer_role']);
        $this->assertEquals('downsell', $downsell['offer_role']);
        $draft = json_encode(['title' => 'Alternativa', 'price' => 9.9, 'theme' => ['mechanic' => 'direct']]);
        $this->pdo->prepare('UPDATE upsell_offers SET draft_config = ? WHERE id = ?')->execute([$draft, $downsell['id']]);
        CampaignPage::publishOffer($this->pdo, $campaign, 'direct', 'downsell');
        $saved = CampaignPage::offer($this->pdo, $campaign, 'downsell');
        $published = json_decode($saved['published_config'], true);
        $this->assertEquals('Alternativa', $published['title']);
        $this->assertEquals(9.9, $published['price']);
        $this->assertEquals(1, (int)$saved['is_published']);
        $unchanged = CampaignPage::offer($this->pdo, $campaign);
        $this->assertEquals($principal['title'], $unchanged['title']);
        $this->assertEquals(0, (int)$unchanged['is_published']);
        $this->assertEquals(20.0, (float)$unchanged['price']);
        $this->pdo->exec("INSERT INTO campaigns (workspace_id, title, ticket_price) VALUES (1, 'Other campaign isolation', 30)");
        $other = CampaignPage::campaign($this->pdo, 1, (int)$this->pdo->lastInsertId());
        $otherOffer = CampaignPage::offer($this->pdo, $other, 'downsell');
        $this->assertTrue($otherOffer['id'] !== $downsell['id']);
        $this->assertEquals(0, (int)$otherOffer['is_published']);
        $this->assertEquals(null, CampaignPage::campaign($this->pdo, 999999, $id));
    }
}
