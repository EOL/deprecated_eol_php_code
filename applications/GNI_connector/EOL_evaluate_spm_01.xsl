<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:dwc="http://rs.tdwg.org/dwc/dwcore/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:eol="http://www.eol.org/transfer/content/0.1">
<xsl:template match="/">
<html>
<body>
    <table>
    <tr><td>taxon = <xsl:value-of select="count(//eol:taxon)"/></td></tr>
    <tr><td>dwc:ScientificName = <xsl:value-of select="count(//dwc:ScientificName)"/></td></tr>
    <tr><td>taxon reference = <xsl:value-of select="count(//eol:taxon/eol:reference)"/></td></tr>
    <tr><td>synonym = <xsl:value-of select="count(//eol:synonym)"/></td></tr>
    <tr><td>commonName = <xsl:value-of select="count(//eol:commonName)"/></td></tr>
    <tr><td><hr></hr></td></tr>
    <tr><td>DataObjects = <xsl:value-of select="count(//eol:dataObject)"/></td></tr>
    <tr><td>reference = <xsl:value-of select="count(//eol:dataObject/eol:reference)"/></td></tr>
    <tr><td><hr></hr></td></tr>
    <tr><td>DataObjects breakdown</td></tr>
    <tr><td>texts = <xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/Text'])"/></td></tr>
    <tr><td>images = <xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/StillImage'])"/></td></tr>
    <tr><td>videos = <xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/MovingImage'])"/></td></tr>
    <tr><td>sounds = <xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/Sound'])"/></td></tr>
    <tr><td><hr></hr></td></tr>
    <tr><td>SPM breakdown</td></tr>
    <xsl:for-each select="eol:response/eol:taxon/eol:dataObject">
        <xsl:variable name="subject" select='substring-after(eol:subject[not(.=following::eol:subject)],"#")' />
        <xsl:if test="$subject != ''">
            <tr>
            <td><xsl:value-of select="$subject"/> =
            <subject id="{concat($subject,'')}">
            <xsl:choose>
                <xsl:when test="$subject != 'Barcode' and $subject != 'Education' and $subject != 'Wikipedia'">
                    <xsl:value-of select="count(//eol:dataObject[eol:subject = concat('http://rs.tdwg.org/ontology/voc/SPMInfoItems#',$subject)])"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="count(//eol:dataObject[eol:subject = concat('http://www.eol.org/voc/table_of_contents#',$subject)])"/>
                </xsl:otherwise>
            </xsl:choose>
            </subject>
            </td>
            </tr>
        </xsl:if>
    </xsl:for-each>
    <tr><td><hr></hr></td></tr>
    <tr><td><a href='javascript:self.close()'>&lt;&lt; Back to menu</a></td></tr>
    </table>
</body>
</html>
</xsl:template>
</xsl:stylesheet>