<?php

$finder = (new PhpCsFixer\Finder())
    ->in('src')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => true,
    ])
    ->setFinder($finder)
;
