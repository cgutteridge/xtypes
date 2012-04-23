<?php
#error_reporting(0);
require_once( "ontolib.php" );

$base = 'http://purl.org/xtypes';
$PATH = "".@$_SERVER["PATH_INFO"];
if( $PATH == "" ) { resolve( "/" ); }
if( $PATH == "/" ) { serve(); }

#http://purl.org/xtypes/XXXX all redirect to
#http://ecs/resolver/XXXX
# predicates 
if( $PATH == '/hasFileExtension' || $PATH == '/hasMimeType' ) { resolve( "/" ); }

# top classes
if( $PATH == '/Fragment' ) { resolve( "/" ); }
if( $PATH == '/Encoding' ) { resolve( "/" ); }
if( $PATH == '/DocumentEncoded' ) { resolve( "/" ); }

if( preg_match( '/\/(DocumentEncoded|Encoding)-(.+)$/', $PATH, $bits ) ) 
{ 
	$type = $bits[1];
	$enc = $bits[2];
	$encodings = encodings();
	if( isset( $encodings[$enc] ) ) { resolve("/"); }
	serve( $type, $enc );
	exit;
}
# get parts
if( !preg_match( '/\/(Document|Format|Fragment)-([^-]+)(?:-(.*))?$/', $PATH, $bits ) ) { do404(); }
$type = $bits[1];
$format = $bits[2];

# generic formats
if( preg_match( '/^(Audio|Package|Office|Video|RDFSerialisation|Code)$/', $format )) { resolve("/"); }

# versioned format 
if( @$bits[3] ) { serve( $type, $format, $bits[3] ); }

# standard formats
$formats = formats();
if( isset( $formats[$format] ) ) { resolve("/"); }

# unversioned not standard format
serve( $type, $format );

exit;

#/
#/VALIDCLASS -> /
#/miscbits -> /
#/parents... -> /
#/validclass-version
#/OTHERCLASS 
#/OTHERCLASS-version

#LanguageFormatHTML
#LanguageFragmentHTML.v5
#Document-HTML-1.23
#Format-HTML-4
#Fragment-HTML

