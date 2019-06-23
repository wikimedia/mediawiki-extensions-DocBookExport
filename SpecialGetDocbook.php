<?php

use MediaWiki\MediaWikiServices;

class SpecialGetDocbook extends SpecialPage {

	static $excludedTags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function __construct() {
		parent::__construct( 'GetDocbook', 'getdocbook' );
	}

	private $bookName = '';

	function execute( $query ) {
		global $wgServer, $wgScriptPath, $wgDocbookExportPandocServerPath;

		$this->setHeaders();
		$request = $this->getRequest();
		$out = $this->getOutput();

		$this->bookName = $request->getVal( 'bookname' );
		if ( empty( $this->bookName ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\n$1\n</div><br clear=\"both\" />",
				"This page cannot be called directly"
			);
			return;
		}

		$title = Title::newFromText( $this->bookName );
		$dbr = wfGetDB( DB_SLAVE );
		$propValue = $dbr->selectField( 'page_props', // table to use
			'pp_value', // Field to select
			array( 'pp_page' => $title->getArticleID(), 'pp_propname' => "docbook" ), // where conditions
			__METHOD__
		);

		$options = unserialize( $propValue );
		if ( !$options ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\n$1\n</div><br clear=\"both\" />",
				"Empty docbook structure found."
			);
			return;
		}
		$docbook_folder = escapeshellcmd( str_replace( ' ', '_', $options['title'] ) );
		if ( $request->getVal( 'action' ) == "check_status" ) {
			return $this->getDocbookStatus( $docbook_folder );
		}


		$book_title = '';
		$book_contents = '<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.1.2//EN" "http://www.oasis-open.org/docbook/xml/4.1.2/docbookx.dtd">
		<book xmlns:xlink="http://www.w3.org/1999/xlink">';

		$all_files = array();
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

		$index_terms_capitalized = array();
		foreach( $index_terms as $index_term ) {
			if ( ucfirst( $index_term ) != $index_term ) {
				$index_terms_capitalized[] = ucfirst( $index_term );
			} else {
				$index_terms_capitalized[] = lcfirst( $index_term );
			}
		}
		$index_terms = array_merge( $index_terms, $index_terms_capitalized );

		$popts = new ParserOptions();
		$popts->enableLimitReport( false );
		$popts->setIsPreview( false );
		$popts->setIsSectionPreview( false );
		$popts->setEditSection( false );
		$popts->setTidy( true );

		if ( array_key_exists( 'cover page', $options ) ) {
			$book_contents .= '<info><cover>' . $this->getHTMLFromWikiPage( $options['cover page'], $all_files, $popts ) . '</cover></info>';
		}

		$book_contents .= '<title>' . $options['title'] . '</title>';

		$uploadDir = $this->getUploadDir();
		rrmdir( "$uploadDir/$docbook_folder" );
		mkdir( "$uploadDir/$docbook_folder" );
		mkdir( "$uploadDir/$docbook_folder/images" );

		$xsl_contents = file_get_contents( __DIR__ . '/docbookexport_template.xsl' );
		if ( array_key_exists( 'header', $options ) ) {
			$xsl_contents = str_replace( 'HEADERPLACEHOLDER', $options['header'], $xsl_contents );
		}
		if ( array_key_exists( 'footer', $options ) ) {
			$xsl_contents = str_replace( 'FOOTERPLACEHOLDER', $options['footer'], $xsl_contents );
		}

		if ( !file_put_contents( "$uploadDir/$docbook_folder/docbookexport.xsl", $xsl_contents ) ) {
			return '';
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/docbookexport.xsl";
		}

