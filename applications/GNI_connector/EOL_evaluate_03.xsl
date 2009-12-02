<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:dwc="http://rs.tdwg.org/dwc/dwcore/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:eol="http://www.eol.org/transfer/content/0.3">

	
<!--
<xsl:template match="/">
	<xsl:value-of select="count(//eol:taxon)"/>
</xsl:template>
-->

	
<xsl:template match="/">
<html>
<body>
	<table>
	<tr><td>
	dataObjects = <xsl:value-of select="count(//eol:dataObject)"/>
	</td></tr>		

	<tr><td>
	synonym = <xsl:value-of select="count(//eol:synonym)"/>
	</td></tr>		

	<tr><td>
	common name = <xsl:value-of select="count(//eol:commonName)"/>
	</td></tr>		
    
	<tr><td>
	dwc:ScientificName = <xsl:value-of select="count(//dwc:ScientificName)"/>
	</td></tr>		


	<tr><td>
	<a href='javascript:self.close()'>&lt;&lt; Back to menu</a>
	</td></tr>
	
	</table>	
</body>
</html>
</xsl:template>

</xsl:stylesheet>