#"MP3:Audio:audio/wav:.wav:MP3 Audio:nf",
function rdf_header($path)
{
	global $base;
	$url = $base.$path;
	return '<?xml version="1.0" encoding="utf-8"?>
<rdf:RDF
    xmlns:xtypes="http://purl.org/xtypes/"
    xmlns:vann="http://purl.org/vocab/vann/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:foaf="http://xmlns.com/foaf/0.1/"
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:owl="http://www.w3.org/2002/07/owl#">

<owl:Ontology rdf:about="http://purl.org/xtypes/">
  <vann:preferredNamespaceUri>xtypes</vann:preferredNamespaceUri>
  <rdfs:label>Extra Types Namespace</rdfs:label>
  <dcterms:hasFormat>
    <xtypes:Document-HTML rdf:about="'.$url.'?format=HTML" rdfs:label="Extra Types Namespace described as HTML" />
    <xtypes:Document-RDFXML rdf:about="'.$url.'?format=RDFXML" rdfs:label="Extra Types Namespace described as RDF+XML" />
  </dcterms:hasFormat>
  <dcterms:creator>
     <foaf:Person rdf:about="http://id.ecs.soton.ac.uk/person/1248">
       <foaf:name>Christopher Gutteridge</foaf:name>
       <foaf:mbox rdf:resource="mailto:cjg@ecs.soton.ac.uk" />
       <foaf:homepage rdf:resource="http://users.ecs.soton.ac.uk/cjg/" />
     </foaf:Person>
  </dcterms:creator>
  <rdfs:comment>This namespace provides identifiers for many common formats and encodings, and also for documents in that format and for (RDF) literals containing fragments of these formats. 

The Format-XXX and Encoding-XXX classes are intended for use with dcterms:format or similar predicates.

The Document-XXX and DocumentEncoded-XXX classes are intended for use with rdf:type to indicate the format and character encoding of the Document available at a URL.

The Fragment-XXX classes are intended for use as the datatype of literals in RDF. 

In some cases you might use Fragment-XXX as the type of a URL to indicate the URL resolves to a fragment, not a valid document. You might also use Document-XXX as a datatype if you had an entire valid document in a literal for some whacky reason.

Sub-versions of formats, document types and fragments: If you want to specifically indicate a version of a document, fragment or format, you can refer to it in the form Fragment-HTML-4.1 - this will resolve to an RDF class telling you it\'s version 4.1 of Fragment-HTML.

If you resolve Fragment-MyCrazyFormat that will resolve to a fragment class, and a format class. Use at your own risk. 

Motivation: This was actually just created due to the lack of a good datatype to indicate that a literal is a fragment of HTML rather than plain text. We brainstromed a list of useful formats and got the encodings from wikipedia, feedback welcome.

\'blessing\' new formats or encodings: If there\'s a format or encoding which really should be in the main list, and not \'unstable\' tell me why at cjg@ecs.soton.ac.uk
</rdfs:comment> 
</owl:Ontology>
<owl:Ontology rdf:about="http://xmlns.com/foaf/0.1/">
  <vann:preferredNamespaceUri>foaf</vann:preferredNamespaceUri>
</owl:Ontology>
<owl:Ontology rdf:about="http://purl.org/dc/terms/">
  <vann:preferredNamespaceUri>dcterms</vann:preferredNamespaceUri>
</owl:Ontology>
';
}
function serve($type='',$format='',$version='')
{
	if( ontolib_wants() != "RDF" )
	{
		$url = "http://graphite.ecs.soton.ac.uk".$_SERVER["SCRIPT_NAME"].$_SERVER["PATH_INFO"];
		global $base;
		print "<h1>Extra Types!</h1>";
		print ontolib_render_html( $url, "$base/" );

		exit;
	}
	
	header( "content-type: application/rdf+xml");

	if( $type == 'Document' ) { $data = render_Document( $format, $version ); }
	elseif( $type == 'Format' ) { $data = render_Format( $format, $version ); }
	elseif( $type == 'Fragment' ) { $data = render_Fragment( $format, $version ); }
	elseif( $version == '' && $type == 'DocumentEncoded' ) { $data = render_DocumentEncoded( $format ); }
	elseif( $version == '' && $type == 'Encoding' ) { $data = render_Encoding( $format ); }
	elseif( $type == '' ) { $data= render_full(); }
	else { print "Unknown type; $type"; exit; }
	$path = '/';
	if( $type != '' ) { $path.="$type-$format"; }
	if( $version != '' ) { $path.="-$version"; }
	print rdf_header($path);
	print $data;
	print rdf_footer();
	exit;
}

function render_Format( $format, $version='' )
{
	global $base;
	
	$rdf = "";
	$rdf.= " 		
<dcterms:MediaType rdf:about='http://purl.org/xtypes/Format-$format'>
  <rdfs:label>$format Format</rdfs:label>
</dcterms:MediaType>
";
	if( $version != '' )
	{
		$rdf.= " 		
<dcterms:MediaType rdf:about='http://purl.org/xtypes/Format-$format-$version'>
  <rdfs:label>$format Format (version $version)</rdfs:label>
</dcterms:MediaType>
";
	}

	return $rdf;
}
function render_Fragment( $format, $version='' )
{
	global $base;
	$rdf = "";
	$rdf.= " 		
<rdfs:Datatype rdf:about='http://purl.org/xtypes/Fragment-$format'>
  <rdfs:label>$format Fragment</rdfs:label>
  <dcterms:format rdf:resource='http://purl.org/xtypes/Format-$format'/>
  <rdfs:subClassOf rdf:resource='http://purl.org/xtypes/Fragment' />
</rdfs:Datatype>
";
	if( $version != '' )
	{
		$rdf.= " 		
<rdfs:Datatype rdf:about='http://purl.org/xtypes/Fragment-$format-$version'>
  <rdfs:label>$format Fragment (version $version)</rdfs:label>
  <dcterms:format rdf:resource='http://purl.org/xtypes/Format-$format-$version'/>
  <rdfs:subClassOf rdf:resource='http://purl.org/xtypes/Fragment-$format' />
</rdfs:Datatype>
";
	}

	$rdf .= render_Format( $format, $version );
	return $rdf;
}

