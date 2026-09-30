<?php
declare(strict_types=1);
namespace core\middlewares;

use Flight;

class WebHeader {

    public function before(): void {
        Flight::response()->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        Flight::response()->header('Referrer-Policy', 'origin-when-cross-origin');
        Flight::response()->header('X-Frame-Options', 'SAMEORIGIN');
        Flight::response()->header('X-XSS-Protection', '1; mode=block');
        Flight::response()->header('X-Content-Type-Options', 'nosniff');
        Flight::response()->header('X-Powered-By', 'RskL8/2.0');
    }

    /*
        #header('Content-Security-Policy: default-src https:');
        #ini_set('session.cookie_httponly', 1);
    */

}
