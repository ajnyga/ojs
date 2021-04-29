<?php

/**
 * @defgroup pages_articles Articles archive page
 */

/**
 * @file pages/articles/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_articles
 * @brief Handle requests for articles archive view.
 *
 */

switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'ArticlesHandler');
		import('pages.articles.ArticlesHandler');
		break;
	case 'category':
	case 'fullSize':
	case 'thumbnail':
		define('HANDLER_CLASS', 'PKPCatalogHandler');
		import('lib.pkp.pages.catalog.PKPCatalogHandler');
		break;
	case 'section':
		define('HANDLER_CLASS', 'SectionsHandler');
		import('pages.articles.SectionsHandler');
		break;
}


