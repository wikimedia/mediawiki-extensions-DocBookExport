# DocbookExport
A MediaWiki extension to DocBook from wiki pages


To create a book define the page structure using the docbook parser function or tag extension. See examples below.

Parser Function Example:

{{#docbook:
page structure=
* Buying clothes intro=Buying the right clothes
** Buying shoes
|title=My Guide to Bowling
|toc_depth=3
}}

Tag Extension Example:


<docbook title="My Guide to Bowling" toc_depth="3">
* Buying clothes intro=Buying the right clothes
** Buying shoes
</docbook>
