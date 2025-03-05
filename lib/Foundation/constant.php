<?php

defined('DIR_BOOTSTRAP') || define(
    'DIR_BOOTSTRAP',
    str_replace(
        ['\\', '/'],
        DS,
        join(
            DS, [DIR_ROOT, 'bootstrap']
        ) . DS
    )
);

defined('DIR_MODULE') || define(
    'DIR_MODULE',
    str_replace(
        ['\\', '/'],
        DS,
        join(
            DS, [DIR_ROOT, 'app', 'modules']
        ) . DS
    )
);

defined('DIR_LIB') || define(
    'DIR_LIB',
    str_replace(
        ['\\', '/'],
        DS,
        join(
            DS, [DIR_ROOT, 'lib']
        ) . DS
    )
);

defined('DIR_TEMP') || define(
    'DIR_TEMP',
    str_replace(
        ['\\', '/'],
        DS,
        join(
            DS, [DIR_ROOT, 'tmp']
        ) . DS
    )
);
