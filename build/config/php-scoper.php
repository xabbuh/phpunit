<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$publicSymbols = json_decode(file_get_contents(__DIR__ . '/../tmp/public-symbols.json'), true);

return [
    'prefix' => 'PHPUnitPHAR',

    'exclude-classes' => $publicSymbols['classLikes'],
    'exclude-functions' => $publicSymbols['functions'],

    'expose-constants' => [
        '/^__PHPUNIT_.+$/'
    ],
];
