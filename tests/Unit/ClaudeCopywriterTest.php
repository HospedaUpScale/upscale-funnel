<?php

namespace Tests\Unit;

use App\AI\ClaudeCopywriter;
use Tests\TestCase;

class ClaudeCopywriterTest extends TestCase
{
    private function response(array $replace = []): array
    {
        return array_replace([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => json_encode([
                'title' => 'Booster', 'subtitle' => 'Sua campanha',
                'offer_headline_start' => 'Oferta disponível', 'offer_headline_highlight' => 'Confira os detalhes',
                'cta_label' => 'ATIVAR AGORA', 'cta_subtext' => 'Pagamento via Pix', 'price' => 999,
            ])]], 'usage' => ['input_tokens' => 500, 'output_tokens' => 150],
        ], $replace);
    }

    public function testPayloadIsBoundedAndResultCannotChangePrice(): void
    {
        $client = new ClaudeCopywriter('fake-test-key', function ($body) {
            $data = json_decode($body, true);
            $this->assertEquals(ClaudeCopywriter::MODEL, $data['model']);
            $this->assertEquals(900, $data['max_tokens']);
            $this->assertFalse(str_contains($body, 'fake-test-key'));
            $this->assertFalse(str_contains($body, 'customer@example.com'));
            return $this->response();
        });
        $input = ClaudeCopywriter::input('Melhorar clareza', [
            'title' => 'Booster', 'price' => 0.5, 'quota_count' => 20,
            'email' => 'customer@example.com', 'api_key' => 'ignored',
        ]);
        $result = $client->generate($input);
        $this->assertEquals(6, count($result['suggestion']));
        $this->assertFalse(isset($result['suggestion']['price']));
        $this->assertEquals('ATIVAR AGORA', $result['suggestion']['cta_label']);
        $this->assertEquals(150, $result['tokens']['output']);
    }

    public function testInvalidAndOversizedInputAreRejectedBeforeTransport(): void
    {
        foreach ([['', []], [str_repeat('x', 2501), []], ['ok', ['price' => -1]],
            ['ok', ['title' => []]], ['ok', ['title' => str_repeat('á', 161)]], ['ok', ['price' => INF]],
            ['ok', ['title' => "\xFF"]], ['ok', null]] as [$brief, $context]) {
            try {
                ClaudeCopywriter::input($brief, $context);
                $this->assertTrue(false, 'Entrada inválida deveria falhar.');
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testMalformedPartialAndOversizedResponsesAreRejected(): void
    {
        foreach ([
            $this->response(['stop_reason' => 'max_tokens']),
            $this->response(['content' => [['type' => 'text', 'text' => 'Não é JSON']]]),
            $this->response(['content' => [['type' => 'text', 'text' => '{"title":"incompleto"}']]]),
            $this->response(['content' => [['type' => 'text', 'text' => str_replace('ATIVAR AGORA', str_repeat('x', 81), $this->response()['content'][0]['text'])]]]),
        ] as $response) {
            $client = new ClaudeCopywriter('fake', fn() => $response);
            try {
                $client->generate(ClaudeCopywriter::input('Melhorar', []));
                $this->assertTrue(false, 'Resposta inválida deveria falhar.');
            } catch (\RuntimeException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testMarkdownFenceAndHtmlDoNotReachEditorAsMarkup(): void
    {
        $response = $this->response();
        $response['content'][0]['text'] = "```json\n" . str_replace('ATIVAR AGORA', '<b>ATIVAR AGORA</b>', $response['content'][0]['text']) . "\n```";
        $result = (new ClaudeCopywriter('fake', fn() => $response))->generate(ClaudeCopywriter::input('Melhorar', []));
        $this->assertEquals('ATIVAR AGORA', $result['suggestion']['cta_label']);
    }
}
