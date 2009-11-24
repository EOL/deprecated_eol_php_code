<?xml version='1.0' encoding='utf-8' ?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:dwc="http://rs.tdwg.org/dwc/dwcore/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"		
	xmlns:eol="http://www.eol.org/transfer/content/0.2">
	
	
<!--
	xmlns:eol="http://www.eol.org/transfer/content/0.2"
	xmlns:eol="http://www.eol.org/transfer/content/0.1"
	
<response 	
		xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
		xmlns:dcterms="http://purl.org/dc/terms/" 
		xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" 
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
		xmlns="http://www.eol.org/transfer/content/0.1" 
		       http://www.eol.org/transfer/content/0.2
			   
		xsi:schemaLocation="http://www.eol.org/transfer/content/0.1 http://services.eol.org/schema/content_0_1.xsd">
		                    http://www.eol.org/transfer/content/0.2
-->	
	
	
	
	
<!--
<xsl:output method="xml" indent="yes" encoding="utf-8"/>  	
-->
<xsl:output method="xml" indent="yes" encoding="iso-8859-1"/>  	


<!--
fn:QName()  	 
fn:local-name-from-QName() 	 
fn:namespace-uri-from-QName() 	 
fn:namespace-uri-for-prefix()
-->




<xsl:template match="/">
<DataSet>
<TaxonNames>




<xsl:for-each select="eol:response/eol:taxon">

		
	<xsl:variable name="dwc_ScientificName"> <xsl:value-of select="dwc:ScientificName"/> 	
	</xsl:variable>	
	
	<xsl:variable name="dwc_Kingdom"> <xsl:value-of select="dwc:Kingdom"/> </xsl:variable>
	<xsl:variable name="dwc_Phylum"> <xsl:value-of select="dwc:Phylum"/> </xsl:variable>
	<xsl:variable name="dwc_Class"> <xsl:value-of select="dwc:Class"/> </xsl:variable>
	<xsl:variable name="dwc_Order"> <xsl:value-of select="dwc:Order"/> </xsl:variable>	
	<xsl:variable name="dwc_Family"> <xsl:value-of select="dwc:Family"/> </xsl:variable>
	<xsl:variable name="dwc_Genus"> <xsl:value-of select="dwc:Genus"/> </xsl:variable>

	<xsl:variable name="dc_source"> <xsl:value-of select="dc:source"/> </xsl:variable>
	<xsl:variable name="dc_identifier"> <xsl:value-of select="dc:identifier"/> </xsl:variable>

	<!-- use to test if variable exists
 	<xsl:if test="boolean(string($css-style))">    	
  	</xsl:if>
	-->
	
	<!--
	<xsl:variable name="syn_id">0</xsl:variable>	
	-->
	
	
	<!--
	<xsl:variable name="syn_id"> 
		<xsl:number value="position()" format="1" />
	</xsl:variable>
	<xsl:variable name="syn_id2"> 
		<xsl:number value="generate-id()" format="1" />
	</xsl:variable>
	-->

	<!--
	<xsl:value-of select = "namespace-uri()" /> 
	
