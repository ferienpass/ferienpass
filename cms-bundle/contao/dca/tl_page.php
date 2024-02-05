<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['palettes']['offer_list'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['offer_details'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['host_details'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['applications'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['user_account'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['notifications'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['personal_data'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['account_deleted'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['lost_password'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['lost_password_confirm'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['registration_activate'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['registration_confirm'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';
$GLOBALS['TL_DCA']['tl_page']['palettes']['registration_welcome'] = '{title_legend},title,type;{routing_legend},alias,routePath;{expert_legend:hide},hide;{publish_legend},published';

(new PaletteManipulator())
    ->removeField('requireItem')
    ->removeField('routePath')
    ->removeField('routePriority')
    ->removeField('routeConflicts')
    ->removeField('canonicalLink')
    ->removeField('canonicalKeepParams')
    ->removeField('protected')
    ->removeField('includeCache')
    ->removeField('includeChmod')
    ->removeField('cssClass')
    ->removeField('sitemap')
    ->removeField('noSearch')
    ->removeField('accesskey')
    ->applyToPalette('regular', 'tl_page')
;

unset(
    $GLOBALS['TL_DCA']['tl_page']['fields']['type']['filter'],
    $GLOBALS['TL_DCA']['tl_page']['fields']['protected']['filter'],
    $GLOBALS['TL_DCA']['tl_page']['fields']['groups']['filter'],
    $GLOBALS['TL_DCA']['tl_page']['fields']['noSearch']['filter'],
);
