<xsl:template name="footer.content">
	<xsl:param name="pageclass" select="''"/>
	<xsl:param name="position" select="''"/>
	<xsl:param name="sequence" select="''"/>
        <fo:block>
			<xsl:choose>
				<xsl:when test="$sequence = 'odd' or $sequence = 'first'">
					<xsl:choose>
						<xsl:when test="$position = 'right'">
							<xsl:choose>
								<xsl:when test="./section/title/@footer_right">
									<xsl:value-of select="./section/title/@footer_right"/>
								</xsl:when>
								<xsl:when test="./chapter/title/@footer_right">
									<xsl:value-of select="./chapter/title/@footer_right"/>
								</xsl:when>
								<xsl:otherwise>
									FOOTERRIGHTPLACEHOLDER
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:when test="$position = 'left'">
							<xsl:choose>
								<xsl:when test="./section/title/@footer_left">
									<xsl:value-of select="./section/title/@footer_left"/>
								</xsl:when>
								<xsl:when test="./chapter/title/@footer_left">
									<xsl:value-of select="./chapter/title/@footer_left"/>
								</xsl:when>
								<xsl:otherwise>
									FOOTERLEFTPLACEHOLDER
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="$sequence = 'even' or $sequence = 'blank'">
					<xsl:choose>
						<xsl:when test="$position = 'left'">
							<xsl:choose>
								<xsl:when test="./section/title/@footer_right">
									<xsl:value-of select="./section/title/@footer_right"/>
								</xsl:when>
								<xsl:when test="./chapter/title/@footer_right">
									<xsl:value-of select="./chapter/title/@footer_right"/>
								</xsl:when>
								<xsl:otherwise>
									FOOTERRIGHTPLACEHOLDER
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:when test="$position = 'right'">
							<xsl:choose>
								<xsl:when test="./section/title/@footer_left">
									<xsl:value-of select="./section/title/@footer_left"/>
								</xsl:when>
								<xsl:when test="./chapter/title/@footer_left">
									<xsl:value-of select="./chapter/title/@footer_left"/>
								</xsl:when>
								<xsl:otherwise>
									FOOTERLEFTPLACEHOLDER
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
					</xsl:choose>
				</xsl:when>
			</xsl:choose>
        </fo:block>
</xsl:template>
