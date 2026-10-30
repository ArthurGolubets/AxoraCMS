<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Bitrix24;

use RuntimeException;

/**
 * An error of the Bitrix24 REST API. The message is safe to show in the admin
 * panel: it never contains the webhook address.
 */
class Bitrix24Exception extends RuntimeException {}
