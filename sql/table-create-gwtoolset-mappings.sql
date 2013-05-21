-- MySQL database schema for the GWToolset extension.
-- @author: dan entous
-- @datetime 2013-01-10 22:58 gmt +1
-- @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html


CREATE TABLE IF NOT EXISTS /*_*/gwtoolset_mappings (

	`user_name` varchar(255) NOT NULL,
	`mediawiki_template_name` varchar(255) NOT NULL,
	`mapping_name` varchar(255) NOT NULL,
	`mapping_json` blob NOT NULL,
	`created` datetime NOT NULL,
	/*i*/KEY `user_name/mapping_name` (`user_name`,`mapping_name`)

) /*$wgDBTableOptions*/;


-- dublin core : Artwork
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`,`mapping_json`, `created` ) VALUES ( 'GWToolset', 'Artwork', 'dublin core : Artwork', '{"artist":["dc:creator"],"title":["dc:title","dcterms:alternative"],"description":["dc:description"],"date":["dc:date","dcterms:created","dcterms:issued"],"medium":["dc:format","dcterms:medium"],"dimensions":["dc:format","dcterms:extent"],"institution":["dcterms:provenance"],"location":["dc:identifier","dcterms:spatial"],"references":["dcterms:references"],"object history":["dcterms:provenance"],"exhibition history":["dc:description"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"source":["dc:source"],"permission":["dc:rights"],"other_versions":["dc:relation","dcterms:hasVersion"],"object type":["dc:type"]}', now() );


-- ESE : Artwork
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Artwork', 'ESE : Artwork', '{"artist":["dc:creator"],"title":["dc:title","dcterms:alternative"],"description":["dc:description"],"date":["dc:date","dcterms:created","dcterms:issued","europeana:year"],"medium":["dc:format","dcterms:medium"],"dimensions":["dc:format","dcterms:extent"],"institution":["europeana:dataProvider","dc:source","dcterms:provenance"],"location":["dc:identifier","dc:terms:spatial","europeana:country"],"references":["dcterms:references"],"object history":["dcterms:provenance","dc:rights"],"exhibition history":["dc:description"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"permission":["europeana:rights","dc:rights"],"other_versions":["dc:relation","dcterms:hasVersion"],"object type":["dc:type","europeana:type"]}', now() );


-- MODS : Artwork
--INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Artwork', 'MODS : Artwork', '{"artist":["name","namePart","roleTerm"],"title":["titleinfo","title"],"description":["note"],"date":["dateCreated","dateIssued","dateCaptured","dateOther"],"medium":["physicalDescription","form"],"dimensions":["physicalDescription","extent"],"institution":["location","physicalLocation","place"],"location":["location","shelfLocator"],"references":["relatedItem"],"object history":["originInfo"],"exhibition history":["note"],"credit line":["accessCondition"],"inscriptions":["note"],"notes":["note"],"accession number":["identifier","location","url"],"permission":["accessCondition"],"source":["relatedItem","title","location","url"],"other_versions":["relatedItem","titleInfo","title","location","url"],"object type":["typeOfResource","genre"]}', now() );


-- dublin core : Book
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Book', 'dublin core : Book', '{"Author":["dc:creator"],"translator":["dcterms:contributor"],"Editor":["dcterms:contributor"],"Illustrator":["dcterms:contributor"],"Title":["dc:title"],"Subtitle":["dcterms:alternative"],"Series title":["dcterms:alternative"],"Volume":["dcterms:extent"],"Edition":["dcterms:extent"],"Publisher":["dc:publisher"],"Printer":["dc:publisher"],"Date":["dc:date","dcterms:issued"],"City":["dc:publisher"],"Language":["dc:language"],"description":["dc:description"],"source":["dc:source"],"permissions":["dc:rights"],"image":["dc:description"],"Page overview":["dcterms:extent"],"Other_versions":["dcterms:hasVersion"],"ISBN":["dc:identifier"],"LCCN":["dc:identifier"],"OCLC":["dc:identifier"]}', now() );


-- ESE : Book
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Book', 'ESE : Book', '{"Author":["dc:creator"],"translator":["dcterms:contributor"],"Editor":["dcterms:contributor"],"Illustrator":["dcterms:contributor"],"Title":["dc:title"],"Subtitle":["dcterms:alternative"],"Series title":["dcterms:alternative"],"Volume":["dc:format","dcterms:extent","dcterms:isPartOf"],"Edition":["dc:format","dcterms:extent"],"Publisher":["dc:publisher"],"Printer":["dc:publisher"],"Date":["dc:date","dcterms:issued"],"City":["dc:publisher"],"Language":["dc:language"],"description":["dc:description"],"permissions":["dc:rights","europeana:rights"],"image":["europeana:object"],"Page overview":["dc:format","dcterms:extent"],"ISBN":["dc:identifier"],"LCCN":["dc:identifier"],"OCLC":["dc:identifier"]}', now() );


-- dublin core : Photograph
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Photograph', 'dublin core : Photograph', '{"Photographer":["dc:creator"],"title":["dc:title"],"description":["dc:description"],"date":["dc:date"],"medium":["dc:format","dcterms:medium"],"dimensions":["dc:format","dcterms:extent"],"institution":["dcterms:provenance"],"department":["dcterms:spatial"],"references":["dcterms:references"],"object history":["dcterms:provenance"],"exhibition history":["dcterms:provenance"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"source":["dc:source"],"permissions":["dc:rights"],"other_versions":["dcterms:references"]}', now() );


-- ESE : Photograph
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Photograph', 'ESE : Photograph', '{"Photographer":["dc:creator"],"title":["dc:title"],"description":["dc:description"],"date":["dc:date","dcterms:created"],"medium":["dc:format","dcterms:medium"],"dimensions":["dc:format","dcterms:extent"],"institution":["europeana:dataProvider","dc:source","dcterms:provenance"],"department":["dc:identifier","dcterms:spatial","europeana:country"],"references":["dcterms:references"],"object history":["dcterms:provenance"],"exhibition history":["dc:description"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"source":["dc:source"],"permissions":["dc:rights","europeana:rights"],"other_versions":["dcterms:references"]}', now() );


-- dublin core : Musical Work
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Musical work', 'dublin core : Musical work', '{"composer":["dc:creator"],"lyrics_writer":["dcterms:contributor"],"performer":["dcterms:contributor"],"title":["dc:title"],"description":["dc:description"],"composition_date":["dc:date","dcterms:created"],"performance_date":["dc:date","dcterms:issued"],"notes":["dc:description"],"references":["dcterms:references"],"source":["dc:source"],"permission":["dc:rights"],"other_versions":["dcterms:references"]}', now() );


-- ESE : Musical Work
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Musical work', 'ESE : Musical work', '{"composer":["dc:creator"],"lyrics_writer":["dcterms:contributor"],"performer":["dcterms:contributor"],"title":["dc:title"],"description":["dc:description"],"composition_date":["dc:date","dcterms:created"],"performance_date":["dc:date"],"notes":["dc:description"],"references":["dcterms:references"],"source":["dc:source"],"permission":["dc:rights","europeana:rights"],"other_versions":["dcterms:references"]}', now() );