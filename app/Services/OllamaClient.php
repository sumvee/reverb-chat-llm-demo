<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin client for a local Ollama server. One job: turn a list of chat messages
 * into a reply string. Kept deliberately small so the job and any future caller
 * depend on this seam, not on Ollama's HTTP shape.
 */
class OllamaClient
{
    public function __construct(
        private string $baseUrl,
        private string $model,
        private int $maxTokens = 220,
        private int $timeout = 120,
        private float $temperature = 0.3,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): string
    {
        $response = Http::timeout($this->timeout)
            ->post(rtrim($this->baseUrl, '/').'/api/chat', [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'num_predict' => $this->maxTokens,
                    'temperature' => $this->temperature,
                ],
            ]);

        $response->throw();

        return trim((string) $response->json('message.content', ''));
    }

    /**
     * Streaming variant: invokes $onDelta($text) for each chunk as it is
     * generated and returns the full reply. Ollama streams newline-delimited
     * JSON; we parse it line by line off the response body.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function streamChat(array $messages, callable $onDelta): string
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['stream' => true])
            ->post(rtrim($this->baseUrl, '/').'/api/chat', [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => true,
                'options' => [
                    'num_predict' => $this->maxTokens,
                    'temperature' => $this->temperature,
                ],
            ]);

        $response->throw();

        $full = '';
        $buffer = '';
        $body = $response->toPsrResponse()->getBody();

        while (! $body->eof()) {
            $buffer .= $body->read(2048);
            while (($nl = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $nl));
                $buffer = substr($buffer, $nl + 1);
                if ($line === '') {
                    continue;
                }
                $chunk = json_decode($line, true);
                $delta = $chunk['message']['content'] ?? '';
                if ($delta !== '') {
                    $full .= $delta;
                    $onDelta($delta);
                }
                if (! empty($chunk['done'])) {
                    return $full;
                }
            }
        }

        return $full;
    }
}
