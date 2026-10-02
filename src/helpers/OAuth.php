<?php
declare(strict_types=1);
namespace core\helpers;

/**
 * Logica OAuth2 "authorization code" comune ai provider supportati (Google, Github, Gitlab, Microsoft, Facebook, Yahoo, Dropbox, Erebox, ...).
 *
 * La configurazione di un provider è un array con:
 *   clientID, clientSecret, urlAuth (con i segnaposto {clientID} e {redirectUri}), urlAccessToken, urlUserInfo
 */
class OAuth {

    /**
     * URL di autorizzazione del provider, con il parametro state indicato (un eventuale state già presente in urlAuth viene sostituito).
     */
    public static function authUrl(array $cfg, string $redirectUri, string $state): string {
        $url = strtr($cfg['urlAuth'], [
            '{clientID}'    => rawurlencode($cfg['clientID']),
            '{redirectUri}' => rawurlencode($redirectUri),
        ]);
        $url = rtrim((string) preg_replace('/([?&])state=[^&]*(&|$)/', '$1', $url), '?&');
        return $url.(str_contains($url, '?') ? '&' : '?').'state='.rawurlencode($state);
    }

    /**
     * Scambia il code con l'access token e legge il profilo dell'utente.
     *
     * @return array{email: string, name: string, avatar: string, raw: array} avatar è un URL o un data URI, oppure '' se non disponibile
     * @throws OAuthException se il provider non restituisce token o profilo
     */
    public static function fetchUser(string $provider, array $cfg, string $code, string $redirectUri): array {
        $params = [
            'client_id'     => $cfg['clientID'],
            'client_secret' => $cfg['clientSecret'],
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ];
        if ($provider !== 'Github') {
            $params['grant_type'] = 'authorization_code';
        }
        $token = json_decode(self::request($cfg['urlAccessToken'], [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        ])['body'], true);
        $accessToken = is_array($token) ? (string) ($token['access_token'] ?? '') : '';
        if ($accessToken === '') {
            throw new OAuthException('access token non ricevuto.');
        }

        $options = [
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: flightphp-wire', 'Authorization: Bearer '.$accessToken],
        ];
        if ($provider === 'Dropbox') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = 'null';
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }
        $info = json_decode(self::request($cfg['urlUserInfo'], $options)['body'], true);
        if (!is_array($info)) {
            throw new OAuthException('profilo utente non ricevuto.');
        }

        $name = $info['name'] ?? $info['login'] ?? '';
        return [
            'email'  => (string) ($info['email'] ?? ''),
            'name'   => (string) (is_array($name) ? ($name['display_name'] ?? '') : $name), // Dropbox: name.display_name
            'avatar' => self::avatar($provider, $info, $accessToken),
            'raw'    => $info,
        ];
    }

    private static function avatar(string $provider, array $info, string $accessToken): string {
        if ($provider === 'Microsoft') {
            // Microsoft restituisce l'URL della foto su Graph, leggibile solo con il token: la si scarica come data URI
            $url = $info['picture'] ?? '';
            return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) ? self::fetchPicture($url, $accessToken) : '';
        }
        $avatar = $info['picture']['data']['url']       // Facebook
            ?? $info['profile_images']['image64']       // Yahoo
            ?? $info['avatar_url']                      // Github, Gitlab
            ?? $info['picture']                         // Google
            ?? '';
        return is_string($avatar) ? $avatar : '';
    }

    private static function fetchPicture(string $url, string $accessToken): string {
        try {
            $response = self::request(strtr($url, ['/$value' => 's/48x48/$value']), [
                CURLOPT_HTTPHEADER => ['User-Agent: flightphp-wire', 'Authorization: Bearer '.$accessToken],
            ]);
        } catch (OAuthException $e) {
            return '';
        }
        return str_starts_with($response['type'], 'image') ? 'data:'.$response['type'].';base64,'.base64_encode($response['body']) : '';
    }

    /**
     * @return array{body: string, type: string}
     * @throws OAuthException in caso di errore di rete/TLS
     */
    private static function request(string $url, array $options): array {
        $c = curl_init($url);
        curl_setopt_array($c, $options + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $body = curl_exec($c);
        if ($body === false) {
            throw new OAuthException('Errore di connessione a '.parse_url($url, PHP_URL_HOST).': '.curl_error($c));
        }
        return ['body' => (string) $body, 'type' => (string) curl_getinfo($c, CURLINFO_CONTENT_TYPE)];
    }

}
