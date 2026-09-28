<?php

namespace App\Services\ThemeCanvas;

use RuntimeException;

/** A declared theme-global asset could not be validated and localized atomically. */
class ThemeAssetLocalizationException extends RuntimeException {}
