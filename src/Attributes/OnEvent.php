<?php

namespace Io\Discord\Attributes;

use Attribute;

/** Makes a method a listener for the gateway event its parameter's type names: `ready(Ready $ready)`. */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class OnEvent {}
