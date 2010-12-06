<?xml version='1.0' encoding='utf-8' ?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:dwc="http://rs.tdwg.org/dwc/dwcore/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"		
	xmlns:eol="http://www.eol.org/transfer/content/0.2">	

<xsl:output method="xml" indent="yes" encoding="iso-8859-1"/>  	
<xsl:template match="/">
    <DataSet>
        <TaxonNames>
            <xsl:for-each select="eol:response/eol:taxon">		            
            	<xsl:variable name="dwc_ScientificName"> <xsl:value-of select="dwc:ScientificName"/> </xsl:variable>		
            	<xsl:variable name="dwc_Kingdom"> <xsl:value-of select="dwc:Kingdom"/> </xsl:variable>
            	<xsl:variable name="dwc_Phylum"> <xsl:value-of select="dwc:Phylum"/> </xsl:variable>
            	<xsl:variable name="dwc_Class"> <xsl:value-of select="dwc:Class"/> </xsl:variable>
            	<xsl:variable name="dwc_Order"> <xsl:value-of select="dwc:Order"/> </xsl:variable>	
            	<xsl:variable name="dwc_Family"> <xsl:value-of select="dwc:Family"/> </xsl:variable>
            	<xsl:variable name="dwc_Genus"> <xsl:value-of select="dwc:Genus"/> </xsl:variable>            
            	<xsl:variable name="dc_source"> <xsl:value-of select="dc:source"/> </xsl:variable>
            	<xsl:variable name="dc_identifier"> <xsl:value-of select="dc:identifier"/> </xsl:variable>            
                <TaxonName id="{concat($dc_identifier,'_',position())}">            
        			<Simple>            			
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
                    <xsl:if test="dwc:Kingdom != '' or dc:source != '' or dc:identifier != ''">
            		    <ProviderSpecificData>
            			    <xsl:if test="dwc:Kingdom != ''"><dwc:Kingdom><xsl:value-of select="dwc:Kingdom"/></dwc:Kingdom></xsl:if>				
                			<xsl:if test="dc:source != ''"><dc:source><xsl:value-of select="dc:source"/></dc:source></xsl:if>				
                			<xsl:if test="dc:identifier != ''"><dc:identifier><xsl:value-of select="dc:identifier"/></dc:identifier></xsl:if>							
            	    	</ProviderSpecificData>								        
                    </xsl:if>                            		
                </TaxonName>			
            			
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
            		<xsl:variable name="syn"> <xsl:value-of select="."/> </xsl:variable>            		
            		<TaxonName id="{concat($dc_identifier,'_syn_',position())}">						
            			<Simple>							
            				<xsl:value-of select="."/>
            			</Simple>			            			
            			<Rank><xsl:value-of select="$rank_element2" /></Rank>            
                        <xsl:if test="$dwc_Kingdom != '' or $dc_source != '' or $dc_identifier != ''">
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
        </TaxonNames>
    </DataSet>
</xsl:template>
</xsl:stylesheet>