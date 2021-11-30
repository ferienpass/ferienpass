<?php

namespace Deployer;

import('recipe/symfony.php');

add('recipes', ['contao']);

add('shared_files', ['config/parameters.yml']);

add('shared_dirs', [
    'assets/images',
    'contao-manager',
    'files',
    'public/share',
    'system/config',
    'var/backups',
    'var/logs',
]);

set('console_options', function () {
    return '--no-interaction';
});

set('bin/console', function () {
    return '{{release_path}}/vendor/bin/contao-console';
});

desc('Validate local Contao setup');
task('contao:validate', function () {
    runLocally('./vendor/bin/contao-console contao:version');
});

desc('Run Contao migrations ');
task('contao:migrate', function () {
    run('{{bin/php}} {{bin/console}} contao:migrate {{console_options}}');
});

desc('Download the Contao Manager');
task('contao:manager:download', function () {
    run('cd {{release_path}} && curl -LsO https://download.contao.org/contao-manager/stable/contao-manager.phar && mv contao-manager.phar public/contao-manager.phar.php');
});

desc('Enable maintenance mode');
task('contao:maintenance:enable', function () {
    run('{{bin/php}} {{bin/console}} contao:maintenance-mode --enable {{console_options}}');
});

desc('Disable maintenance mode');
task('contao:maintenance:disable', function () {
    run('{{bin/php}} {{bin/console}} contao:maintenance-mode --disable {{console_options}}');
});

desc('Deploy project');
task('deploy', [
    'contao:validate',
    'deploy:prepare',
    'deploy:vendors',
    'contao:maintenance:enable',
    'contao:migrate',
    'contao:maintenance:disable',
    'deploy:publish',
]);
