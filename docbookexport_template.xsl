<?xml version='1.0'?>
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" version="1.0">

<xsl:import href="DOCBOOKXSLPLACEHOLDER"/>


<xsl:template name="header.content">
	<xsl:param name="pageclass"/>
	<xsl:param name="position" select="''"/>
    <fo:block>
		<xsl:choose><xsl:when test="$pageclass = 'body'">
			<xsl:choose><xsl:when test="$position = 'center'">
				<xsl:choose><xsl:when test="./section/title/@header">
					<xsl:value-of select="./section/title/@header"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose><xsl:when test="title/@header">
						<xsl:value-of select="title/@header"/>
					</xsl:when>
					<xsl:otherwise>
						HEADERPLACEHOLDER
					</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:when></xsl:choose>
		</xsl:when></xsl:choose>
    </fo:block>
</xsl:template>
<xsl:template name="footer.content">
	<xsl:param name="pageclass"/>
	<xsl:param name="position" select="''"/>
        <fo:block>
			<xsl:choose><xsl:when test="$pageclass = 'body'">
                <xsl:choose>
                        <xsl:when test="$position = 'center'">
							FOOTERPLACEHOLDER
                        </xsl:when>
                </xsl:choose>
			</xsl:when></xsl:choose>
        </fo:block>
</xsl:template>

</xsl:stylesheet>
