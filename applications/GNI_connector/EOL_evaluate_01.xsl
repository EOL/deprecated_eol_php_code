<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:dwc="http://rs.tdwg.org/dwc/dwcore/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"	
    xmlns:eol="http://www.eol.org/transfer/content/0.1">	    

    <xsl:output method="xml" indent="yes" encoding="UTF-8"/>  		
	<xsl:template match="/">
        <result>
        <taxon><xsl:value-of select="count(//eol:taxon)"/></taxon>
        <dwc:ScientificName><xsl:value-of select="count(//dwc:ScientificName)"/></dwc:ScientificName>
        <reference><xsl:value-of select="count(//eol:taxon/eol:reference)"/></reference>
        <synonyms><xsl:value-of select="count(//eol:synonym)"/></synonyms>
        <commonName><xsl:value-of select="count(//eol:commonName)"/></commonName>        
    	<dataObjects><xsl:value-of select="count(//eol:dataObject)"/></dataObjects>
        <dataObjectsRef><xsl:value-of select="count(//eol:dataObject/eol:reference)"/></dataObjectsRef>
	    <texts><xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/Text'])"/></texts>
	    <images><xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/StillImage'])"/></images>
	    <videos><xsl:value-of select="count(//eol:dataObject[eol:dataType = 'http://purl.org/dc/dcmitype/MovingImage'])"/></videos>
        <xsl:for-each select="eol:response/eol:taxon/eol:dataObject">
    		<xsl:variable name="subject" select='substring-after(eol:subject[not(.=following::eol:subject)],"#")' />            
    		<xsl:if test="$subject != ''">
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
        	</xsl:if>
        </xsl:for-each>    
        </result>
    </xsl:template>
</xsl:stylesheet>