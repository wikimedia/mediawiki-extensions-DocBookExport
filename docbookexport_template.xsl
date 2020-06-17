<?xml version='1.0'?>
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0">

<xsl:import href="DOCBOOKXSLPLACEHOLDER"/>

<xsl:import href="pagenumberprefixes.xsl"/>

<xsl:param name="ulink.show" select="0"></xsl:param>
<xsl:param name="double.sided" select="1" />
<xsl:param name="page.orientation">ORIENTATIONPLACEHOLDER</xsl:param>
<xsl:param name="paper.type">SIZEPLACEHOLDER</xsl:param>
<xsl:param name="column.count.body" select="COLUMNSPLACEHOLDER"></xsl:param>

<xsl:param name="section.autolabel.max.depth">SECTION_AUTOLABEL_MAX_DEPTH_PLACEHOLDER</xsl:param>

<xsl:param name="page.margin.bottom">MARBOTPLACEHOLDER</xsl:param>
<xsl:param name="page.margin.top">MARTOPPLACEHOLDER</xsl:param>
<xsl:param name="page.margin.inner">MARINNERPLACEHOLDER</xsl:param>
<xsl:param name="page.margin.outer">MAROUTERPLACEHOLDER</xsl:param>

<xsl:template name="header.content">
	<xsl:param name="pageclass" select="''"/>
	<xsl:param name="position" select="''"/>
	<xsl:param name="sequence" select="''"/>

    <fo:block>
		<xsl:choose>
			<xsl:when test="$sequence != 'blank' and $pageclass = 'body'">
				<xsl:choose>
					<xsl:when test="$position = 'center'">
						<xsl:choose>
							<xsl:when test="./section/title/@header">
								<xsl:value-of select="./section/title/@header"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="title/@header">
										<xsl:value-of select="title/@header"/>
									</xsl:when>
									<xsl:otherwise>
										HEADERPLACEHOLDER
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
				</xsl:choose>
			</xsl:when>

			<xsl:when test="$sequence = 'blank' and $position = 'center'">
				<xsl:text>This page intentionally left blank</xsl:text>
			</xsl:when>
	  </xsl:choose>
	</fo:block>
</xsl:template>

<xsl:template name="footer.content">
	<xsl:param name="pageclass"/>
	<xsl:param name="position" select="''"/>
        <fo:block>
			<xsl:choose>
				<xsl:when test="$pageclass = 'body'">
					<xsl:choose>
                        <xsl:when test="$position = 'center'">
							<xsl:apply-templates mode="page-number-prefix" select="."/>
							<fo:page-number/>
							<fo:block>FOOTERPLACEHOLDER</fo:block>
                        </xsl:when>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$position = 'center'">
							<xsl:apply-templates mode="page-number-prefix" select="."/>
							<fo:page-number/>
                        </xsl:when>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
        </fo:block>
</xsl:template>

<xsl:attribute-set name="xref.properties">
	<xsl:attribute name="color">
		<xsl:choose>
			<xsl:when test="self::link and @xlink:href">EXTERNAL_LINK_COLOR</xsl:when>
			<xsl:otherwise>black</xsl:otherwise>
		</xsl:choose>
	</xsl:attribute>
</xsl:attribute-set>
</xsl:stylesheet>
