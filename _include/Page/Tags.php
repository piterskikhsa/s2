<?php
/**
 * Displays tags list page.
 *
 * @copyright (C) 2007-2014 Roman Parpalak
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package S2
 */


class Page_Tags extends Page_Abstract
{
	public function __construct (array $params = array())
	{
		global $lang_common;

		$page = array(
			'text'			=> '<div class="tags_list">'.Placeholder::tags_list().'</div>',
			'date'			=> '',
			'title'			=> $lang_common['Tags'],
			'path'			=> array(
				array(
					'title' => Model::main_page_title(),
					'link'  => s2_link('/'),
				),
				array(
					'title' => $lang_common['Tags'],
				),
			),
		);

		($hook = s2_hook('Page_Tags_end')) ? eval($hook) : null;

		$this->page = $page + $this->page;
	}
}
