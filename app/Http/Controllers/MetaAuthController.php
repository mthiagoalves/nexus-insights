<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\PlatformConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $user = $request->user();

        $workspace = $user->workspaces()
            ->select('workspaces.id', 'workspaces.name', 'workspaces.slug')
            ->first();

        if (! $workspace) {
            abort(403, 'Nenhum workspace encontrado para este usuário.');
        }

        // Guardamos o workspace atual na sessão para usar no callback
        $request->session()->put('meta_connect_workspace_id', $workspace->id);

        $clientId    = config('services.meta.client_id');
        $redirectUri = config('services.meta.redirect');
        $apiVersion  = config('services.meta.api_version', 'v20.0');

        // Scopes mínimos para ler e gerenciar ads
        $scopes = [
            'ads_read',
            'ads_management',
            'business_management',
        ];

        $state = csrf_token(); // simples por enquanto
        $request->session()->put('meta_oauth_state', $state);

        $authUrl = sprintf(
            'https://www.facebook.com/%s/dialog/oauth?client_id=%s&redirect_uri=%s&state=%s&scope=%s',
            $apiVersion,
            urlencode($clientId),
            urlencode($redirectUri),
            urlencode($state),
            urlencode(implode(',', $scopes))
        );

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $storedState = $request->session()->pull('meta_oauth_state');

        if (! $storedState || $storedState !== $request->get('state')) {
            return redirect()->route('connections.index')
                ->with('error', 'Estado de autenticação inválido. Tente novamente.');
        }

        if ($request->has('error')) {
            return redirect()->route('connections.index')
                ->with('error', 'Permissão negada na Meta. Autorização cancelada.');
        }

        $code       = $request->get('code');
        $workspaceId = $request->session()->pull('meta_connect_workspace_id');

        if (! $code || ! $workspaceId) {
            return redirect()->route('connections.index')
                ->with('error', 'Código de autorização ou workspace inválido.');
        }

        $clientId     = config('services.meta.client_id');
        $clientSecret = config('services.meta.client_secret');
        $redirectUri  = config('services.meta.redirect');
        $apiVersion   = config('services.meta.api_version', 'v20.0');

        try {
            // 1) Trocar o "code" por um access token de curto prazo
            $tokenResponse = Http::get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
            ]);

            if (! $tokenResponse->ok()) {
                Log::error('Meta OAuth token error', ['body' => $tokenResponse->body()]);
                return redirect()->route('connections.index')
                    ->with('error', 'Erro ao obter token de acesso da Meta.');
            }

            $tokenData = $tokenResponse->json();
            $shortLivedToken = $tokenData['access_token'] ?? null;

            if (! $shortLivedToken) {
                return redirect()->route('connections.index')
                    ->with('error', 'Token de acesso não retornado pela Meta.');
            }

            // 2) Trocar por um token de longo prazo (opcional mas recomendado)
            $longLivedResponse = Http::get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $clientId,
                'client_secret'     => $clientSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

            $longTokenData = $longLivedResponse->ok()
                ? $longLivedResponse->json()
                : [];

            $accessToken = $longTokenData['access_token'] ?? $shortLivedToken;
            $expiresIn   = $longTokenData['expires_in'] ?? null;

            $tokenExpiresAt = $expiresIn
                ? now()->addSeconds((int) $expiresIn)
                : null;

            // 3) Buscar perfil básico do usuário da Meta
            $meResponse = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/{$apiVersion}/me", [
                    'fields' => 'id,name',
                ]);

            if (! $meResponse->ok()) {
                Log::error('Meta /me error', ['body' => $meResponse->body()]);
                return redirect()->route('connections.index')
                    ->with('error', 'Erro ao obter dados do usuário na Meta.');
            }

            $me = $meResponse->json();

            // 4) Criar ou atualizar PlatformConnection
            $connection = PlatformConnection::updateOrCreate(
                [
                    'workspace_id'        => $workspaceId,
                    'provider'            => 'meta',
                    'external_account_id' => $me['id'],
                ],
                [
                    'name'             => 'Meta - ' . $me['name'],
                    'access_token'     => $accessToken,
                    'refresh_token'    => null,
                    'token_expires_at' => $tokenExpiresAt,
                    'extra_data'       => [
                        'meta_user_id'   => $me['id'],
                        'meta_user_name' => $me['name'],
                    ],
                ]
            );

            // 5) Buscar as ad accounts do usuário e salvar em ad_accounts
            $accountsResponse = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/{$apiVersion}/me/adaccounts", [
                    'fields' => 'id,account_id,name,currency',
                    'limit'  => 100,
                ]);

            if ($accountsResponse->ok()) {
                $accountsData = $accountsResponse->json();

                if (isset($accountsData['data']) && is_array($accountsData['data'])) {
                    foreach ($accountsData['data'] as $acc) {
                        AdAccount::updateOrCreate(
                            [
                                'workspace_id' => $workspaceId,
                                'provider'     => 'meta',
                                'external_id'  => $acc['account_id'] ?? $acc['id'],
                            ],
                            [
                                'platform_connection_id' => $connection->id,
                                'name'                   => $acc['name'] ?? 'Conta Meta',
                                'currency'               => $acc['currency'] ?? null,
                                'extra_data'             => [
                                    'raw_id' => $acc['id'],
                                ],
                            ]
                        );
                    }
                }
            } else {
                Log::warning('Meta /me/adaccounts error', ['body' => $accountsResponse->body()]);
            }

            return redirect()->route('connections.index')
                ->with('success', 'Conta Meta conectada com sucesso! Contas de anúncio sincronizadas.');
        } catch (\Throwable $e) {
            Log::error('Meta OAuth callback exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Ocorreu um erro inesperado ao conectar com a Meta.');
        }
    }
}
