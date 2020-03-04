<?php

use MediaWiki\MediaWikiServices;

class SpecialGetDocbook extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GetDocbook', 'getdocbook' );
	}

	private $embed_page = '';
	private $bookname = '';

	private $section_levels = [ "h2", "h3", "h4", "h5", "h6" ];

	function execute( $query ) {
		global $wgServer, $wgScriptPath, $wgDocbookExportPandocServerPath;

		$this->setHeaders();
		$request = $this->getRequest();
		$out = $this->getOutput();

		$this->embed_page = $request->getVal( 'embed_page' );
		$this->bookname = $request->getVal( 'bookname' );
		if ( empty( $this->embed_page ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"This page cannot be called directly"
			);
			return;
		}

		$title = Title::newFromText( $this->embed_page );
		$dbr = wfGetDB( DB_REPLICA );
		$propValue = $dbr->selectField( 'page_props', // table to use
			'pp_value', // Field to select
			array( 'pp_page' => $title->getArticleID(), 'pp_propname' => md5( "docbook_" . $this->bookname ) ), // where conditions
			__METHOD__
		);

		$options = unserialize( $propValue );
		if ( !$options ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Empty docbook structure found."
			);
			return;
		}

		$docbook_folder = escapeshellcmd( str_replace( ' ', '_', $options['title'] ) );
		if ( strpos( $docbook_folder, "(" ) !== false || strpos( $docbook_folder, ")" ) !== false ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Title cannot contain parenthesis."
			);
			return;
		}

		$out->addHTML( "<span class='mw-headline'><h3>". str_replace( "_", " ", $this->bookname ) ."</h3></span>" );

		if ( $request->getVal( 'action' ) == "check_status" ) {
			return $this->getDocbookStatus( $docbook_folder );
		}

		if ( empty( $request->getVal( 'action' ) ) ) {
			return $this->getDocbookGenerateButton( $docbook_folder );
		}

		$book_contents = '<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.1.2//EN" "http://www.oasis-open.org/docbook/xml/4.1.2/docbookx.dtd">
		<book xmlns:xlink="http://www.w3.org/1999/xlink">';

		$all_files = array();
		$index_terms = array();
		if ( array_key_exists( 'index terms', $options ) ) {
			foreach( explode( ",", $options['index terms'] ) as $index ) {
				$index_data = [];
				$index_title = Title::newFromText( $index );
				$propValue = $dbr->selectField( 'page_props', // table to use
					'pp_value', // Field to select
					array( 'pp_page' => $index_title->getArticleID(), 'pp_propname' => "docbook_index_group_by" ), // where conditions
					__METHOD__
				);
				if ( $propValue !== false ) {
					$index_data = [ 'primary' => $propValue ];
				}
				$index_terms[$index] = $index_data;
			}
		}

		$index_categories = array();
		if ( array_key_exists( 'index term categories', $options ) ) {
			$index_categories = self::smartSplit( ",", $options['index term categories'] );
		}

		foreach( $index_categories as $index_category ) {
			$parts = explode( '(', $index_category );
			$category_name = $parts[0];
			$categoryMembers = Category::newFromName( trim( $category_name ) )->getMembers();

			$line_props = array();
			if ( count( $parts ) > 1 ) {
				$line_props = explode( ')', $parts[1] )[0];
				$chunks = array_chunk(preg_split('/(=|,)/', $line_props), 2); // See https://stackoverflow.com/a/32768029/1150075
				$line_props = array_combine(array_column($chunks, 0), array_column($chunks, 1));
				$a = array_map('trim', array_keys($line_props));
				$b = array_map('trim', $line_props);
				$line_props = array_combine($a, $b);
			}
			foreach( $categoryMembers as $categoryMember ) {
				$index_title = $categoryMember;
				$index_data = [];
				$grouping_property = '';
				if ( count( $parts ) > 1 ) {
					if ( array_key_exists( 'property', $line_props ) ) {
						$page = SMWDIWikiPage::newFromTitle( $categoryMember );
						$store = \SMW\StoreFactory::getStore();
						$data = $store->getSemanticData( $page );
						$property = SMWDIProperty::newFromUserLabel( $line_props['property'] );
						$values = $data->getPropertyValues( $property );
						if ( count( $values ) > 0 ) {
							$value = array_shift( $values );
							if ( $value->getDIType() == SMWDataItem::TYPE_BLOB ) {
								$index_title = Title::newFromText( $value->getString() );
							} else if ( $value->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
								$index_title = $value->getTitle();
							}
						}
					}
					if ( array_key_exists( 'group_by', $line_props ) ) {
						$grouping_property = $line_props['group_by'];
					}
					if ( array_key_exists( 'group by', $line_props ) ) {
						$grouping_property = $line_props['group by'];
					}
					if ( !empty( $grouping_property ) ) {
						$page = SMWDIWikiPage::newFromTitle( $categoryMember );
						$store = \SMW\StoreFactory::getStore();
						$data = $store->getSemanticData( $page );
						$property = SMWDIProperty::newFromUserLabel( $grouping_property );
						$values = $data->getPropertyValues( $property );
						if ( count( $values ) > 0 ) {
							$value = array_shift( $values );
							if ( $value->getDIType() == SMWDataItem::TYPE_BLOB ) {
								$index_data = [ 'primary' => $value->getString() ];
							} else if ( $value->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
								$index_data = [ 'primary' => $value->getTitle()->getText() ];
							}
						} else {
							$grouping_property = '';
						}
					}
				}
				if ( empty( $grouping_property ) ) {
					$propValue = $dbr->selectField( 'page_props', // table to use
						'pp_value', // Field to select
						array( 'pp_page' => $categoryMember->getArticleID(), 'pp_propname' => "docbook_index_group_by" ), // where conditions
						__METHOD__
					);
					if ( $propValue !== false ) {
						$index_data = [ 'primary' => $propValue ];
					}
				}
				$index_terms[$index_title->getText()] = $index_data;
			}
		}
		$index_terms_capitalized = array();
		foreach( $index_terms as $index_term => $index_data ) {
			if ( ucfirst( $index_term ) != $index_term ) {
				$index_terms_capitalized[ucfirst( $index_term )] = $index_data;
			} else {
				$index_terms_capitalized[lcfirst( $index_term )] = $index_data;
			}
		}
		$index_terms += $index_terms_capitalized;

		$uploadDir = $this->getUploadDir();
		rrmdir( "$uploadDir/$docbook_folder" );
		mkdir( "$uploadDir/$docbook_folder" );
		mkdir( "$uploadDir/$docbook_folder/images" );

		if ( !file_put_contents( "$uploadDir/$docbook_folder/index_terms.json", json_encode( $index_terms ) ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Failed to create file index_terms.json"
			);
			return;
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/index_terms.json";
		}

		$popts = new ParserOptions( $this->getUser() );
		$popts->enableLimitReport( false );
		$popts->setIsPreview( false );
		$popts->setIsSectionPreview( false );
		$popts->setEditSection( false );
		$popts->setTidy( true );

		$book_contents .= '<info><title>' . $options['title'] . '</title>';

		if ( array_key_exists( 'cover page', $options ) ) {

			$titleObj = Title::newFromText( $options['cover page'] );
			if ( $titleObj == null || !$titleObj->exists() ) {
				$out->wrapWikiMsg(
					"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
					"Could not find page: " . $options['cover page']
				);
				return;
			}
			$cover_html = $this->getHTMLFromWikiPage( $options['cover page'], $all_files, $popts );
			if ( $options['timestamp'] == '1' ) {
				$cover_html .= '<div style="margin-top:10px;clear:both;text-align: right;">Produced from '. $wgServer .' on '. date("Y-m-d H:i:s") .'</div>';
			}
			$book_contents .= '<cover>' . $cover_html . '</cover>';

			$orientation = 'portrait';
			$size = 'LETTER';
			if ( array_key_exists( 'orientation', $options ) ) {
				$orientation = $options['orientation'];
			}
			if ( array_key_exists( 'size', $options ) ) {
				$size = $options['size'];
			}

			if ( !class_exists( '\Mpdf\Mpdf' ) ) {
				$out->wrapWikiMsg(
					"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
					"Mpdf missing. Install using composer: composer require mpdf/mpdf"
				);
				return;
			}
			$mpdf = new \Mpdf\Mpdf( ['format' => $size, 'orientation' => $orientation] );
			$mpdf->WriteHTML( $cover_html );
			$mpdf->Output( "$uploadDir/$docbook_folder/cover.pdf", 'F' );
			$all_files[] = "$uploadDir/$docbook_folder/cover.pdf";
		}

		$book_contents .= '</info>';

		$xsl_contents = file_get_contents( __DIR__ . '/docbookexport_template.xsl' );
		if ( array_key_exists( 'header', $options ) ) {
			$xsl_contents = str_replace( 'HEADERPLACEHOLDER', $options['header'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'HEADERPLACEHOLDER', "", $xsl_contents );
		}
		if ( array_key_exists( 'footer', $options ) ) {
			$xsl_contents = str_replace( 'FOOTERPLACEHOLDER', $options['footer'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'FOOTERPLACEHOLDER', "", $xsl_contents );
		}
		if ( array_key_exists( 'orientation', $options ) ) {
			$xsl_contents = str_replace( 'ORIENTATIONPLACEHOLDER', $options['orientation'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'ORIENTATIONPLACEHOLDER', "portrait", $xsl_contents );
		}
		if ( array_key_exists( 'size', $options ) ) {
			$xsl_contents = str_replace( 'SIZEPLACEHOLDER', $options['size'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'SIZEPLACEHOLDER', "USletter", $xsl_contents );
		}
		if ( array_key_exists( 'columns', $options ) ) {
			$xsl_contents = str_replace( 'COLUMNSPLACEHOLDER', $options['columns'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'COLUMNSPLACEHOLDER', "1", $xsl_contents );
		}
		if ( array_key_exists( 'margin-inner', $options ) ) {
			$xsl_contents = str_replace( 'MARINNERPLACEHOLDER', $options['margin-inner'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'MARINNERPLACEHOLDER', "0.5in", $xsl_contents );
		}
		if ( array_key_exists( 'margin-outer', $options ) ) {
			$xsl_contents = str_replace( 'MAROUTERPLACEHOLDER', $options['margin-outer'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'MAROUTERPLACEHOLDER', "0.5in", $xsl_contents );
		}
		if ( array_key_exists( 'margin-top', $options ) ) {
			$xsl_contents = str_replace( 'MARTOPPLACEHOLDER', $options['margin-top'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'MARTOPPLACEHOLDER', "0.5in", $xsl_contents );
		}
		if ( array_key_exists( 'margin-bottom', $options ) ) {
			$xsl_contents = str_replace( 'MARBOTPLACEHOLDER', $options['margin-bottom'], $xsl_contents );
		} else {
			$xsl_contents = str_replace( 'MARBOTPLACEHOLDER', "0.5in", $xsl_contents );
		}

		if ( !file_put_contents( "$uploadDir/$docbook_folder/docbookexport.xsl", $xsl_contents ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Failed to create file docbookexport.xsl"
			);
			return;
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/docbookexport.xsl";
		}

		$pagenumberprefixes = file_get_contents( __DIR__ . '/pagenumberprefixes.xsl' );
		if ( !file_put_contents( "$uploadDir/$docbook_folder/pagenumberprefixes.xsl", $pagenumberprefixes ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Failed to create file pagenumberprefixes.xsl"
			);
			return;
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/pagenumberprefixes.xsl";
		}

		$xsltproc_args = "";
		if ( array_key_exists( 'font', $options ) ) {
			$xsltproc_args .= " --stringparam  body.font.family " . $options['font'] . " ";
		}
		if ( array_key_exists( 'font size', $options ) ) {
			$xsltproc_args .= " --stringparam  body.font.master " . $options['font size'] . " ";
		}

		if ( !empty( $xsltproc_args ) ) {
			if ( !file_put_contents( "$uploadDir/$docbook_folder/xsltproc_args.txt", $xsltproc_args ) ) {
				$out->wrapWikiMsg(
					"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
					"Failed to create file xsltproc_args.txt"
				);
				return;
			} else {
				$all_files[] = "$uploadDir/$docbook_folder/xsltproc_args.txt";
			}
		}

		$css_contents = file_get_contents( __DIR__ . '/docbookexport_styles.css' );
		if ( !file_put_contents( "$uploadDir/$docbook_folder/docbookexport_styles.css", $css_contents ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Failed to create file docbookexport_styles.css"
			);
			return;
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/docbookexport_styles.css";
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
			$wiki_pages = explode( ',', $parts[0] );
			$display_pagename = $wiki_pages[0];

			if ( count( $parts ) == 2 ) {
				$line_props = explode( ')', $parts[1] )[0];
				$chunks = array_chunk(preg_split('/(=|,)/', $line_props), 2); // See https://stackoverflow.com/a/32768029/1150075
				$line_props = array_combine(array_column($chunks, 0), array_column($chunks, 1));
				if ( array_key_exists( 'title', $line_props ) ) {
					$display_pagename = $line_props['title'];
				}
				if ( array_key_exists( 'header', $line_props ) ) {
					$custom_header = ' header="' . $line_props['header']. '"';
				}
			}

			$book_contents .= "<title$custom_header>$display_pagename</title>";
			foreach( $wiki_pages as $wikipage ) {
				$titleObj = Title::newFromText( $wikipage );
				if ( $titleObj == null || !$titleObj->exists() ) {
					$out->wrapWikiMsg(
						"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
						"Could not find page: " . $wikipage
					);
					return;
				}
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

		$status = $this->checkForErrors( $book_contents );
		if ( !$status->isGood() ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				$status->getMessage()
			);
			return;
		}

		if ( !file_put_contents( "$uploadDir/$docbook_folder/$docbook_folder.pandochtml", $book_contents ) ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Failed to create file $docbook_folder.pandochtml"
			);
			return;
		} else {
			$all_files[] = "$uploadDir/$docbook_folder/$docbook_folder.pandochtml";
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
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Error: " . curl_error($request)
			);
			return;
		}

		$httpcode = curl_getinfo($request, CURLINFO_HTTP_CODE);
		curl_close($request);

		$result = json_decode( $result, true );
		$this->processResponse( $httpcode, $result );
	}

	protected function checkForErrors( $book_contents ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHtml( $book_contents, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$anchor_ids = [];
		foreach( $dom->getElementsByTagName( 'span' ) as $span ) {
			if ( $span->hasAttribute( 'id' ) ) {
				$span_id = $span->getAttribute( 'id' );
				if ( in_array( $span_id, $anchor_ids ) ) {
					return Status::newFatal( "ID $span_id already defined." );
				} else {
					$anchor_ids[] = $span_id;
				}
			}
		}
		return Status::newGood();
	}

	public function getDocbookGenerateButton( $docbook_folder ) {
		$this->setHeaders();
		$request = $this->getRequest();
		$out = $this->getOutput();

		$check_status_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Check Status", [], [ 'embed_page' => $this->embed_page, 'bookname' => $this->bookname, 'action' => 'check_status' ] );

		$out->addHTML( "<p>$check_status_link</p>" );

		$create_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Re-generate Docbook", ['id' => "create_docbook"], [ 'embed_page' => $this->embed_page, 'bookname' => $this->bookname, 'action' => 'create' ] );
		$out->addHTML( "<p>$create_link (Requires upload to Docbook server. This may take a while)</p>" );
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
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				"Error: " . curl_error($request)
			);
			return;
		}
		$httpcode = curl_getinfo($request, CURLINFO_HTTP_CODE);
		curl_close($request);

		$result = json_decode( $result, true );
		$this->processResponse( $httpcode, $result );
	}

	public function processResponse( $httpcode, $result ) {
		global $wgDocbookDownloadServerPath;

		$out = $this->getOutput();
		if ( $result['result'] == 'success' ) {
			if ( !empty( $result['status'] ) ) {
				$out->addHTML( "<p>Status: ". $result['status'] ."</p>" );
				$check_status_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Refresh Status", [], [ 'embed_page' => $this->embed_page, 'bookname' => $this->bookname, 'action' => 'check_status' ] );
				$out->addHTML( "<p>$check_status_link</p>" );
				if ( $result['status'] == "Docbook generated" ) {
					$out->addHTML( '<a href="'. $wgDocbookDownloadServerPath . $result['docbook_zip'] .'">Download XML</a><br>' );
					$out->addHTML( '<a href="'. $wgDocbookDownloadServerPath . $result['docbook_html'] .'">Download HTML</a><br>' );
					$out->addHTML( '<a href="'. $wgDocbookDownloadServerPath . $result['docbook_pdf'] .'">Download PDF</a><br>' );
					$out->addHTML( '<a href="'. $wgDocbookDownloadServerPath . $result['docbook_odf'] .'">Download ODF</a><br>' );
				}
			} else {
				$check_status_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Check Status", [], [ 'embed_page' => $this->embed_page, 'bookname' => $this->bookname, 'action' => 'check_status' ] );
				$out->addHTML( "<p>Successfully Sent Request. $check_status_link</p>" );
			}
		} else if ( $result['result'] == 'failed' ) {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
				$result['error']
			);
		} else {
			if ( $httpcode == 200 ) {
				$out->wrapWikiMsg(
					"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
					"Docbook was never generated!"
				);
			} else {
				$out->wrapWikiMsg(
					"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
					"Unknown Error. Response data:" . json_encode( $result ) . " Response Code: " . $httpcode
				);
			}
		}
	}

	public function getHTMLFromWikiPage( $wikipage, &$all_files, $popts ) {
		global $wgServer;

		$placeholderId = 0;
		$footnotes = array();

		$titleObj = Title::newFromText( $wikipage );
		$pageObj = new WikiPage( $titleObj );

		$content = $pageObj->getContent( Revision::RAW );
		if ( !$content ) {
			return '';
		}
		$wikitext = $content->getNativeData() . "\n" . '__NOTOC__ __NOEDITSECTION__';

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
		}
		$content = new WikitextContent( $wikitext );

		$parser_output = $content->getParserOutput( $titleObj, null, $popts );
		if ( !$parser_output ) {
			return '';
		}

		$page_html = $parser_output->getText();

		$page_html = $this->recursiveFindSections( $wikipage, $page_html, 0 );

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHtml( '<html>' . $page_html . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		foreach( $dom->getElementsByTagName( 'table' ) as $table ) {
			$classes = $table->getAttribute( 'class' );
			$classes = explode( " ", $classes );
			foreach( $classes as $class ) {
				if ( strpos( $class, "colwidth" ) !== FALSE ) {
					$colwidths = explode( "-", $class );
					array_shift( $colwidths );
					if ( $table->getElementsByTagName( "caption" )->length > 0 ) {
						$table->insertBefore( $dom->createElement( "colgroup" ), $table->getElementsByTagName( "caption" )->item(0)->nextSibling );
					} else {
						$table->insertBefore( $dom->createElement( "colgroup" ), $table->firstChild );
					}
					foreach( $colwidths as $colwidth ) {
						$col = $dom->createElement( "col" );
						$col->setAttribute( "width", $colwidth . "%" );
						$table->getElementsByTagName( 'colgroup' )->item(0)->appendChild( $col );
					}
				}
			}
		}

		// Handle SMW Bug https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4232
		foreach( $dom->getElementsByTagName( 'table' ) as $table ) {
			if ( $table->getElementsByTagName( 'th' )->item(0)->parentNode->nodeName != 'tr' ) {
				$table->getElementsByTagName( 'th' )->item(0)->parentNode->insertBefore( $dom->createElement( "tr" ), $table->getElementsByTagName( 'th' )->item(0) );
				$th_nodes = [];
				while ($th_node = $table->getElementsByTagName( "th" )->item(0) ) {
					$th_nodes[] = $th_node->parentNode->removeChild( $th_node );
				}
				foreach( $th_nodes as $th_node ) {
					$table->getElementsByTagName( 'tr' )->item( 0 )->appendChild( $th_node );
				}
			}
		}

		foreach( $dom->getElementsByTagName( 'a' ) as $node ) {
			$url = $node->getAttribute( 'href' );
			if ( $url[0] == '/' ) {
				$node->setAttribute( 'href', $wgServer . $url );
			}
		}

		foreach( $dom->getElementsByTagName( 'img' ) as $node ) {
			$file_url = $node->getAttribute( 'src' );
			$error = false;
			if ( wfFindFile( basename( $file_url ) ) ) {
				$file_path = wfFindFile( basename( $file_url ) )->getLocalRefPath();
			} else {
				if ( strpos( $file_url, "thumb" ) !== FALSE ) {
					$parts = explode( "px-", basename( $file_url ) );
					$width = array_shift( $parts );
					$file_name = array_shift( $parts );
					$file = wfFindFile( $file_name );
					if ( !$file ) {
						if ( substr_count( $file_name, "." ) > 1 ) { // In case of filename.svg files the thumbnail file can be filename.svg.png
							$file_name = substr( $file_name, 0, strrpos( $file_name, '.' ) );
							$file = wfFindFile( $file_name );
						}
					}
					if ( $file ) {
						$file_path = $file->transform( [ 'width' => $width, 'height' => $height ] )->getLocalCopyPath();
					} else {
						$error = true;
					}
				} else {
					$error = true;
				}
			}
			if ( $file_path ) {
				$all_files[] = $file_path;
			}
			if ( $error ) {
				$this->getOutput()->wrapWikiMsg(
					"<div class=\"errorbox\">\nError: $1\n</div><br clear=\"both\" />",
					"File $file_url not found"
				);
			}
			$node->setAttribute( 'src', basename( $file_url ) );
		}

		$html = utf8_decode($dom->saveHTML($dom->documentElement));

		$html = str_replace( "</html>", "", $html );
		$html = str_replace( "<html>", "", $html );
		$html = cleanse( $html );

		return $html;
	}

	protected function recursiveFindSections( $wikipage, $page_html, $section_level ) {
		$section_header = $this->section_levels[$section_level];
		$no_sections = true;
		$offset = 0;
		$new_page_html = '';
		$open_section = false;

		while( ( $pos = strpos( $page_html, "<$section_header>", $offset ) ) !== FALSE ) {
			$no_sections = false;
			if ( $pos != 0 ) {
				if ( $section_level == ( count( $this->section_levels ) -1 ) ) {
					$temp_html = '<html_pandoc>' . substr( $page_html, $offset, $pos - $offset ) . '</html_pandoc>';
					$temp_html = makeProperHtml( $temp_html );
					$new_page_html .= $temp_html;
				} else {
					$new_page_html .= $this->recursiveFindSections( $wikipage, substr( $page_html, $offset, $pos - $offset ), $section_level+1 );
				}
			}
			if ( $open_section ) {
				$new_page_html .= '</section>';
				$open_section = false;
			}
			$pos += 4;
			$offset = strpos( $page_html, "</$section_header>", $pos );
			$title_html = substr( $page_html, $pos, $offset - $pos );
			$title_html = str_replace( 'id="', 'id="' . str_replace( " ", "_", $wikipage ) . '_', $title_html );
			$new_page_html .= '<section><title><html_pandoc>' . $title_html . '</html_pandoc></title>';
			$offset += 5;
			$open_section = true;
		}
		if ( $open_section ) {
			if ( $section_level == ( count( $this->section_levels ) -1 ) ) {
				$temp_html = '<html_pandoc>' . substr( $page_html, $offset, strlen( $page_html ) - $offset ) . '</html_pandoc>';
				$temp_html = makeProperHtml( $temp_html );
				$new_page_html .= $temp_html;
			} else {
				$new_page_html .= $this->recursiveFindSections( $wikipage, substr( $page_html, $offset, strlen( $page_html ) - $offset ), $section_level + 1 );
			}
			$new_page_html .= '</section>';
			$open_section = false;
			$page_html = $new_page_html;
		}
		if ( $no_sections ) {
			if ( $section_level == ( count( $this->section_levels ) -1 ) ) {
				$page_html = makeProperHtml( '<html_pandoc>' . $page_html . '</html_pandoc>' );
			} else {
				$page_html = $this->recursiveFindSections( $wikipage, $page_html, $section_level + 1 );
			}
		}
		return $page_html;
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

	static function smartSplit( $delimiter, $string, $includeBlankValues = false ) {
		if ( $string == '' ) {
			return array();
		}

		$ignoreNextChar = false;
		$returnValues = array();
		$numOpenParentheses = 0;
		$curReturnValue = '';

		for ( $i = 0; $i < strlen( $string ); $i++ ) {
			$curChar = $string{$i};

			if ( $ignoreNextChar ) {
				// If previous character was a backslash,
				// ignore the current one, since it's escaped.
				// What if this one is a backslash too?
				// Doesn't matter - it's escaped.
				$ignoreNextChar = false;
			} elseif ( $curChar == '(' ) {
				$numOpenParentheses++;
			} elseif ( $curChar == ')' ) {
				$numOpenParentheses--;
			} elseif ( $curChar == '\'' || $curChar == '"' ) {
				$pos = self::findQuotedStringEnd( $string, $curChar, $i + 1 );
				if ( $pos === false ) {
					throw new MWException( "Error: unmatched quote in SQL string constant." );
				}
				$curReturnValue .= substr( $string, $i, $pos - $i );
				$i = $pos;
			} elseif ( $curChar == '\\' ) {
				$ignoreNextChar = true;
			}

			if ( $curChar == $delimiter && $numOpenParentheses == 0 ) {
				$returnValues[] = trim( $curReturnValue );
				$curReturnValue = '';
			} else {
				$curReturnValue .= $curChar;
			}
		}
		$returnValues[] = trim( $curReturnValue );

		if ( $ignoreNextChar ) {
			throw new MWException( "Error: incomplete escape sequence." );
		}

		if ( $includeBlankValues ) {
			return $returnValues;
		}

		// Remove empty strings (but not other quasi-empty values, like '0') and re-key the array.
		$noEmptyStrings = function ( $s ) {
			return $s !== '';
		};
		return array_values( array_filter( $returnValues, $noEmptyStrings ) );
	}
}

function makeProperHtml( $improperHtml ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dom->loadHtml( $improperHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_clear_errors();
	return $dom->saveHTML();
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

function cleanse($string, $allowedTags = array()) {
	if (get_magic_quotes_gpc()) {
		$string = stripslashes($stringIn);
	}
	
	// ============
	// Remove MS Word Special Characters
	// ============
	
	$search  = array('&acirc;€“','&acirc;€œ','&acirc;€˜','&acirc;€™','&Acirc;&pound;','&Acirc;&not;','&acirc;„&cent;');
	$replace = array('-','&ldquo;','&lsquo;','&rsquo;','&pound;','&not;','&#8482;');
	
	$string = str_replace($search, $replace, $string);
	$string = str_replace('&acirc;€', '&rdquo;', $string);

	$search = array("&#39;", "\xc3\xa2\xc2\x80\xc2\x99", "\xc3\xa2\xc2\x80\xc2\x93", "\xc3\xa2\xc2\x80\xc2\x9d", "\xc3\xa2\x3f\x3f");
	$resplace = array("'", "'", ' - ', '"', "'");
	
	$string = str_replace($search, $replace, $string);

	$quotes = array(
		"\xC2\xAB"     => '"',
		"\xC2\xBB"     => '"',
		"\xE2\x80\x98" => "'",
		"\xE2\x80\x99" => "'",
		"\xE2\x80\x9A" => "'",
		"\xE2\x80\x9B" => "'",
		"\xE2\x80\x9C" => '"',
		"\xE2\x80\x9D" => '"',
		"\xE2\x80\x9E" => '"',
		"\xE2\x80\x9F" => '"',
		"\xE2\x80\xB9" => "'",
		"\xE2\x80\xBA" => "'",
		"\xe2\x80\x93" => "-",
		"\xc2\xb0"	   => "°",
		"\xc2\xba"     => "°",
		"\xc3\xb1"	   => "&#241;",
		"\x96"		   => "&#241;",
		"\xe2\x81\x83" => '&bull;'
	);
	$string = strtr($string, $quotes);
	/*
	// Use the below to get the byte of the special char and put it in the array above + the replacement.
	
	if (strpos($string, "Live Wave Buoy Data") !== false)
	{
		for ($i=strpos($string, "Live Wave Buoy Data") ; $i<strlen($string) ; $i++) {
			$byte = $string[$i];
			$char = ord($byte);
			printf('%s:0x%02x ', $byte, $char);
		}
	}
	var_dump($string);
	exit;
	*/
	// ============
	// END
	// ============
	
	return $string;
}
