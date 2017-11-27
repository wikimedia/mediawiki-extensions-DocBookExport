<?php

class DocBookExport {

	static $excludedTags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');

	public static function onParserSetup( Parser &$parser ) {
		$parser->setHook( 'docbook', 'DocBookExport::parseDocBookSyntax' );
		return true;
	}

	public static function parseDocBookSyntax( $input, array $args, Parser $parser, PPFrame $frame ) {

		$book_title = '';
		$book_contents = '<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.1.2//EN" "http://www.oasis-open.org/docbook/xml/4.1.2/docbookx.dtd">
		<book>';

		if ($args['title'] == null) {
			return 'Error: Title must be specified';
		}
		$book_contents .= '<title>' . $args['title'] . '</title>';

		$chapter_number = 0;
		$section_number = 'a';

		$page_structure = explode("\n", $input);
		foreach($page_structure as $current_line) {
			$parts = explode(' ', $current_line, 2);
			if (count($parts) < 2) {
				continue;
			}
			$identifier = $parts[0];
			$after_identifier = $parts[1];

			if ($identifier == '*'){
				$book_contents .= '<chapter label="'. $chapter_number++ .'">';
			} else if ($identifier == '**') {
				$book_contents .= '<section label="'. $chapter_number . $section_number .'">';
				$section_number++;
			} else {
				continue;
			}

			$parts = explode('=', $after_identifier);
			$wiki_pages = explode(',', $parts[0]);

			if (count($parts) == 2) {
				$display_pagename = $parts[1];
			} else {
				$display_pagename = $wiki_pages[0];
			}

			$book_contents .= '<title>'. $display_pagename .'</title>';
			foreach($wiki_pages as $wikipage) {
				$titleObj = Title::newFromText( $wikipage );
				$pageObj = WikiPage::factory( $titleObj );
				$pageObj->loadPageData( 'fromdb' );
				$titleObj = $pageObj->getTitle();
				$content = $pageObj->getContent( Revision::RAW );
				if ($content != null) {
					$popts = $pageObj->makeParserOptions( RequestContext::getMain() );
					$popts->enableLimitReport( false );
					$popts->setIsPreview( false );
					$popts->setIsSectionPreview( false );
					$popts->setEditSection( false );
					$popts->setTidy( true );
					$page_html = $pageObj->getParserOutput($popts)->getText();
					$dom = new DOMDocument();
					$dom->loadHtml('<html>' . $page_html . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
					foreach(self::$excludedTags as $tag) {
						foreach($dom->getElementsByTagName($tag) as $node) {
							$node->parentNode->removeChild($node);
						}
					}
					$dir = __DIR__ . '/';
					$dir = str_replace("\\", "/", $dir);
					file_put_contents($dir . "tmp.html", $dom->saveHTML());
					$cmd = "pandoc ". $dir . "tmp.html -f html -t docbook5 2>&1";
					$pandoc_output = shell_exec($cmd);
					if ($pandoc_output != null) {
						$book_contents .= $pandoc_output;
					}
				}
			}
			if ($identifier == '*'){
				$book_contents .= '</chapter>';
			} else if ($identifier == '**') {
				$book_contents .= '</section>';
			}
		}
		$book_contents .= '</book>';
		$file_path = $dir . str_replace(' ', '_', $args['title']) .".db";
		file_put_contents($file_path, $book_contents);
		global $wgScriptPath;
		$file_server_path = $wgScriptPath . '/extensions/DocBookExport/' . str_replace(' ', '_', $args['title']) .".db";
		return '<a href="' . $file_server_path . '">Open File</a>';
	}

}

?>