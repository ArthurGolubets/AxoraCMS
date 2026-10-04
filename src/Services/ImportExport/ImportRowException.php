<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport;

use InvalidArgumentException;

/**
 * A row cannot be imported; the message is shown to the administrator as is.
 */
class ImportRowException extends InvalidArgumentException {}
