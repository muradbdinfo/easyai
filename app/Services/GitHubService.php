<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private const MAX_BYTES = 512000; // 500KB max file size

    public function __construct()
    {
        $this->clientId     = config('services.github.client_id');
        $this->clientSecret = config('services.github.client_secret');
        $this->redirectUri  = config('services.github.redirect');
    }

    public function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'    => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope'        => 'read:user repo',
            'state'        => csrf_token(),
        ]);

        return 'https://github.com/login/oauth/authorize?' . $params;
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::timeout(30)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://github.com/login/oauth/access_token', [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => $code,
                'redirect_uri'  => $this->redirectUri,
            ]);

        return $response->json() ?? [];
    }

    public function getUser(string $token): array
    {
        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get('https://api.github.com/user');

        return $response->json() ?? [];
    }

    public function listRepos(string $token, int $page = 1): array
    {
        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get('https://api.github.com/user/repos', [
                'per_page' => 30,
                'page'     => $page,
                'sort'     => 'updated',
                'type'     => 'all',
            ]);

        return $response->json() ?? [];
    }

    public function listContents(string $token, string $repo, ?string $path = ''): array
    {
        $path = $path ?? '';
        $url  = "https://api.github.com/repos/{$repo}/contents/{$path}";

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get($url);

        if (!$response->successful()) {
            return ['message' => $response->json('message') ?? 'GitHub API error'];
        }

        $items = $response->json();

        // GitHub returns single object for direct file path
        if (isset($items['type'])) {
            return [$items];
        }

        return is_array($items) ? $items : [];
    }

    public function getFileContent(string $token, string $repo, string $path): string
    {
        $url = "https://api.github.com/repos/{$repo}/contents/{$path}";

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get($url);

        if (!$response->successful()) {
            return '';
        }

        $data = $response->json();

        // Skip files that are too large
        if (isset($data['size']) && $data['size'] > self::MAX_BYTES) {
            return '';
        }

        // Standard: base64-encoded content
        if (isset($data['encoding']) && $data['encoding'] === 'base64' && isset($data['content'])) {
            return base64_decode(str_replace("\n", '', $data['content']));
        }

        // Large files: download_url
        if (isset($data['download_url'])) {
            $raw = Http::timeout(30)->withToken($token)->get($data['download_url']);
            return $raw->successful() ? $raw->body() : '';
        }

        return '';
    }
}