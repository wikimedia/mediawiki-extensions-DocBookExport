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
			<xsl:when test="$pageclass = 'body'">
				<xsl:choose>
					<xsl:when test="$sequence = 'blank' and $position = 'center'">
						<xsl:text>This page intentionally left blank</xsl:text>
					</xsl:when>
					<xsl:when test="$sequence != 'blank'">
						<xsl:choose>
							<xsl:when test="$position = 'center'">
								<xsl:choose>
									<xsl:when test="./section/title/@header">
										<xsl:value-of select="./section/title/@header"/>
									</xsl:when>
									<xsl:when test="./chapter/title/@header">
										<xsl:value-of select="./chapter/title/@header"/>
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
					<xsl:when test="$sequence = 'odd' or $sequence = 'first'">
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
			</xsl:when>
	  </xsl:choose>
	</fo:block>
</xsl:template>

<xsl:template name="footer.content">
	<xsl:param name="pageclass" select="''"/>
	<xsl:param name="position" select="''"/>
	<xsl:param name="sequence" select="''"/>
        <fo:block>
			<xsl:choose>
				<xsl:when test="$pageclass = 'body'">
					<xsl:choose>
                        <xsl:when test="$position = 'center'">
							<xsl:apply-templates mode="page-number-prefix" select="."/>
							<fo:page-number/>
							<xsl:choose>
								<xsl:when test="./section/title/@footer">
									<xsl:value-of select="./section/title/@footer"/>
								</xsl:when>
								<xsl:when test="./chapter/title/@footer">
									<xsl:value-of select="./chapter/title/@footer"/>
								</xsl:when>
								<xsl:otherwise>
									<fo:block>FOOTERPLACEHOLDER</fo:block>
								</xsl:otherwise>
							</xsl:choose>
                        </xsl:when>
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


