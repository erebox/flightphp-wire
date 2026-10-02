<?php
declare(strict_types=1);
namespace core\helpers;

/**
 * Wrapper della sessione PHP che non tiene il lock sul file di sessione:
 * - la sessione viene letta e subito chiusa (read_and_close), così le richieste concorrenti dello stesso utente non si bloccano a vicenda;
 * - se il browser non ha inviato il cookie di sessione non viene avviata nessuna sessione: la si crea solo al primo commit() con modifiche;
 * - le modifiche (set/del) restano in memoria e vengono scritte da commit().
 *
 * Registrata da Bootstrap come servizio Flight 'session' (istanza unica e lazy: $this->session() nei controller).
 */
class Session {

    protected array $data = [];
    protected bool $changed = false;
    protected bool $regenerate = false;
    protected array $options;

    protected const DEFAULT_OPTIONS = [
        'name'              => 'SESS_ID',
        'cache_limiter'     => 'nocache', // pagine legate alla sessione (login, redirect OAuth): mai in cache del browser
        'use_strict_mode'   => true,
        'use_only_cookies'  => true,
        'cookie_httponly'   => true,
        'cookie_samesite'   => 'Lax',
    ];

    /**
     * @param array $options Opzioni di session_start() da sovrascrivere a quelle di default (es. ['name' => 'MY_SESS'])
     */
    public function __construct(array $options = []) {
        $this->options = $options + self::DEFAULT_OPTIONS + ['cookie_secure' => self::isHttps()];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->data = $_SESSION;
        } elseif (isset($_COOKIE[$this->options['name']])) {
            session_start($this->options + ['read_and_close' => true]);
            $this->data = $_SESSION ?? [];
        }
    }

    public function exist(string $name): bool {
        return array_key_exists($name, $this->data);
    }

    public function get(string $name) {
        if (!$this->exist($name)) {
            throw new \UnexpectedValueException("\"{$name}\" not in current session.");
        }
        return $this->data[$name];
    }

    public function getAll(): array {
        return $this->data;
    }

    public function set(string $name, $value): self {
        $this->data[$name] = $value;
        $this->changed = true;
        return $this;
    }

    public function del(string $name): self {
        if ($this->exist($name)) {
            unset($this->data[$name]);
            $this->changed = true;
        }
        return $this;
    }

    /**
     * Rigenera l'id di sessione al prossimo commit(): da usare dopo un cambio di privilegi (login) contro la session fixation.
     */
    public function regenerate(): self {
        $this->regenerate = true;
        $this->changed = true;
        return $this;
    }

    /**
     * Scrive le modifiche: riapre la sessione (creandola se non esiste), salva i dati e rilascia subito il lock.
     */
    public function commit(): void {
        if (!$this->changed) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start($this->options);
        }
        if ($this->regenerate) {
            session_regenerate_id(true);
            $this->regenerate = false;
        }
        $_SESSION = $this->data;
        session_write_close();
        $this->changed = false;
    }

    public function id(): string {
        return session_id() ?: '';
    }

    private static function isHttps(): bool {
        return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

}
