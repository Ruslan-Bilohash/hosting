<?php
declare(strict_types=1);

/** @return array<string, array{label:string,api_base:string,models:list<string>}> */
function hs_ai_providers(): array
{
    return [
        'openai' => [
            'label' => 'OpenAI (ChatGPT)',
            'api_base' => 'https://api.openai.com/v1',
            'models' => ['gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini'],
        ],
        'grok' => [
            'label' => 'xAI Grok',
            'api_base' => 'https://api.x.ai/v1',
            'models' => ['grok-3-mini', 'grok-3', 'grok-2-latest'],
        ],
    ];
}

function hs_ai_defaults(): array
{
    return [
        'enabled' => false,
        'provider' => 'openai',
        'openai_api_key' => '',
        'grok_api_key' => '',
        'openai_model' => 'gpt-4o-mini',
        'grok_model' => 'grok-3-mini',
    ];
}

/** @param array<string,mixed> $ai */
function hs_ai_normalize(array $ai): array
{
    return array_merge(hs_ai_defaults(), is_array($ai) ? $ai : []);
}

/** @param array<string,mixed> $ai */
function hs_ai_resolve(array $ai, ?string $providerOverride = null): array
{
    $ai = hs_ai_normalize($ai);
    $providers = hs_ai_providers();
    $provider = $providerOverride ?? (string) ($ai['provider'] ?? 'openai');
    if (!isset($providers[$provider])) {
        $provider = 'openai';
    }
    $preset = $providers[$provider];
    $keyField = $provider === 'grok' ? 'grok_api_key' : 'openai_api_key';
    $modelField = $provider === 'grok' ? 'grok_model' : 'openai_model';
    $apiKey = trim((string) ($ai[$keyField] ?? ''));
    $model = trim((string) ($ai[$modelField] ?? ''));
    if ($model === '') {
        $model = $preset['models'][0] ?? 'gpt-4o-mini';
    }
    return [
        'provider' => $provider,
        'api_base' => rtrim($preset['api_base'], '/'),
        'api_key' => $apiKey,
        'model' => $model,
    ];
}

/** @return array{ok:bool,text:string,error:string} */
function hs_ai_call(array $ai, string $prompt, int $maxTokens = 900): array
{
    $resolved = hs_ai_resolve($ai);
    if ($resolved['api_key'] === '') {
        return ['ok' => false, 'text' => '', 'error' => 'api_key_missing'];
    }
    $endpoint = $resolved['api_base'] . '/chat/completions';
    $payload = [
        'model' => $resolved['model'],
        'messages' => [
            ['role' => 'system', 'content' => 'You are a professional hosting support assistant for BILOHASH. Reply with plain text or valid JSON when asked.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.55,
        'max_tokens' => max(64, min(2000, $maxTokens)),
    ];
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return ['ok' => false, 'text' => '', 'error' => 'json_encode'];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $resolved['api_key'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $http < 200 || $http >= 300) {
            return ['ok' => false, 'text' => '', 'error' => 'api_http_' . $http];
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$resolved['api_key']}\r\n",
                'content' => $body,
                'timeout' => 45,
            ],
        ]);
        $raw = @file_get_contents($endpoint, false, $ctx);
        if ($raw === false) {
            return ['ok' => false, 'text' => '', 'error' => 'api_request_failed'];
        }
    }

    $decoded = json_decode((string) $raw, true);
    $text = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
    if ($text === '') {
        return ['ok' => false, 'text' => '', 'error' => 'empty_response'];
    }
    return ['ok' => true, 'text' => $text, 'error' => ''];
}

/**
 * @param array<string,mixed> $context
 * @return array{ok:bool,demo:bool,subject:string,body:string,error:string}
 */
function hs_support_ai_compose(array $ai, string $agent, string $draft, string $lang, array $context = []): array
{
    $ai = hs_ai_normalize($ai);
    $username = trim((string) ($context['username'] ?? ''));
    $site = trim((string) ($context['site'] ?? ''));
    $adminName = trim((string) ($context['admin_name'] ?? $username));
    $draft = trim($draft);

    $agentPrompts = [
        'technical' => 'You are a technical hosting expert. Help the client describe their server/site issue clearly for BILOHASH support.',
        'billing' => 'You are a billing support agent. Help formulate a polite question about hosting plan, invoice or renewal.',
        'migration' => 'You are a migration consultant. Help describe moving a website to BILOHASH hosting.',
        'wordpress' => 'You are a WordPress hosting specialist. Help describe WP install, plugin or update issues.',
        'general' => 'You help a hosting panel user write a clear support message to BILOHASH owner.',
    ];
    $agentHint = $agentPrompts[$agent] ?? $agentPrompts['general'];
    $provider = match ($agent) {
        'technical', 'wordpress' => 'grok',
        'billing', 'migration' => 'openai',
        default => null,
    };

    $fallback = hs_support_ai_fallback($draft, $lang, $context);
    if (empty($ai['enabled'])) {
        return array_merge($fallback, ['demo' => true, 'error' => '']);
    }
    $resolved = hs_ai_resolve($ai, $provider);
    if ($resolved['api_key'] === '') {
        return array_merge($fallback, ['demo' => true, 'error' => 'api_key_missing']);
    }

    $prompt = $agentHint . ' Client: ' . $adminName
        . ($username !== '' ? ' (@' . $username . ')' : '')
        . ($site !== '' ? '. Site/folder: ' . $site : '')
        . '. Language: ' . $lang . '. Draft notes: "' . ($draft !== '' ? $draft : 'Need hosting support') . '". '
        . 'Return ONLY valid JSON {"subject":"...","body":"..."} — concise subject, body with problem, steps tried, and what they need.';

    $result = hs_ai_call($ai, $prompt, 900);
    if (!$result['ok']) {
        return array_merge($fallback, ['demo' => true, 'error' => $result['error']]);
    }

    $raw = trim($result['text']);
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $raw, $m)) {
        $raw = trim($m[1]);
    }
    $parsed = json_decode($raw, true);
    $subject = trim((string) ($parsed['subject'] ?? ''));
    $body = trim((string) ($parsed['body'] ?? ''));
    if ($subject === '' || $body === '') {
        return array_merge($fallback, ['demo' => true, 'error' => 'invalid_json']);
    }
    return ['ok' => true, 'demo' => false, 'subject' => $subject, 'body' => $body, 'error' => ''];
}

/**
 * @param array<string,mixed> $context
 * @return array{ok:bool,demo:bool,subject:string,body:string,error:string}
 */
function hs_support_ai_fallback(string $draft, string $lang, array $context): array
{
    $adminName = trim((string) ($context['admin_name'] ?? 'Client'));
    $username = trim((string) ($context['username'] ?? ''));
    $site = trim((string) ($context['site'] ?? ''));
    $draft = $draft !== '' ? $draft : 'I need assistance with my hosting account.';
    $prefix = $username !== '' ? "Account: {$username}\n" : '';
    if ($site !== '') {
        $prefix .= "Site: {$site}\n";
    }
    $subjects = [
        'uk' => 'Запит у підтримку хостингу',
        'no' => 'Hosting support request',
        'en' => 'Hosting support request',
    ];
    $subject = ($subjects[$lang] ?? $subjects['en']) . ($username !== '' ? ' — ' . $username : '');
    return [
        'ok' => true,
        'demo' => true,
        'subject' => $subject,
        'body' => "Hello BILOHASH team,\n\n" . $prefix . "\n" . $draft . "\n\n— " . $adminName,
        'error' => '',
    ];
}