function render_Document( $format, $version='' )
{
	global $base;
	$rdf = "";
	$rdf.= " 		
<rdfs:Class rdf:about='http://purl.org/xtypes/Document-$format'>
  <rdfs:label>$format Document</rdfs:label>
  <dcterms:format rdf:resource='http://purl.org/xtypes/Format-$format'/>
  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Document'/>
</rdfs:Class>
";
	if( $version != '' )
	{
		$rdf.= " 		
<rdfs:Class rdf:about='http://purl.org/xtypes/Document-$format-$version'>
  <rdfs:label>$format Document (version $version)</rdfs:label>
  <dcterms:format rdf:resource='http://purl.org/xtypes/Format-$format-$version'/>
  <rdfs:subClassOf rdf:resource='http://purl.org/xtypes/Document-$format' />
</rdfs:Class>
";
	}

	$rdf .= render_Format( $format, $version );
	return $rdf;
}

function render_Encoding( $id ) 
{
	$rdf = "";
	$rdf .= "<xtypes:Encoding rdf:about='$base/Encoding-$id'>\n";
	$rdf .= "  <rdfs:label>$id Encoding</rdfs:label>\n";
	$rdf .= "</xtypes:Encoding>\n";
	return $rdf;
}

function render_DocumentEncoded( $id ) 
{
	$rdf = "";
	$rdf .= "<rdfs:Class rdf:about='$base/DocumentEncoded-$id'>\n";
	$rdf .= "  <rdfs:label>Document encoded as $id</rdfs:label>\n";
        $rdf .= "  <xtypes:hasEncoding rdf:resource='$base/Encoding-$id' />\n";
  	$rdf .= "  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Document'/>\n";
	$rdf .= "</rdfs:Class>\n";
	$rdf .= render_Encoding( $id );
	$rdf .= "\n";
	return $rdf;
}
function render_full()
{
global $base;
$rdf = array();
$parents = array();
foreach( formats() as $id=>$line )
{
	list( $parent, $mime, $suffix, $name, $flags ) = preg_split( "/:/", $line );

	$rdf []= "<dcterms:MediaType rdf:about='$base/Format-$id'>\n";
	$rdf []= "  <rdfs:label>$name Format</rdfs:label>\n";
	$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
	if( $id == 'N3' ) { $rdf []= "  <owl:sameAs rdf:resource='http://www.w3.org/ns/formats/N3' />\n"; }
	if( $id == 'NTriples' ) { $rdf []= "  <owl:sameAs rdf:resource='http://www.w3.org/ns/formats/N-Triples' />\n"; }
	if( $id == 'RDFXML' ) { $rdf []= "  <owl:sameAs rdf:resource='http://www.w3.org/ns/formats/RDF_XML' />\n"; }
	if( $id == 'Turtle' ) { $rdf []= "  <owl:sameAs rdf:resource='http://www.w3.org/ns/formats/Turtle' />\n"; }
        $rdf []= "  <rdfs:subClassOf rdf:resource='$base/Format' />";
	if( $suffix != '' ) { $rdf []= "  <xtypes:hasFileExtension>$suffix</xtypes:hasFileExtension>\n"; }
	if( $mime != '' ) { $rdf []= "  <xtypes:hasMimeType>$mime</xtypes:hasMimeType>\n"; }
	if( $parent != '' ) { 
		#$rdf []= "  <rdfs:subClassOf rdf:resource='$base/Format-$parent' />"; 
		$parents[$parent] = 1;
	}
	$rdf []= "</dcterms:MediaType>\n";


	$rdf []= "<rdfs:Class rdf:about='$base/Document-$id'>\n";
	$rdf []= "  <rdfs:label>$name Document</rdfs:label>\n";
	$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
        $rdf []= "  <dcterms:format rdf:resource='$base/Format-$id' />\n";
        $rdf []= "  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Document' />";
	if( $parent == 'Image' )
	{
		$rdf []= "  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Image' />\n";
	}
	elseif( $parent != '' )
	{
		$rdf []= "  <rdfs:subClassOf rdf:resource='$base/Document-$parent' />"; 
	}
	$rdf []= "</rdfs:Class>\n";


	if( $flags != 'nf' )
	{
	$rdf []= "<rdfs:Datatype rdf:about='$base/Fragment-$id'>\n";
	$rdf []= "  <rdfs:label>$name Fragment</rdfs:label>\n";
	$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
        $rdf []= "  <dcterms:format rdf:resource='$base/Format-$id' />\n";
        $rdf []= "  <rdfs:subClassOf rdf:resource='$base/Fragment' />";
	if( $parent != '' ) { 
		$rdf []= "  <rdfs:subClassOf rdf:resource='$base/Fragment-$parent' />"; 
		$fparents[$parent] = 1;
	}
	
	$rdf []= "</rdfs:Datatype>\n";
	}
	$rdf []= "\n";
}

foreach( $parents as $parent=>$dummy )
{
	$rdf []= "<dcterms:MediaType rdf:about='$base/Format-$parent'>\n";
	$rdf []= "  <rdfs:label>$parent Format (Generic)</rdfs:label>\n";
	$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
	$rdf []= "</dcterms:MediaType>\n";

	if( $parent == 'Image' )
	{
		$rdf []= "<rdfs:Class rdf:about='http://xmlns.com/foaf/0.1/Image'>\n";
		$rdf []= "  <rdfs:label>$parent Document (Generic)</rdfs:label>\n";
        	$rdf []= "  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Document' />";
		$rdf []= "</rdfs:Class>\n";
	}
	else
	{
		$rdf []= "<rdfs:Class rdf:about='$base/Document-$parent'>\n";
		$rdf []= "  <rdfs:label>$parent Document (Generic)</rdfs:label>\n";
		$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
        	$rdf []= "  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Document' />";
		$rdf []= "</rdfs:Class>\n";
	}
	if( @$fparents[$parent] )
	{
		$rdf []= "<rdfs:Datatype rdf:about='$base/Fragment-$parent'>\n";
		$rdf []= "  <rdfs:label>$parent Fragment (Generic)</rdfs:label>\n";
		$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
        	$rdf []= "  <rdfs:subClassOf rdf:resource='$base/Fragment' />";
		$rdf []= "</rdfs:Datatype>\n";
	}
	$rdf []= "\n";
}

foreach( encodings() as $id=>$name )
{
	$rdf []= "<xtypes:Encoding rdf:about='$base/Encoding-$id'>\n";
	$rdf []= "  <rdfs:label>$name Encoding</rdfs:label>\n";
	$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
	$rdf []= "</xtypes:Encoding>\n";
	$rdf []= "<rdfs:Class rdf:about='$base/DocumentEncoded-$id'>\n";
	$rdf []= "  <rdfs:label>Document encoded as $name</rdfs:label>\n";
	$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
        $rdf []= "  <xtypes:hasEncoding rdf:resource='$base/Encoding-$id' />\n";
  	$rdf []= "  <rdfs:subClassOf rdf:resource='http://xmlns.com/foaf/0.1/Document'/>\n";
	$rdf []= "</rdfs:Class>\n";
	$rdf []= "\n";
}

$rdf []= "<rdfs:Datatype rdf:about='$base/Fragment'>\n";
$rdf []= "  <rdfs:label>Fragment</rdfs:label>\n";
$rdf []= "  <rdfs:comment>A fragment of a document which may not be a valid document by iteslf.</rdfs:comment>\n";
$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
$rdf []= "</rdfs:Datatype>\n";

$rdf []= "<rdfs:Class rdf:about='http://xmlns.com/foaf/0.1/Document'>\n";
$rdf []= "  <rdfs:label>Document</rdfs:label>\n";
$rdf []= "  <rdfs:isDefinedBy rdf:resource='http://xmlns.com/foaf/0.1/' />\n";
$rdf []= "</rdfs:Class>\n";

$rdf []= "<rdfs:Class rdf:about='$base/Encoding'>\n";
$rdf []= "  <rdfs:label>Encoding Scheme</rdfs:label>\n";
$rdf []= "  <rdfs:comment>A scheme used to encode a series of characters, such as ASCII or UTF-8.</rdfs:comment>\n";
$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
$rdf []= "</rdfs:Class>\n";



$rdf []= "<rdf:Property rdf:about='$base/hasFileExtension'>\n";
$rdf []= "  <rdfs:label>File Extension</rdfs:label>\n";
$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
$rdf []= "  <rdfs:comment>Describes a file extension commonly associated with a format, such as .xml or .zip</rdfs:comment>\n";
$rdf []= "  <rdfs:domain rdf:resource='http://purl.org/dc/terms/MediaType' />\n";
$rdf []= "</rdf:Property>\n";

$rdf []= "<rdf:Property rdf:about='$base/hasMimeType'>\n";
$rdf []= "  <rdfs:label>Mime Type</rdfs:label>\n";
$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
$rdf []= "  <rdfs:comment>Describes MIME type commonly associated with a format, such as image/png or text/html</rdfs:comment>\n";
$rdf []= "  <rdfs:domain rdf:resource='http://purl.org/dc/terms/MediaType' />\n";
$rdf []= "  <rdfs:subPropertyOf rdf:resource='http://purl.org/dc/terms/format' />\n";
$rdf []= "</rdf:Property>\n";

$rdf []= "<rdf:Property rdf:about='$base/hasEncoding'>\n";
$rdf []= "  <rdfs:label>Encoding</rdfs:label>\n";
$rdf []= "  <rdfs:isDefinedBy rdf:resource='$base/' />\n";
$rdf []= "  <rdfs:comment>Links the class of all documents encoded with a scheme to the encoding scheme that they are encoded with. [try saying that three times quickly...] </rdfs:comment>\n";
$rdf []= "</rdf:Property>\n";


return join( '',$rdf );
}

