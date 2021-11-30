<?php

namespace Deployer;

require __DIR__.'/contao.php';
require __DIR__.'/contao-rsync.php';

set('keep_releases', 10);

add('exclude', [
    '/.githooks',
    '/themes/*/assets',
    'package.json',
    'package-lock.json',
    'yarn.lock',
    '/node_modules',
]);

task('encore:compile', function () {
    runLocally('yarn run prod');
});

before('deploy', 'encore:compile');

after('deploy:failed', 'deploy:unlock');