node.setAttribute("xmlns:eol","http://www.w3.org/2001/XMLSchema-instance")			
	-->

        <!-- replaced bec some EOL XML like Flickr doesn't have taxon dc:identifier
		<TaxonName id="{dc:identifier}">						
        -->
        <TaxonName id="{concat($dc_identifier,'_',position())}">

			<Simple>
			
			<!-- <xsl:value-of select = "namespace-uri()" /> -->
													
			<xsl:choose>
    			<xsl:when test="dwc:ScientificName != ''"><xsl:value-of select="dwc:ScientificName"/></xsl:when>
				<xsl:otherwise>
					<xsl:choose>
    					<xsl:when test="dwc:Genus != ''"><xsl:value-of select="dwc:Genus"/></xsl:when>
						<xsl:otherwise>
							<xsl:choose>
    							<xsl:when test="dwc:Family != ''"><xsl:value-of select="dwc:Family"/></xsl:when>
								<xsl:otherwise>
									<xsl:choose>
    									<xsl:when test="dwc:Order != ''"><xsl:value-of select="dwc:Order"/></xsl:when>
										<xsl:otherwise>
											<xsl:choose>
						    					<xsl:when test="dwc:Class != ''"><xsl:value-of select="dwc:Class"/></xsl:when>
												<xsl:otherwise>													
													<xsl:choose>
								    					<xsl:when test="dwc:Phylum != ''"><xsl:value-of select="dwc:Phylum"/></xsl:when>
														<xsl:otherwise>
															<xsl:value-of select="dwc:Kingdom"/>
														</xsl:otherwise>														
													</xsl:choose>		
												</xsl:otherwise>
											</xsl:choose>												
										</xsl:otherwise>
									</xsl:choose>										
								</xsl:otherwise>
							</xsl:choose>								
						</xsl:otherwise>
					</xsl:choose>						
				</xsl:otherwise>
			</xsl:choose>																									
			</Simple>			


			<!--
			<xsl:if test="dwc:ScientificName != ''"><Rank>Species</Rank></xsl:if>			
			<xsl:if test="dwc:ScientificName = ''">
			</xsl:if>												
			-->	
				
			<!--
			<xsl:variable name="rank_element"> 				
				<xsl:if test="dwc:Genus != ''">Species</xsl:if>			
				<xsl:if test="dwc:Genus = '' or count(dwc:Genus) = 0">
					<xsl:if test="dwc:Family != '' and dwc:ScientificName = dwc:Family">Family</xsl:if>				
					<xsl:if test="dwc:Family = '' or count(dwc:Family) = 0">
						<xsl:if test="dwc:Order != '' and dwc:ScientificName = dwc:Order">Order</xsl:if>				
						<xsl:if test="dwc:Order = '' or count(dwc:Order) = 0">
							<xsl:if test="dwc:Class != '' and dwc:ScientificName = dwc:Class">Class</xsl:if>				
							<xsl:if test="dwc:Class = '' or count(dwc:Class) = 0">
								<xsl:if test="dwc:Phylum != '' and dwc:ScientificName = dwc:Phylum">Phylum</xsl:if>				
								<xsl:if test="dwc:Phylum = '' or count(dwc:Phylum) = 0">
									<xsl:if test="dwc:Kingdom != '' and dwc:ScientificName = dwc:Kingdom">Kingdom</xsl:if>				
									<xsl:if test="dwc:Kingdom = '' or count(dwc:Kingdom) = 0"></xsl:if>				
								</xsl:if>												
							</xsl:if>											
						</xsl:if>										
					</xsl:if>									
				</xsl:if>							
			</xsl:variable>
			-->
			

			<xsl:variable name="rank_element"> 				
			<xsl:choose>
    	    	<xsl:when test="dwc:Genus = dwc:ScientificName">Genus</xsl:when>
				<xsl:otherwise>            
					<xsl:choose>
    			    	<xsl:when test="dwc:Family = dwc:ScientificName">Family</xsl:when>
						<xsl:otherwise>            				
							<xsl:choose>
    					    	<xsl:when test="dwc:Order = dwc:ScientificName">Order</xsl:when>
								<xsl:otherwise>            						
									<xsl:choose>
    							    	<xsl:when test="dwc:Class = dwc:ScientificName">Class</xsl:when>
										<xsl:otherwise>            																
											<xsl:choose>
    									    	<xsl:when test="dwc:Phylum = dwc:ScientificName">Phylum</xsl:when>
												<xsl:otherwise>            						
													<xsl:choose>
    											    	<xsl:when test="dwc:Kingdom = dwc:ScientificName">Kindom</xsl:when>
														<xsl:otherwise>Species</xsl:otherwise>
													</xsl:choose>																									
					   			    		 	</xsl:otherwise>
											</xsl:choose>																																
					   	    		 	</xsl:otherwise>
									</xsl:choose>																						
					   	     	</xsl:otherwise>
							</xsl:choose>										
			   	     	</xsl:otherwise>
					</xsl:choose>				
	   	     	</xsl:otherwise>
			</xsl:choose>
			</xsl:variable>

			<Rank><xsl:value-of select="$rank_element" /></Rank>		
		
	
        <xsl:if test="dwc:Kingdom != ''"    or
                test="dc:source != ''"      or
                test="dc:identifier != ''"                
        >
		    <ProviderSpecificData>
			    <xsl:if test="dwc:Kingdom != ''"><dwc:Kingdom><xsl:value-of select="dwc:Kingdom"/></dwc:Kingdom></xsl:if>				
    			<xsl:if test="dc:source != ''"><dc:source><xsl:value-of select="dc:source"/></dc:source></xsl:if>				
    			<xsl:if test="dc:identifier != ''"><dc:identifier><xsl:value-of select="dc:identifier"/></dc:identifier></xsl:if>							
	    	</ProviderSpecificData>								        
        </xsl:if>
        
		
		</TaxonName>			
	


			<!--
			basically just the same as rank_element			
			<xsl:variable name="rank_element2"> 				
				<xsl:if test="dwc:Genus != ''">Species</xsl:if>			
				<xsl:if test="dwc:Genus = '' or count(dwc:Genus) = 0">
					<xsl:if test="dwc:Family != ''">Family</xsl:if>				
					<xsl:if test="dwc:Family = '' or count(dwc:Family) = 0">
						<xsl:if test="dwc:Order != ''">Order</xsl:if>				
						<xsl:if test="dwc:Order = '' or count(dwc:Order) = 0">
							<xsl:if test="dwc:Class != ''">Class</xsl:if>				
							<xsl:if test="dwc:Class = '' or count(dwc:Class) = 0">
								<xsl:if test="dwc:Phylum != ''">Phylum</xsl:if>				
								<xsl:if test="dwc:Phylum = '' or count(dwc:Phylum) = 0">
									<xsl:if test="dwc:Kingdom != ''">Kingdom</xsl:if>				
									<xsl:if test="dwc:Kingdom = '' or count(dwc:Kingdom) = 0"></xsl:if>				
								</xsl:if>												
							</xsl:if>											
						</xsl:if>										
					</xsl:if>									
				</xsl:if>							
			</xsl:variable>
			-->				

			
			<xsl:variable name="rank_element2"> 							
			<xsl:choose>
    	    	<xsl:when test="dwc:Genus = dwc:ScientificName">Genus</xsl:when>
				<xsl:otherwise>            
					<xsl:choose>
    			    	<xsl:when test="dwc:Family = dwc:ScientificName">Family</xsl:when>
						<xsl:otherwise>            				
							<xsl:choose>
    					    	<xsl:when test="dwc:Order = dwc:ScientificName">Order</xsl:when>
								<xsl:otherwise>            						
									<xsl:choose>
    							    	<xsl:when test="dwc:Class = dwc:ScientificName">Class</xsl:when>
										<xsl:otherwise>            																
											<xsl:choose>
    									    	<xsl:when test="dwc:Phylum = dwc:ScientificName">Phylum</xsl:when>
												<xsl:otherwise>            						
													<xsl:choose>
    											    	<xsl:when test="dwc:Kingdom = dwc:ScientificName">Kindom</xsl:when>
														<xsl:otherwise>Species</xsl:otherwise>
													</xsl:choose>																									
					   			    		 	</xsl:otherwise>
											</xsl:choose>																																
					   	    		 	</xsl:otherwise>
									</xsl:choose>																						
					   	     	</xsl:otherwise>
							</xsl:choose>										
			   	     	</xsl:otherwise>
					</xsl:choose>				
	   	     	</xsl:otherwise>
			</xsl:choose>
			</xsl:variable>			

		
	<!-- ############################################################################################ -->
	<xsl:for-each select="eol:synonym">

		<!--
		<synonym><xsl:value-of select="."/></synonym>
		concat($syn_id2,$syn)
		generate-id()
		-->
		
		<xsl:variable name="syn"> <xsl:value-of select="."/> </xsl:variable>
		
		
		<!-- generate-id() -->
		<TaxonName id="{concat($dc_identifier,'_syn_',position())}">						
			<Simple>							
				<xsl:value-of select="."/>
			</Simple>			
			
			<Rank><xsl:value-of select="$rank_element2" /></Rank>
			

            <xsl:if test="$dwc_Kingdom != ''"    or
                    test="$dc_source != ''"      or
                    test="$dc_identifier != ''"                
            >
    			<ProviderSpecificData>
	    			<xsl:if test="$dwc_Kingdom != ''"><dwc:Kingdom><xsl:copy-of select="$dwc_Kingdom" /></dwc:Kingdom></xsl:if>				
		    		<xsl:if test="$dc_source != ''"><dc:source><xsl:copy-of select="$dc_source" /></dc:source></xsl:if>				
			    	<xsl:if test="$dc_identifier != ''">
				    	<dc:identifier>
					    	<xsl:copy-of select="concat($dc_identifier,'_syn_',string(position()))" />						
    					</dc:identifier>
	    			</xsl:if>							
    			</ProviderSpecificData>								            
            </xsl:if>
            


		</TaxonName>			

	</xsl:for-each>	
	<!-- ############################################################################################ -->



		
</xsl:for-each>
	
	
</TaxonNames></DataSet></xsl:template></xsl:stylesheet>