function rdf_footer() { return "</rdf:RDF>\n"; }

function formats() { return array(
"PlainText"=>":text/plain:.txt:Plain Text:",

"JPEG"=>"Image:image/jpeg:.jpg:JPEG Image:nf",
"GIF"=>"Image:image/gif:.gif:GIF Image:nf",
"PNG"=>"Image:image/png:.png:PNG Image:nf",
"PostScript"=>":application/postscript:.ps:Postscript:",
"EPS"=>"::.eps:Encapsulated Postscript:nf",
"PDF"=>":application/pdf:.pdf:PDF:nf",
"SVG"=>":image/svg+xml:.svg:Scalable Vector Graphics:",
"TextArt"=>":text/plain:.txt:Text Art (aka ASCII Art):",

"Word"=>"Office:application/msword:.doc:Microsoft Word:nf",
"PowerPoint"=>"Office:application/vnd.ms-powerpoint:.ppt:Microsoft Powerpoint:nf",
"Excel"=>"Office:application/vnd.ms-excel:.xls:Microsoft Excel:nf",
"WordX"=>"Office:application/vnd.openxmlformats-officedocument.wordprocessingml.document:.doc:Microsoft Word (Open XML):nf",
"PowerPointX"=>"Office:application/vnd.openxmlformats-officedocument.presentationml.presentation:.pptx:Microsoft Powerpoint (Open XML):nf",
"ExcelX"=>"Office:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet:.xlsx:Microsoft Excel (Open XML):nf",

"ZIP"=>"Archive:application/zip:.zip:Zip Archive:nf",
"LHA"=>"Archive::.lha:LHA Archive:nf",
"Tar"=>"Archive:application/x-tar:.tar:Tar Archive:nf",
"TGZ"=>"Archive::.tgz:TGZ Archive:nf",


"RPM"=>"Package::.rpm:Redhat Package Module:nf",
"DBM"=>"Package::.deb:Debian Package:nf",
"DMG"=>"Package::.dmg:OS X Package:nf",

"C"=>"Code::.c:C Code:",
"Java"=>"Code::.java:Java Code:",
"CPP"=>"Code::.cpp:C++ Code:",
"CS"=>"Code::.cs:C# Code:",
"PHP"=>"Code::.php:PHP Code:",
"Scheme"=>"Code:::Scheme Code:",
"Perl"=>"Code::.pl:Perl Code:",
"Python"=>"Code::.py:Python Code:",
"Ruby"=>"Code:::Ruby Code:",
"JavaScript"=>"Code::.js:JavaScript Code:",

"AVI"=>"Video::.avi:AVI Video:nf",
"Real"=>"Video::.rm:Real Media Video:nf",
"MPG"=>"Video::.mpg:MPG Video:nf",
"FlashVideo"=>"Video::.flv:Flash Video:nf",

"RDFXML"=>"RDFSerialisation:application/rdf+xml:.rdf:RDF in XML:",
"N3"=>"RDFSerialisation:text/n3:.n3:RDF in N3:",
"NTriples"=>"RDFSerialisation:text/plain::RDF in Triples:",
"Turtle"=>"RDFSerialisation:text/turtle:.ttl:RDF in Turtle:",

"JSON"=>":application/json:.js:JavaScript Object Notation:",
"JSONP"=>"::.js:JSONP:nf",

"XML"=>":text/xml:.xml:XML:",
"YAML"=>":::YAML Ain't Markup Language:",

"HTML"=>":text/html:.html:HTML:",
"XHTML"=>":text/html:.html:XHTML:",
"MediawikiMarkup"=>":::Mediawiki Markup:",
"CSS"=>":text/css:.css:Cascading Style Sheet:",
"Markdown"=>":text/x-web-markdown:.md:Markdown plain text formatting syntax:",

"KML"=>":application/vnd.google-earth.kml+xml:.kml:KML:",
"MathML"=>":::MathML:",
"BBCode"=>":::Bulletin Board Code:",
"RTF"=>"::.rtf:Rich Text Format:",
"LaTeX"=>":application/x-latex:.tex:LaTeX:",

"iCalendar"=>":text/calendar:.ics:iCalendar:",
"RSS"=>":application/rss+xml::.rss:RSS Feed:",
"Atom"=>":application/atom+xml:.atom:Atom Feed:",

"Endnote"=>":::Endnote:",
"BibTeX"=>"::.bib:BibTeX:",

"WAV"=>"Audio:audio/wav:.wav:WAV Audio:nf",
"MP3"=>"Audio:audio/wav:.wav:MP3 Audio:nf",
"Ogg"=>"Audio:audio/ogg:.ogg:Ogg Vorbis Audio:nf",
"FLAC"=>"Audio:audio/x-flac:.flac:FLAC Audio:nf",
"ISOImage"=>":application/x-iso9660-image:.iso:ISO Disk Image:nf",
); }

