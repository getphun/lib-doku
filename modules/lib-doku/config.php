<?php
/**
 * lib-doku config file
 * @package lib-doku
 * @version 0.0.1
 * @upgrade true
 */

return [
    '__name' => 'lib-doku',
    '__version' => '0.0.1',
    '__git' => 'https://github.com/getphun/lib-doku',
    '__files' => [
        'modules/lib-doku' => [
            'install',
            'remove',
            'update'
        ]
    ],
    '__dependencies' => [],
    '_services' => [
        'doku' => 'LibDoku\\Service\\Doku'
    ],
    '_autoload' => [
        'classes' => [
            'LibDoku\\Service\\Doku' => 'modules/lib-doku/service/Doku.php'
        ],
        'files' => []
    ]
];