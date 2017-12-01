# DocBookExport
A MediaWiki extension to DocBook from wiki pages


#Installation

For installation of this extension you need to have ssh access to your server.

* To install the extension, place the entire 'DocBookExport' directory within your MediaWiki 'extensions' directory
* Just enter the following command in the 'extensions' directory: 'git clone https://bitbucket.org/wikiworksdev/docbookexport.git DocBookExport'
* Add the following line to your LocalSettings.php file: 'wfLoadExtension( 'DocBookExport' );'
* Verify you have this extension installed by visiting the Special:Version page
* Install pandoc - a dependancy for this extension. See https://pandoc.org/installing.html


#Usage
To create a book define the page structure using the docbook parser function or tag extension. See examples below.

Parser Function Example:

{{#docbook:
page structure=
* Buying clothes intro,Buying shoes=Buying the right clothes
** How I buy shoes
|title=My Guide to Bowling
}}


Tag Extension Example:


<docbook title="My Guide to Bowling">
* Buying clothes intro,Buying shoes=Buying the right clothes
** How I buy shoes
</docbook>