		$close_tags = array();
		$deep_level = 0;
		$page_structure = explode( "\n", $options['page structure'] );
		$content_started = false;
		foreach( $page_structure as $current_line ) {
			$display_pagename = '';
			$custom_header = '';
			$parts = explode( ' ', $current_line, 2 );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$identifier = $parts[0];
			$after_identifier = $parts[1];

			if ( $identifier == '*' ) {
			$content_started = true;
				$this_level = $deep_level;
				while( $this_level >= 0 ) {
					if ( array_key_exists( $this_level, $close_tags ) ) {
						$book_contents .= $close_tags[$this_level];
						$close_tags[$this_level] = '';
					}
					$this_level--;
				}
				$book_contents .= '<chapter>';
				if ( !array_key_exists( 0, $close_tags ) ) {
					$close_tags[0] = '';
				}
				$close_tags[0] .= '</chapter>';
			} else if ( $identifier[0] == '*' ) {
				$indent_level = strlen( $identifier ) - 1;
				$deep_level = max( $deep_level, $indent_level );
				$this_level = $deep_level;
				while( $this_level >= $indent_level ) {
					if ( array_key_exists( $this_level, $close_tags ) ) {
						$book_contents .= $close_tags[$this_level];
						$close_tags[$this_level] = '';
					}
					$this_level--;
				}
				$book_contents .= '<section>';
				if ( !array_key_exists( $indent_level, $close_tags ) ) {
					$close_tags[$indent_level] = '';
				}
				$close_tags[$indent_level] .= '</section>';
			} else if ( $identifier == '?' ) {
				$this_level = $deep_level;
				while($this_level >= 0) {
					if (array_key_exists($this_level, $close_tags)) {
						$book_contents .= $close_tags[$this_level];
						$close_tags[$this_level] = '';
					}
					$this_level--;
				}
				if ( !$content_started ) {
					$book_contents .= '<preface>';
				} else {
					$book_contents .= '<appendix>';
				}
				if ( !array_key_exists( 0, $close_tags ) ) {
					$close_tags[0] = '';
				}
				if ( !$content_started ) {
					$close_tags[0] .= '</preface>';
				} else {
					$close_tags[0] .= '</appendix>';
				}
			} else {
				$this->getResult()->addValue( 'result', 'failed', "Unsupported identifier: $identifier used in page structure" );
			}

			$parts = explode( '(', $after_identifier );

			if ( count( $parts ) == 2 ) {
				$wiki_pages = explode( ',', $parts[0] );
				$line_props = explode( ')', $parts[1] )[0];
				$chunks = array_chunk(preg_split('/(=|,)/', $line_props), 2); // See https://stackoverflow.com/a/32768029/1150075
				$line_props = array_combine(array_column($chunks, 0), array_column($chunks, 1));
				if ( array_key_exists( 'title', $line_props ) ) {
					$display_pagename = $line_props['title'];
				}
				if ( array_key_exists( 'header', $line_props ) ) {
					$custom_header = ' header="' . $line_props['header']. '"';
				}
			} else {
				$wiki_pages = explode( ',', $parts[0] );
				$display_pagename = $wiki_pages[0];
			}

			$book_contents .= "<title$custom_header>$display_pagename</title>";
			foreach( $wiki_pages as $wikipage ) {
				$book_contents .= $this->getHTMLFromWikiPage( $wikipage, $all_files, $popts );
			}
		}
		$this_level = $deep_level;
		while($this_level >= 0) {
			if (array_key_exists($this_level, $close_tags)) {
				$book_contents .= $close_tags[$this_level];
				$close_tags[$this_level] = '';
			}
			$this_level--;
		}
		$book_contents .= '<index/></book>';

		if ( !file_put_contents( "$uploadDir/$docbook_folder/$docbook_folder.html", $book_contents ) ) {
			return '';
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/$docbook_folder.html";
		}


		// Set postdata array
		$postData = [
			'request_type' => 'getDocbook',
			'docbook_name' => $docbook_folder,
		];

		// Create array of files to post
		foreach ($all_files as $index => $file) {
			$postData['files[' . $index . ']'] = curl_file_create(
				realpath($file),
				mime_content_type($file),
				basename($file)
			);
		}
		$request = curl_init( $wgDocbookExportPandocServerPath . '/wikiHtmlToDocbookConvertor.php' );
		curl_setopt($request, CURLOPT_POST, true);
		curl_setopt($request, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($request);

		if ($result === false) {
			error_log(curl_error($request));
		}

		curl_close($request);

		$result = json_decode( $result, true );
		$this->processResponse( $result );
	}

