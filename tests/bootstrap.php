<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;

(static function () {
    if (!is_file($autoloadFile = __DIR__ . '/../vendor/autoload.php')) {
        throw new RuntimeException('Did not find vendor/autoload.php. Did you run "composer install --dev"?');
    }
    AnnotationRegistry::registerLoader('class_exists');
})();
