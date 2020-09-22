<xsl:template name="header.content">
	<xsl:param name="pageclass" select="''"/>
	<xsl:param name="position" select="''"/>
	<xsl:param name="sequence" select="''"/>

    <fo:block>
		<xsl:choose>
			<xsl:when test="$sequence = 'blank' and $position = 'center'">
				<xsl:text>This page intentionally left blank</xsl:text>
			</xsl:when>
			<xsl:when test="$pageclass='titlepage' or $sequence = 'odd' or $sequence = 'first'">
				<xsl:choose>
					<xsl:when test="$position = 'right'">
						<xsl:choose>
							<xsl:when test="./section/title/@header_right">
								<xsl:value-of select="./section/title/@header_right"/>
							</xsl:when>
							<xsl:when test="./chapter/title/@header_right">
								<xsl:value-of select="./chapter/title/@header_right"/>
							</xsl:when>
							<xsl:otherwise>
								HEADERRIGHTPLACEHOLDER
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$position = 'left'">
						<xsl:choose>
							<xsl:when test="./section/title/@header_left">
								<xsl:value-of select="./section/title/@header_left"/>
							</xsl:when>
							<xsl:when test="./chapter/title/@header_left">
								<xsl:value-of select="./chapter/title/@header_left"/>
							</xsl:when>
							<xsl:otherwise>
								HEADERLEFTPLACEHOLDER
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="$sequence = 'even' or $sequence = 'blank'">
				<xsl:choose>
					<xsl:when test="$position = 'left'">
						<xsl:choose>
							<xsl:when test="./section/title/@header_right">
								<xsl:value-of select="./section/title/@header_right"/>
							</xsl:when>
							<xsl:when test="./chapter/title/@header_right">
								<xsl:value-of select="./chapter/title/@header_right"/>
							</xsl:when>
							<xsl:otherwise>
								HEADERRIGHTPLACEHOLDER
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$position = 'right'">
						<xsl:choose>
							<xsl:when test="./section/title/@header_left">
								<xsl:value-of select="./section/title/@header_left"/>
							</xsl:when>
							<xsl:when test="./chapter/title/@header_left">
								<xsl:value-of select="./chapter/title/@header_left"/>
							</xsl:when>
							<xsl:otherwise>
								HEADERLEFTPLACEHOLDER
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
				</xsl:choose>
			</xsl:when>
		</xsl:choose>
	</fo:block>
</xsl:template>
