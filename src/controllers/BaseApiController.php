<?php
declare(strict_types=1);
namespace core\controllers;

/**
 * Classe marcatore: i controller che estendono questa vengono
 * censiti automaticamente come controller "API" (prefisso /api, ValidateApikey forzato).
 */
abstract class BaseApiController extends BaseController { }
