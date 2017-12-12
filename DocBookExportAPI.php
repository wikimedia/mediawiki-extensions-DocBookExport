<?php
/**
 * @author Nischayn22
 */

class DocBookExportAPI extends ApiBase {

	static $excludedTags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		global $wgDocBookExportPandocPath, $wgScriptPath;

		$bookName = $this->getMain()->getVal( 'bookname' );
		$title = Title::newFromText( $bookName );

		$dbr = wfGetDB( DB_SLAVE );
		$propValue = $dbr->selectField( 'page_props', // table to use
			'pp_value', // Field to select
			array( 'pp_page' => $title->getArticleID(), 'pp_propname' => "docbook" ), // where conditions
			__METHOD__
		);
		$options = unserialize( $propValue );
		$book_title = '';
		$book_contents = '<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.1.2//EN" "http://www.oasis-open.org/docbook/xml/4.1.2/docbookx.dtd">
		<book>';

		$book_contents .= '<title>' . $options['title'] . '</title>';
		$page_structure = explode( "\n", $options['page structure'] );

		$index_terms = array();
		if ( array_key_exists( 'index terms', $options ) ) {
			$index_terms = explode( ",", $options['index terms'] );
		}

		$index_categories = array();
		if ( array_key_exists( 'index term categories', $options ) ) {
			$index_categories = explode( ",", $options['index term categories'] );
		}

		foreach( $index_categories as $index_category ) {
			$categoryMembers = Category::newFromName( $index_category )->getMembers();
			foreach( $categoryMembers as $index_title ) {
				$index_terms[] = $index_title->getText();
			}
		}

		$popts = new ParserOptions();
		$popts->enableLimitReport( false );
		$popts->setIsPreview( false );
		$popts->setIsSectionPreview( false );
		$popts->setEditSection( false );
		$popts->setTidy( true );

		foreach( $page_structure as $current_line ) {
			$parts = explode( ' ', $current_line, 2 );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$identifier = $parts[0];
			$after_identifier = $parts[1];

			if ( $identifier == '*' ){
				$book_contents .= '<chapter>';
			} else if ( $identifier == '**' ) {
				$book_contents .= '<section>';
			} else {
				continue;
			}

			$parts = explode( '=', $after_identifier );

			if ( count( $parts ) == 2 ) {
				$wiki_pages = explode( ',', $parts[1] );
				$display_pagename = $parts[0];
			} else {
				$wiki_pages = explode( ',', $parts[0] );
				$display_pagename = $wiki_pages[0];
			}

			$book_contents .= '<title>'. $display_pagename .'</title>';
			foreach( $wiki_pages as $wikipage ) {
				$placeholderId = 0;
				$footnotes = array();
				$xreflabels = array();

				$titleObj = Title::newFromText( $wikipage );
				$pageObj = new WikiPage( $titleObj );

				$content = $pageObj->getContent( Revision::RAW );
				if ( !$content ) {
					$this->getResult()->addValue( 'result', 'failed', 'Unable to get contents for page "' . $wikipage . '". Please check if the page exists.' );
					return;
				}
				$wikitext = $content->getNativeData();

				preg_match_all( '/<ref ?.*>(.*)<\/ref>/', $wikitext, $matches );

				if ( count( $matches[1] ) > 0 ) {
					$footnotes = $matches[1];
					$wikitext = preg_replace_callback(
						'/<ref ?.*>(.*)<\/ref>/',
						function( $matches ) use ( &$placeholderId ) {
							return 'PLACEHOLDER-' . ++$placeholderId;
						},
						$wikitext
					);
					$content = new WikitextContent( $wikitext );
				}

				$parser_output = $content->getParserOutput( $titleObj, null, $popts );
				if ( !$parser_output ) {
					$this->getResult()->addValue( 'result', 'failed', 'Unable to parse wikitext for page "' . $wikipage . '". Please check if the wikitext is invalid.');
					return;
				}

				$page_html = $parser_output->getText();

				$dom = new DOMDocument();
				libxml_use_internal_errors(true); // see https://stackoverflow.com/a/6090728/1150075
				$dom->loadHtml('<html>' . $page_html . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
				libxml_clear_errors();

				foreach( self::$excludedTags as $tag ) {
					foreach( $dom->getElementsByTagName( $tag ) as $node ) {
						$node->parentNode->removeChild( $node );
					}
				}

				foreach( $dom->getElementsByTagName( 'figure' ) as $node ) {
					$xreflabels[] = $node->getAttribute( 'xreflabel' );
				}

				$temp_file = tempnam(sys_get_temp_dir(), 'docbook_html');
				if ( !file_put_contents( $temp_file, $dom->saveHTML() ) ) {
					$this->getResult()->addValue( 'result', 'failed', 'Unable to create or write to temporary file.' );
				}
				$cmd = $wgDocBookExportPandocPath . " ". $temp_file . " -f html -t docbook5 2>&1";
				$pandoc_output = shell_exec( $cmd );

				if ( !$pandoc_output ) {
					$this->getResult()->addValue( 'result', 'failed', 'Unable to parse contents for page "' . $wikipage . '" using Pandoc. Please check if page contains invalid tags.');
					return;
				}

				$doc = new DOMDocument();
				$doc->loadXML( '<root xmlns:xlink="http://www.w3.org/1999/xlink">' . $pandoc_output . '</root>', LIBXML_HTML_NOIMPLIED );

				foreach( $doc->getElementsByTagName( 'figure' ) as $node ) {
					$label = array_shift( $xreflabels );
					$node->setAttribute( 'xreflabel', $label );
					$node->setAttribute( 'id', $label );
					$node->appendChild( $doc->createElement( 'title', $label ) );
				}

				foreach( $doc->getElementsByTagName( 'link' ) as $node ) {
					if ( $node->hasAttribute( 'role' ) && $node->getAttribute( 'role' ) == 'xref' ) {
						$label = str_replace( '_', ' ', explode( '#', $node->getAttribute( 'xlink:href' ) )[1] );
						$xrefNode = $doc->createElement( 'xref' );
						$xrefNode->setAttribute( 'linkend', $label );
						$node->parentNode->replaceChild( $xrefNode , $node );
					}
				}

				if ( $doc->getElementsByTagName( 'root' )->length > 0 ) {
					$pandoc_output = '';
					foreach ( $doc->getElementsByTagName( 'root' )->item(0)->childNodes as $node ) {
					   $pandoc_output .= $doc->saveXML( $node );
					}
				}

				foreach( $index_terms as $index_term ) {
					$index_term = trim($index_term);
					$pandoc_output = str_replace( $index_term, $index_term . '<indexterm><primary>' . $index_term . '</primary></indexterm>', $pandoc_output );
				}

				$placeholderId = 0;
				foreach( $footnotes as $footnote ) {
					$pandoc_output = str_replace( 'PLACEHOLDER-' . ++$placeholderId, '<footnote>' . $footnote . '</footnote>', $pandoc_output );
				}
				$book_contents .= $pandoc_output;
			}
			if ( $identifier == '*' ){
				$book_contents .= '</chapter>';
			} else if ( $identifier == '**' ) {
				$book_contents .= '</section>';
			}
		}
		$book_contents .= '</book>';

		header( 'Content-type: text/xml' );
		header( 'Content-Disposition: attachment; filename="'. $options['title'] .'.xml"' );

		// do not cache the file
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// create a file pointer connected to the output stream
		$file = fopen( 'php://output', 'w' );

		fwrite( $file, $book_contents );
		fclose( $file );
		exit();
	}
}
