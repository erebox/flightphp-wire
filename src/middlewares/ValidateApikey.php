<?php
declare(strict_types=1);
namespace core\middlewares;

use Flight;

class ValidateApikey {

    public function before(): void {
        $apikey = Flight::request()->getHeader('apikey');
        if ($apikey) {
            $apikeys = Flight::get('config')['APIKEYS'];
            if (!isset($apikeys[$apikey])){
                Flight::jsonHalt(['error' => 'Forbidden.'], 403);
            } else{
                $curr_api = $apikeys[$apikey];
                if (strtotime($curr_api['expires']) > time()) {
                    Flight::set('apikey', $curr_api);
                } else {
                    Flight::jsonHalt(['error' => 'Unauthorized.'], 401);
                }
            }
        } else {
            Flight::jsonHalt(['error' => 'Not Found.'], 404);
        }
    }

}