function encodings() { 
return array( 
'ASCII' => 'ASCII',
'CP37' => 'CP37',
'CP930' => 'CP930',
'CP1047' => 'CP1047',
'ISO8859-1' => 'ISO 8859-1 Western Europe',
'ISO8859-2' => 'ISO 8859-2 Western and Central Europe',
'ISO8859-3' => 'ISO 8859-3 Western Europe and South European (Turkish, Maltese plus Esperanto)',
'ISO8859-4' => 'ISO 8859-4 Western Europe and Baltic countries (Lithuania, Estonia and Lapp)',
'ISO8859-5' => 'ISO 8859-5 Cyrillic alphabet',
'ISO8859-6' => 'ISO 8859-6 Arabic',
'ISO8859-7' => 'ISO 8859-7 Greek',
'ISO8859-8' => 'ISO 8859-8 Hebrew',
'ISO8859-9' => 'ISO 8859-9 Western Europe with amended Turkish character set',
'ISO8859-10' => 'ISO 8859-10 Western Europe with rationalised character set for Nordic languages, including complete Icelandic set',
'ISO8859-11' => 'ISO 8859-11 Thai',
'ISO8859-13' => 'ISO 8859-13 Baltic languages plus Polish',
'ISO8859-14' => 'ISO 8859-14 Celtic languages (Irish Gaelic, Scottish, Welsh)',
'ISO8859-15' => 'ISO 8859-15 Added the Euro sign and other rationalisations to ISO 8859-1',
'ISO8859-16' => 'ISO 8859-16 Central, Eastern and Southern European languages (Polish, Czech, Slovak, Serbian, Croatian, Slovene, Hungarian, Albanian, Romanian, German, Italian)',
'CP437' => 'CP437',
'CP737' => 'CP737',
'CP850' => 'CP850',
'CP852' => 'CP852',
'CP855' => 'CP855',
'CP857' => 'CP857',
'CP858' => 'CP858',
'CP860' => 'CP860',
'CP861' => 'CP861',
'CP863' => 'CP863',
'CP865' => 'CP865',
'CP866' => 'CP866',
'CP869' => 'CP869',
'Windows-1250' => 'Windows-1250 for Central European languages that use Latin script, (Polish, Czech, Slovak, Hungarian, Slovene, Serbian, Croatian, Romanian and Albanian)',
'Windows-1251' => 'Windows-1251 for Cyrillic alphabets',
'Windows-1252' => 'Windows-1252 for Western languages',
'Windows-1253' => 'Windows-1253 for Greek',
'Windows-1254' => 'Windows-1254 for Turkish',
'Windows-1255' => 'Windows-1255 for Hebrew',
'Windows-1256' => 'Windows-1256 for Arabic',
'Windows-1257' => 'Windows-1257 for Baltic languages',
'Windows-1258' => 'Windows-1258 for Vietnamese',
'MacOSRoman' => 'Mac OS Roman',
'KOI8-R' => 'KOI8-R',
'KOI8-U' => 'KOI8-U',
'KOI7' => 'KOI7',
'MIK' => 'MIK',
'ISCII' => 'ISCII',
'TSCII' => 'TSCII',
'VISCII' => 'VISCII',
'ShiftJIS' => 'Shift JIS',
'EUC-JP' => 'EUC-JP',
'ISO-2022-JP' => 'ISO-2022-JP',
'ShiftJIS-2004' => 'Shift_JIS-2004',
'EUC-JIS-2004' => 'EUC-JIS-2004',
'ISO-2022-JP-2004' => 'ISO-2022-JP-2004',
'GB2312' => 'GB 2312',
'GBK' => 'GBK (Microsoft Code page 936)',
'GB18030' => 'GB 18030',
'KSX1001' => 'KS X 1001 is a Korean double-byte character encoding standard',
'EUC-KR' => 'EUC-KR',
'ISO-2022-KR' => 'ISO-2022-KR',
'UTF-8' => 'UTF-8',
'UTF-16' => 'UTF-16',
'UTF-32' => 'UTF-32',
);
}

function resolve( $url )
{
	$resolver = "http://graphite.ecs.soton.ac.uk/xtypes";
	header( "Location: $resolver$url" );
	exit;
}
function do404()
{
	header( "HTTP/1.0 404 Not Found" );
	print "<p>Not Found</p>";
	exit;
}
