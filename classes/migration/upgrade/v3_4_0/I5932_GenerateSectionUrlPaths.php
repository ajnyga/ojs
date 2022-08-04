<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I5932_GenerateSectionUrlPaths.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I5932_GenerateSectionUrlPaths
 * @brief Create section urlPath column and generate URL paths for sections.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\db\DAORegistry;
use PKP\migration\Migration;
use Stringy\Stringy;

class I5932_GenerateSectionUrlPaths extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {

        // pkp/pkp-lib#5932 Generate URL paths for sections
        Schema::table('sections', function (Blueprint $table) {
            $table->string('url_path', 255)->after('review_form_id');
            $table->smallInteger('not_browsable')->default(0)->after('is_inactive');
        });

        $contexts = DB::table('journals AS j')
            ->select('j.journal_id')
            ->get();

        foreach ($contexts as $context) {
            $sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
            $sectionIterator = $sectionDao->getByJournalId($context->journal_id);
            while ($section = $sectionIterator->next()) {
                $sectionTitle = $section->getLocalizedTitle();
                $sectionUrlpath = Stringy::create($sectionTitle)->toAscii()->toLowerCase()->dasherize()->regexReplace('[^a-z0-9\-\_.]', '');
                $section->setUrlPath($sectionUrlpath);
                $sectionDao->updateObject($section);
            }
        }
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('url_path');
        });
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I5932_GenerateSectionUrlPaths', '\I5932_GenerateSectionUrlPaths');
}
