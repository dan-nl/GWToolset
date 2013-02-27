-- MySQL database schema for the GWToolset extension.
-- @author: dan entous
-- @datetime 2013-01-10 22:58 gmt +1
-- @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html


CREATE TABLE IF NOT EXISTS /*_*/gwtoolset_mappings (

	`user_name`					varchar(255)	NOT NULL,
	`mediawiki_template_name`	varchar(255)	NOT NULL,
	`mapping_name`				varchar(255)	NOT NULL,
	`mapping_json`				blob			NOT NULL,
	`created`					datetime		NOT NULL,
	KEY `user_name/mapping_name` (`user_name`,`mapping_name`)

) /*$wgDBTableOptions*/;


-- dublin core : Artwork
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`,`mapping_json`, `created` ) VALUES ( 'GWToolset', 'Artwork', 'dublin core : Artwork', '{"artist":["dc:creator"],"title":["dc:title","dcterms:alternative"],"description":["dc:description"],"date":["dc:date","dcterms:created","dcterms:issued"],"medium":["dc:format","dcterms:medium"],"dimensions":["dc:format","dcterms:extent"],"institution":["dcterms:provenance"],"location":["dc:identifier","dcterms:spatial"],"references":["dcterms:references"],"object history":["dcterms:provenance"],"exhibition history":["dc:description"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"source":["dc:source"],"permission":["dc:rights"],"other_versions":["dc:relation","dcterms:hasVersion"],"object type":["dc:type"]}', now() );


-- dublin core : Book
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Book', 'dublin core : Book', '{"Author":["dc:creator"],"translator":["dcterms:contributor"],"Editor":["dcterms:contributor"],"Illustrator":["dcterms:contributor"],"Title":["dc:title"],"Subtitle":["dcterms:alternative"],"Series title":["dcterms:alternative"],"Volume":["dcterms:extent"],"Edition":["dcterms:extent"],"Publisher":["dc:publisher"],"Printer":["dc:publisher"],"Date":["dcterms:issued"],"City":["dc:publisher"],"Language":["dc:language"],"description":["dc:description"],"source":["dc:source"],"permissions":["dc:rights"],"image":["dc:description"],"Page overview":["dcterms:extent"],"Homecat":["dcterms:references"],"Other_versions":["dcterms:hasVersion"],"ISBN":["dc:identifier"],"LCCN":["dc:identifier"],"OCLC":["dc:identifier"]}', now() );


-- dublin core : Photograph
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Photograph', 'dublin core : Photograph', '{"Photographer":["dc:creator"],"title":["dc:title"],"description":["dc:description"],"date":["dc:date"],"medium":["dcterms:medium"],"dimensions":["dcterms:extent"],"institution":["dcterms:provenance"],"department":["dcterms:spatial"],"references":["dcterms:extent"],"object history":["dcterms:provenance"],"exhibition history":["dcterms:provenance"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"source":["dc:source"],"permissions":["dc:rights"],"other_versions":["dcterms:references"],"other_fields":["dcterms:extent"]}', now() );


-- dublin core : Musical Work
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Musical work', 'dublin core : Musical work', '{"composer":["dc:creator"],"lyrics_writer":["dcterms:contributor"],"performer":["dcterms:contributor"],"title":["dc:title"],"description":["dc:title"],"composition_date":["dcterms:alternative"],"performance_date":["dcterms:alternative"],"notes":["dcterms:alternative"],"references":["dcterms:extent"],"source":["dc:publisher"],"permission":["dc:publisher"],"other_versions":["dcterms:issued"],"other_fields":["dc:publisher"]}', now() );


-- ESE : Artwork
-- INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mediawiki_template_name`, `mapping_name`, `mapping_json`, `created` ) VALUES ( 'GWToolset', 'Artwork', 'ESE : Artwork', {"artist":["dc:creator"],"title":["dc:title","dcterms:alternative"],"description":["dc:description"],"date":["dc:date","dcterms:created","dcterms:issued","europeana:year"],"medium":["dc:format","dcterms:medium"],"Dimensions":["dcterms:extent"],"Institution":["dcterms:provenance"],"Location":["dc:terms:spatial"],"References":["dc:terms:references"],"object history":["dc:terms:provenance"],"exhibition history":["dc:description"],"credit line":["dc:rights"],"inscriptions":["dc:description"],"notes":["dc:description"],"accession number":["dc:identifier"],"permission":["dc:rights"],"other_versions":["dcterms:hasVersion"],"object type":["dc:type"]}', now() );