	public function getDocbookStatus( $docbook_folder ) {
		global $wgDocbookExportPandocServerPath;

		$this->setHeaders();
		$request = $this->getRequest();
		$out = $this->getOutput();

		// Set postdata array
		$postData = [
			'request_type' => 'getDocbookStatus',
			'docbook_name' => $docbook_folder
		];

		$request = curl_init( $wgDocbookExportPandocServerPath . '/wikiHtmlToDocbookConvertor.php' );
		curl_setopt($request, CURLOPT_POST, true);
		curl_setopt($request, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($request);

		if ($result === false) {
			error_log(curl_error($request));
		}

		curl_close($request);
		$result = json_decode( $result, true );
		$this->processResponse( $result );
	}

	public function processResponse( $result ) {
		global $wgDocbookExportPandocServerPath;

		$out = $this->getOutput();
		if ( $result['result'] == 'success' ) {
			if ( !empty( $result['status'] ) ) {
				$out->addHTML( "<p>Status: ". $result['status'] ."</p>" );
				$check_status_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Check Status", [], [ 'bookname' => $this->bookName, 'action' => 'check_status' ] );
				$out->addHTML( "<p>$check_status_link</p>" );
				if ( $result['status'] == "Docbook generated" ) {
					$out->addHTML( '<a href="'. $wgDocbookExportPandocServerPath . $result['docbook_zip'] .'">Download Zip</a><br>' );
					$out->addHTML( '<a href="'. $wgDocbookExportPandocServerPath . $result['docbook_pdf'] .'">Download PDF</a><br>' );
				}
			} else {
				$check_status_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Check Status", [], [ 'bookname' => $this->bookName, 'action' => 'check_status' ] );
				$out->addHTML( "<p>Successfully Sent Request. $check_status_link</p>" );
			}
		} else if ( $result['result'] == 'failed' ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\n$1\n</div><br clear=\"both\" />",
				$result['error']
			);
		} else {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\n$1\n</div><br clear=\"both\" />",
				"Unknown Error"
			);
		}
	}
	public function getHTMLFromWikiPage( $wikipage, &$all_files, $popts ) {
		$placeholderId = 0;
		$footnotes = array();

		$titleObj = Title::newFromText( $wikipage );
		$pageObj = new WikiPage( $titleObj );

		$content = $pageObj->getContent( Revision::RAW );
		if ( !$content ) {
			return '';
		}
		$wikitext = $content->getNativeData();

		preg_match_all( '/<ref ?.*>(.*)<\/ref>/', $wikitext, $matches );

		if ( count( $matches[1] ) > 0 ) {
			$footnotes = $matches[1];
			$wikitext = preg_replace_callback(
				'/<ref ?.*>(.*)<\/ref>/',
				function( $matches ) use ( &$placeholderId ) {
					return '{{#footnote:para='. $matches[1] .'}}';
				},
				$wikitext
			);
			$content = new WikitextContent( $wikitext );
		}

		$parser_output = $content->getParserOutput( $titleObj, null, $popts );
		if ( !$parser_output ) {
			return '';
		}

		$page_html = $parser_output->getText();

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHtml( '<html_pandoc>' . $page_html . '</html_pandoc>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		foreach( self::$excludedTags as $tag ) {
			foreach( $dom->getElementsByTagName( $tag ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		foreach( $dom->getElementsByTagName( 'img' ) as $node ) {
			$file_url = $node->getAttribute( 'src' );
			if ( wfFindFile( basename( $file_url ) ) ) {
				$all_files[] = wfFindFile( basename( $file_url ) )->getLocalRefPath();
			} else {
				$this->getOutput()->wrapWikiMsg(
					"<div class=\"errorbox\">\n$1\n</div><br clear=\"both\" />",
					"File $file_url not found"
				);
			}
			$node->setAttribute( 'src', basename( $file_url ) );
		}

		return $dom->saveHTML();
	}

	/**
	 * Get DocBookExport's custom upload directory (within $wgUploadDirectory).
	 * @return string Full filesystem path with no trailing slash.
	 */
	protected function getUploadDir() {
		$uploadDirectory = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'UploadDirectory' );
		$uploadDir = $uploadDirectory . '/DocBookExport';
		if ( !is_dir( $uploadDir ) ) {
			mkdir( $uploadDir );
		}
		$uploadDirFull = rtrim( realpath( $uploadDir ), DIRECTORY_SEPARATOR );
		if ( !is_dir( $uploadDirFull ) ) {
			throw new Exception( "Unable to create directory: $uploadDir" );
		}
		return $uploadDirFull;
	}
}

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object))
           rrmdir($dir."/".$object);
         else
           unlink($dir."/".$object); 
       } 
     }
     rmdir($dir);
   } 
}
