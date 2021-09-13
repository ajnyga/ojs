<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I5932_GenerateSectionUrlPaths.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I5932_GenerateSectionUrlPaths
 * @brief Generate URL paths for sections.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Migrations\Migration;

class I5932_GenerateSectionUrlPaths extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {
        // pkp/pkp-lib#5932 Generate URL paths for sections
        $contextsIterator = Services::get('context')->getMany();
        foreach ($contextsIterator as $context) {
            $sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
            $sectionIterator = $sectionDao->getByJournalId($context->getId());
            while ($section = $sectionIterator->next()) {
                $sectionTitle = $section->getLocalizedTitle();
                $sectionUrlpath = \Stringy\Stringy::create($sectionTitle)->toAscii()->toLowerCase()->dasherize()->regexReplace('[^a-z0-9\-\_.]', '');
                $section->setUrlPath($sectionUrlpath);
                $sectionDao->updateObject($section);
            }
        }
    }

    /**
     * Reverse the downgrades
     */
    public function down()
    {
        // Downgrades not required
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I5932_GenerateSectionUrlPaths', '\I5932_GenerateSectionUrlPaths');
}
