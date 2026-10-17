<?php

namespace Tests\Unit;

use PDO;
use Tests\TestCase;

class CampaignPrimaryPageMigrationTest extends TestCase
{
    public function testUpgradePreservesPreviouslyPublishedPageAndIgnoresDrafts(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE campaigns(id INTEGER PRIMARY KEY, workspace_id INTEGER); CREATE TABLE funnels(id INTEGER PRIMARY KEY, workspace_id INTEGER, campaign_id INTEGER, status TEXT);');
        $pdo->exec("INSERT INTO campaigns VALUES (1,10),(2,10); INSERT INTO funnels VALUES (1,10,1,'published'),(2,10,1,'published'),(3,10,1,'draft'),(4,99,1,'published');");
        $pdo->exec(file_get_contents(__DIR__ . '/../../migrations/0023_campaign_primary_page.sqlite.sql'));
        $this->assertEquals(2, (int)$pdo->query('SELECT primary_funnel_id FROM campaigns WHERE id = 1')->fetchColumn());
        $this->assertEquals(null, $pdo->query('SELECT primary_funnel_id FROM campaigns WHERE id = 2')->fetchColumn());
    }
}
