<?php

namespace Deployer;

require __DIR__.'/contao.php';
require __DIR__.'/contao-rsync.php';

set('keep_releases', 10);

add('exclude', [
    '.DS_Store',
    '/.githooks',
    '/backups',
    '/var/backups',
    '/themes/*/assets',
    '/package.json',
    '/package-lock.json',
    '/yarn.lock',
    '/.php-version',
    '/.php-cs-fixer.dist.php',
    '/tailwind-preset.js',
    '/node_modules',
]);

task('encore:compile', function () {
    runLocally('yarn run prod');
});

before('deploy', 'encore:compile');

before('deploy:publish', 'contao:install:lock');
before('deploy:publish', 'contao:manager:download');
after('contao:manager:download', 'contao:manager:lock');

after('deploy:failed', 'deploy:unlock');
