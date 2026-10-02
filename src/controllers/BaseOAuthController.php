<?php
declare(strict_types=1);
namespace core\controllers;

use core\attributes\Route;
use core\helpers\OAuth;
use core\helpers\OAuthException;

/**
 * Login OAuth2 pronto all'uso: il controller del sito che estende questa classe eredita le route
 *   GET      /auth/@provider  avvia il login (genera lo state, lo salva in sessione e reindirizza al provider)
 *   GET|POST /auth            callback: verifica lo state, legge il profilo e chiama onOAuthLogin()
 * e deve solo implementare onOAuthLogin() per decidere cosa fare dell'utente autenticato.
 *
 * I provider sono letti da config OAUTHS.CFG.<Provider> (vedi core\helpers\OAuth); il redirect URI da registrare
 * presso i provider è <schema>://<host>/auth.
 */
abstract class BaseOAuthController extends BaseWebController {

    protected const OAUTH_STATE_KEY = 'oauth_state';

    #[Route('/auth/@provider', 'GET', summary: 'Avvio login OAuth', hidden: true)]
    public function oauthStart(string $provider): void {
        $cfg = $this->oauthProviders()[$provider] ?? null;
        if (!is_array($cfg)) {
            $this->notFound();
            return;
        }
        $state = $provider.'.'.bin2hex(random_bytes(16));
        $this->session()->set(self::OAUTH_STATE_KEY, $state)->commit();
        $this->redirect(OAuth::authUrl($cfg, $this->oauthRedirectUri(), $state));
    }

    #[Route('/auth', 'GET|POST', summary: 'Callback OAuth', hidden: true)]
    public function oauthCallback(): void {
        $request = $this->request();
        $code = (string) ($request->query['code'] ?? $request->data['code'] ?? '');
        $state = (string) ($request->query['state'] ?? $request->data['state'] ?? '');

        $session = $this->session();
        $expected = $session->exist(self::OAUTH_STATE_KEY) ? (string) $session->get(self::OAUTH_STATE_KEY) : '';
        $session->del(self::OAUTH_STATE_KEY);
        $provider = (string) strstr($expected, '.', true);
        $cfg = $this->oauthProviders()[$provider] ?? null;

        if ($expected === '' || !hash_equals($expected, $state) || !is_array($cfg)) {
            $this->onOAuthError($provider, 'Richiesta di login non valida o scaduta.');
        } elseif ($code === '') {
            $this->onOAuthError($provider, (string) ($request->query['error_description'] ?? $request->query['error'] ?? 'Login annullato.'));
        } else {
            try {
                $this->onOAuthLogin($provider, OAuth::fetchUser($provider, $cfg, $code, $this->oauthRedirectUri()));
            } catch (OAuthException $e) {
                $this->onOAuthError($provider, $e->getMessage());
            }
        }
        $session->commit();
        $this->redirect($this->oauthReturnPath());
    }

    /**
     * Utente autenticato dal provider. Le modifiche alla sessione ($this->session()) vengono salvate dopo la chiamata.
     *
     * @param array{email: string, name: string, avatar: string, raw: array} $user
     */
    abstract protected function onOAuthLogin(string $provider, array $user): void;

    /**
     * Login fallito (state non valido, annullato dall'utente, errore del provider). Di default non fa nulla.
     */
    protected function onOAuthError(string $provider, string $message): void { }

    /**
     * Provider attivi: quelli di config OAUTHS.CFG per cui il sito ha impostato le credenziali
     * (i default della libreria forniscono solo gli endpoint).
     *
     * @return array<string, array> Configurazione dei provider, indicizzata per nome
     */
    protected function oauthProviders(): array {
        return array_filter(
            $this->get('config')['OAUTHS']['CFG'] ?? [],
            fn($cfg) => is_array($cfg) && ($cfg['clientID'] ?? '') !== '' && ($cfg['clientSecret'] ?? '') !== ''
        );
    }

    protected function oauthRedirectUri(): string {
        $request = $this->request();
        return $request->scheme.'://'.$request->host.rtrim($this->webPath, '/').'/auth';
    }

    /**
     * Pagina a cui tornare al termine del login (riuscito o no).
     */
    protected function oauthReturnPath(): string {
        return $this->webPath;
    }

}
