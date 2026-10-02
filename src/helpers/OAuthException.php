<?php
declare(strict_types=1);
namespace core\helpers;

use RuntimeException;

/**
 * Errore nello scambio con un provider OAuth (rete, token o profilo non ricevuti).
 */
class OAuthException extends RuntimeException { }
