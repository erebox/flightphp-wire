<?php
declare(strict_types=1);
namespace core\controllers;

/**
 * Classe marcatore: i controller che estendono questa vengono
 * censiti automaticamente come controller "web" (route pubbliche, no apikey).
 */
abstract class BaseWebController extends BaseController { }