<xsl:template name="user.pagemasters">
  <!-- landscape body pages -->
      <fo:simple-page-master master-name="landscape-first"
                           page-width="{$page.width}"
                           page-height="{$page.height}"
                           margin-top="{$page.margin.top}"
                           margin-bottom="{$page.margin.bottom}">
      <xsl:attribute name="margin-{$direction.align.start}">
        <xsl:value-of select="$page.margin.inner"/>
	<xsl:if test="$fop.extensions != 0">
	  <xsl:value-of select="concat(' - (',$title.margin.left,')')"/>
        </xsl:if>
	<xsl:if test="$fop.extensions != 0">
	  <xsl:value-of select="concat(' - (',$title.margin.left,')')"/>
        </xsl:if>
      </xsl:attribute>
      <xsl:attribute name="margin-{$direction.align.end}">
        <xsl:value-of select="$page.margin.outer"/>
      </xsl:attribute>
      <xsl:if test="$axf.extensions != 0">
        <xsl:call-template name="axf-page-master-properties">
          <xsl:with-param name="page.master">body-first</xsl:with-param>
        </xsl:call-template>
      </xsl:if>
      <fo:region-body margin-bottom="{$body.margin.bottom}"
                      margin-top="{$body.margin.top}"
					  reference-orientation="90"
                      column-gap="{$column.gap.body}"
                      column-count="{$column.count.body}">
        <xsl:attribute name="margin-{$direction.align.start}">
          <xsl:value-of select="$body.margin.inner"/>
        </xsl:attribute>
        <xsl:attribute name="margin-{$direction.align.end}">
          <xsl:value-of select="$body.margin.outer"/>
        </xsl:attribute>
      </fo:region-body>
      <fo:region-before region-name="xsl-region-before-first"
                        extent="{$region.before.extent}"
                        precedence="{$region.before.precedence}"
                        display-align="before"/>
      <fo:region-after region-name="xsl-region-after-first"
                       extent="{$region.after.extent}"
                        precedence="{$region.after.precedence}"
                       display-align="after"/>
      <xsl:call-template name="region.inner">
        <xsl:with-param name="sequence">first</xsl:with-param>
        <xsl:with-param name="pageclass">body</xsl:with-param>
      </xsl:call-template>
      <xsl:call-template name="region.outer">
        <xsl:with-param name="sequence">first</xsl:with-param>
        <xsl:with-param name="pageclass">body</xsl:with-param>
      </xsl:call-template>
    </fo:simple-page-master>

    <fo:simple-page-master master-name="landscape-odd"
                           page-width="{$page.width}"
                           page-height="{$page.height}"
						   reference-orientation="90"
                           margin-top="{$page.margin.top}"
                           margin-bottom="{$page.margin.bottom}">
      <xsl:attribute name="margin-{$direction.align.start}">
        <xsl:value-of select="$page.margin.inner"/>
	<xsl:if test="$fop.extensions != 0">
	  <xsl:value-of select="concat(' - (',$title.margin.left,')')"/>
        </xsl:if>
      </xsl:attribute>
      <xsl:attribute name="margin-{$direction.align.end}">
        <xsl:value-of select="$page.margin.outer"/>
      </xsl:attribute>
      <xsl:if test="$axf.extensions != 0">
        <xsl:call-template name="axf-page-master-properties">
          <xsl:with-param name="page.master">body-odd</xsl:with-param>
        </xsl:call-template>
      </xsl:if>
      <fo:region-body margin-bottom="{$body.margin.bottom}"
                      margin-top="{$body.margin.top}"
                      column-gap="{$column.gap.body}"
                      column-count="{$column.count.body}">
        <xsl:attribute name="margin-{$direction.align.start}">
          <xsl:value-of select="$body.margin.inner"/>
        </xsl:attribute>
        <xsl:attribute name="margin-{$direction.align.end}">
          <xsl:value-of select="$body.margin.outer"/>
        </xsl:attribute>
      </fo:region-body>
      <fo:region-before region-name="xsl-region-before-odd"
                        extent="{$region.before.extent}"
                        precedence="{$region.before.precedence}"
                        display-align="before"/>
      <fo:region-after region-name="xsl-region-after-odd"
                       extent="{$region.after.extent}"
                       precedence="{$region.after.precedence}"
                       display-align="after"/>
      <xsl:call-template name="region.inner">
        <xsl:with-param name="pageclass">body</xsl:with-param>
        <xsl:with-param name="sequence">odd</xsl:with-param>
      </xsl:call-template>
      <xsl:call-template name="region.outer">
        <xsl:with-param name="pageclass">body</xsl:with-param>
        <xsl:with-param name="sequence">odd</xsl:with-param>
      </xsl:call-template>
    </fo:simple-page-master>

    <fo:simple-page-master master-name="landscape-even"
                           page-width="{$page.width}"
                           page-height="{$page.height}"
                           margin-top="{$page.margin.top}"
                           margin-bottom="{$page.margin.bottom}">
      <xsl:attribute name="margin-{$direction.align.start}">
        <xsl:value-of select="$page.margin.outer"/>
	<xsl:if test="$fop.extensions != 0">
	  <xsl:value-of select="concat(' - (',$title.margin.left,')')"/>
        </xsl:if>
      </xsl:attribute>
      <xsl:attribute name="margin-{$direction.align.end}">
        <xsl:value-of select="$page.margin.inner"/>
      </xsl:attribute>
      <xsl:if test="$axf.extensions != 0">
        <xsl:call-template name="axf-page-master-properties">
          <xsl:with-param name="page.master">body-even</xsl:with-param>
        </xsl:call-template>
      </xsl:if>
      <fo:region-body margin-bottom="{$body.margin.bottom}"
                      margin-top="{$body.margin.top}"
					  reference-orientation="90"
                      column-gap="{$column.gap.body}"
                      column-count="{$column.count.body}">
        <xsl:attribute name="margin-{$direction.align.start}">
          <xsl:value-of select="$body.margin.outer"/>
        </xsl:attribute>
        <xsl:attribute name="margin-{$direction.align.end}">
          <xsl:value-of select="$body.margin.inner"/>
        </xsl:attribute>
      </fo:region-body>
      <fo:region-before region-name="xsl-region-before-even"
                        extent="{$region.before.extent}"
                        precedence="{$region.before.precedence}"
                        display-align="before"/>
      <fo:region-after region-name="xsl-region-after-even"
                       extent="{$region.after.extent}"
                       precedence="{$region.after.precedence}"
                       display-align="after"/>
      <xsl:call-template name="region.outer">
        <xsl:with-param name="pageclass">body</xsl:with-param>
        <xsl:with-param name="sequence">even</xsl:with-param>
      </xsl:call-template>
      <xsl:call-template name="region.inner">
        <xsl:with-param name="pageclass">body</xsl:with-param>
        <xsl:with-param name="sequence">even</xsl:with-param>
      </xsl:call-template>
    </fo:simple-page-master>


    <fo:page-sequence-master master-name="landscape">
      <fo:repeatable-page-master-alternatives>
        <fo:conditional-page-master-reference master-reference="blank"
                                              blank-or-not-blank="blank"/>
        <fo:conditional-page-master-reference master-reference="landscape-first"
                                              page-position="first"/>
        <fo:conditional-page-master-reference master-reference="landscape-odd"
                                              odd-or-even="odd"/>
        <fo:conditional-page-master-reference
                                              odd-or-even="even">
          <xsl:attribute name="master-reference">
            <xsl:choose>
              <xsl:when test="$double.sided != 0">landscape-even</xsl:when>
              <xsl:otherwise>landscape-odd</xsl:otherwise>
            </xsl:choose>
          </xsl:attribute>
        </fo:conditional-page-master-reference>
      </fo:repeatable-page-master-alternatives>
    </fo:page-sequence-master>
</xsl:template>

<xsl:template name="select.user.pagemaster">
  <xsl:param name="element"/>
  <xsl:param name="pageclass"/>
  <xsl:param name="default-pagemaster"/>

  <xsl:choose>
    <xsl:when test="@role = 'landscape'">landscape</xsl:when>
    <xsl:when test="@role = 'portrait'">portrait</xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$default-pagemaster"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>