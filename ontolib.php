<?php

function ontolib_render_html( $ontology, $namespace )
{
	require_once( "arc/ARC2.php" );
	require_once( "Graphite.php" );

	$graph = new Graphite();
	$graph->load( $ontology );

	$thisont = $graph->resource( $namespace );
	$classes = $graph->allOfType( "rdfs:Class", "owl:Class" );
	$datatypes = $graph->allOfType( "rdfs:Datatype" );
	$properties = $graph->allOfType( "rdf:Property", "owl:ObjectProperty" );
	$extclasses = array();

	$classes->uasort( "ontolib_cmp" );
	$datatypes->uasort( "ontolib_cmp" );
	$properties->uasort( "ontolib_cmp" );

	global $ontolib_heat;
	$ontolib_heat = array();
	$classes_ids = array();
	$datatypes_ids = array();
	$properties_ids = array();
	foreach( $properties as $prop )
	{
		$short = ontolib_brutally_shorten( $graph, $prop );
		$ontolib_heat[]= $short;
		$properties_ids []= $short;
		foreach( $prop->all( "rdfs:range", "rdfs:domain" ) as $class )
		{
			$short = ontolib_brutally_shorten( $graph, $class );
			if( strpos( $class->toString(), $namespace ) !== 0 )
			{
				$extclasses[$short] = $class;
			}
			$ontolib_heat[]= $short;
		}
	}

	foreach( $classes as $class )
	{
		foreach( $class->all( 
			"rdfs:subClassOf", 
			"rdfs:-subClassOf", 
			"owl:equivalentClass", 
			"-owl:owl:equivalentClass" ) as $class )
		{ 
			$short = ontolib_brutally_shorten( $graph, $class );
			if( strpos( $class->toString(), $namespace ) !== 0 )
			{
				$extclasses[$short] = $class;
			}
			$ontolib_heat[]= $short;
		}
	}

	foreach( $datatypes as $datatype )
	{
		foreach( $datatype->all( 
			"rdfs:subClassOf", 
			"rdfs:-subClassOf", 
			"owl:equivalentClass", 
			"-owl:owl:equivalentClass" ) as $datatype )
		{ 
			$short = ontolib_brutally_shorten( $graph, $datatype );
			if( strpos( $datatype->toString(), $namespace ) !== 0 )
			{
				$extclasses[$short] = $datatype;
			}
			$ontolib_heat[]= $short;
		}
	}

	foreach( $classes as $class )
	{
		$short = ontolib_brutally_shorten( $graph, $class );
		if( strpos( $class->toString(), $namespace ) !== 0 )
		{
			$extclasses[$short] = $class;
		}
		else
		{
			$classes_ids []= $short;
		}
		$ontolib_heat[]= $short;
	}
	foreach( $datatypes as $class )
	{
		$short = ontolib_brutally_shorten( $graph, $class );
		if( strpos( $class->toString(), $namespace ) !== 0 )
		{
			$extclasses[$short] = $class;
		}
		else
		{
			$datatypes_ids []= $short;
		}
		$ontolib_heat[]= $short;
	}
	ksort( $extclasses );
	usort( $ontolib_heat, "ontolib_lengthcmp" );
	
	
	$html = array();
 	$html []='
<style>
.rdfClass, .rdfProperty {
	border: solid 1px #cccccc;
	padding: 0.5em 1em 0.5em 1em;
	margin-bottom: 2em;
}
.rdfClass h3, .rdfProperty h3 {
	margin: 0em -0.5em 0em -0.5em;
	background-color: #eeeeee;
	padding: 4px;
}
.rdfQuickLinks {
	margin-bottom: 1em;
}
h2 {
	border-top: solid 1px #999999;
	border-bottom: solid 1px #999999;
	padding: 8px;
	margin-top: 4em;
	margin-bottom: 2em;
	text-align: center;
	background-color: #eeeeff;
}
</style>';


	if( $thisont->has( "rdfs:comment" ) )
	{
		$html []= "<p>".preg_replace( "/\n\n/", "</p><p>", ontolib_heat( $thisont->get( "rdfs:comment" )->toString() ) )."</p>";
	}

	$html []= "<p style='font-size:150%'><strong>Namespace:</strong> $namespace</p>";	

	if( sizeof( $classes_ids ) )
	{
		$html []= "<div class='rdfQuickLinks'><strong>Classes:</strong> ".ontolib_heat( ontolib_render_cols( $classes_ids ) )."</div>";
	}
	if( sizeof( $datatypes_ids ) )
	{
		$html []= "<div class='rdfQuickLinks'><strong>Datatypes:</strong> ".ontolib_heat( ontolib_render_cols( $datatypes_ids ) )."</div>";
	}
	if( sizeof( $properties_ids ) )
	{
		$html []= "<div class='rdfQuickLinks'><strong>Properties:</strong> ".ontolib_heat( ontolib_render_cols( $properties_ids ) )."</div>";
	}
	if( sizeof( $extclasses ) )
	{
		$list = array();
		foreach( array_keys($extclasses) as $item ) { $list[]=$item; }
		$html []= "<div class='rdfQuickLinks'><strong>External Classes:</strong> ".ontolib_heat( ontolib_render_cols( $list ) )."</div>";
	}

	$html []= "<ul><li><a href='?format=RDF'>View as RDF</a></li></ul>";

	$ontologies = $graph->allOfType( "owl:Ontology" );
	$graph->ns( "vann", "http://purl.org/vocab/vann/" );

	if( sizeof( $ontologies ) )
	{
		$html []= "<div><strong>Namespaces:</strong></div>";
		$html []= "<table>";
		foreach( $ontologies as $ont )
		{
#<owl:Ontology rdf:about="http://eprints.org/ontology/" vann:preferredNamespaceUri='ep' />
			$html []= "<tr>";
			if( $ont->has( "vann:preferredNamespaceUri" ) )
			{
				$html []= "<td>".$ont->get( "vann:preferredNamespaceUri" )->toString().":</td>";
			}
			$html []= "<td>".$ont->toString()."</td>";

			$html []= "</tr>";
		}
		$html []= "</table>";
	}

	if( $thisont->has( "dct:creator" ) )
	{
		$html []= "<p><strong>Created by:</strong> ".$thisont->all( "dct:creator" )->label()->join( ", " ).".</p>";
	}

	if( sizeof( $classes ))
	{
		$html []= "<h2>Classes</h2>";
		foreach( $classes as $class ) { $html []= ontolib_render_class( $graph, $class ); }
	}

	if( sizeof( $datatypes ))
	{
		$html []= "<h2>Datatypes</h2>";
		foreach( $datatypes as $class )
		{
			$html []= ontolib_render_class( $graph, $class );
		}
	}

	if( sizeof( $properties ) )
	{
		$html []= "<h2>Properties</h2>";
		foreach( $properties as $prop )
		{
			$html []= ontolib_render_property( $graph, $prop );
		}
	}
	
	if( sizeof( $extclasses ) )
	{	
		$html []= "<h2>External Classes</h2>";
		foreach( $extclasses as $class )
		{
			$html []= ontolib_render_class( $graph, $class );
		}
	}

	return join( '', $html );
}
	
	

function ontolib_serve_rdf( $ontology )
{
	header( "content-type: application/rdf+xml");
	readfile( $ontology );
}

function ontolib_expand( $uri, $ontology )
{
	list( $head, $tail ) = split( ":", $uri, 2 );
	if( isset( $ontology["namespaces"][$head] )) { return $ontology["namespaces"][$head].$tail; }
	return $uri;
}


function ontolib_heat( $text )
{
        global $ontolib_heat;
        $reg = "(".join( "|", $ontolib_heat ).")(s?)";
        return preg_replace( "/$reg/", "<a class='crossref' href='#$1'>$1$2</a>", $text );
}

function ontolib_wants()
{
	if( @$_GET["format"] ) { return $_GET["format"]; }
	$accept = $_SERVER["HTTP_ACCEPT"];

	if( strpos( $accept, 'text/html' ) !== false ) { return "HTML"; }
	if( strpos( $accept, 'application/rdf+xml' ) !== false ) { return "RDF"; }

	return "HTML";
}


function ontolib_brutally_shorten( $graph, $uri )
{
	if( !preg_match( '/^(.*[\/#])([^\/#]*)$/', $uri->toString(), $bits ) )
	{
		return $uri;
	}

	if( $graph->resource( $bits[1] )->has( "http://purl.org/vocab/vann/preferredNamespaceUri" ) )
	{
		return $graph->resource( $bits[1] )->get( "http://purl.org/vocab/vann/preferredNamespaceUri" )->toString().":".$bits[2];
	}

	return $bits[2];
}

function ontolib_cmp( $a, $b )
{
	return strcasecmp( $a->toString(), $b->toString() );
}

function ontolib_lengthcmp( $a, $b )
{
	return strlen($b)-strlen($a);
}

function ontolib_render_class( $graph, $class )
{
	$class_name = ontolib_brutally_shorten( $graph, $class );
	$html = array();
	$html []= "<div class='rdfClass'>";
	$html []= "<h3 id='$class_name'>Class: $class_name</h3>";
	$html []= "<div style='font-family:monospace;font-size:80%'>".$class->toString()."</div>";

	if( $class->has( 'rdfs:label' ) )
	{
	 	$html []= "<p>".$class->get("rdfs:label")->toString()."</p>"; 
	}

	if( $class->has( "-rdfs:domain" ) )
	{
		$list = array();
		foreach( $class->all( "-rdfs:domain" ) as $prop )
		{
			$list[]=ontolib_brutally_shorten( $graph, $prop );
		}
	 	$html []= "<p><strong>In domain of:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $class->has( "-rdfs:range" ) )
	{
		$list = array();
		foreach( $class->all( "-rdfs:range" ) as $prop )
		{
			$list[]=ontolib_brutally_shorten( $graph, $prop );
		}
	 	$html []= "<p><strong>In range of:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $class->has( "rdfs:subClassOf" ) )
	{
		$list = array();
		foreach( $class->all( "rdfs:subClassOf" ) as $prop )
		{
			$list[]=ontolib_brutally_shorten( $graph, $prop );
		}
	 	$html []= "<p><strong>Is sub-class of:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $class->has( "-rdfs:subClassOf" ) )
	{
		$list = array();
		foreach( $class->all( "-rdfs:subClassOf" ) as $prop )
		{
			$list[]=ontolib_brutally_shorten( $graph, $prop );
		}
	 	$html []= "<p><strong>Has sub-class:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $class->has( "owl:equivalentClass", "-owl:equivalentClass" ) )
	{
		$list = array();
		foreach( $class->all( "owl:equivalentClass", "-owl:equivalentClass" ) as $prop )
		{
			$list[]=ontolib_brutally_shorten( $graph, $prop );
		}
	 	$html []= "<p><strong>Has equivalent class:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $class->has( "rdfs:comment" ) )
	{ 
		$html []= "<p>".ontolib_heat( $class->get( "rdfs:comment" )->toString() )."</p>"; 
	}
	$html []= "</div>";

	return join( '', $html );
}

function ontolib_render_property( $graph, $prop )
{
	$prop_name = ontolib_brutally_shorten( $graph, $prop );
	$html = array();
	$html []= "<div class='rdfProperty'>";
	$html []= "<h3 id='$prop_name'>Property: $prop_name</h3>";
	$html []= "<div style='font-family:monospace;font-size:80%'>".$prop->toString()."</div>";

	if( $prop->has( 'rdfs:label' ) )
	{
	 	$html []= "<p>".$prop->get("rdfs:label")->toString()."</p>"; 
	}

	if( $prop->has( "rdfs:domain" ) )
	{
		$list = array();
		foreach( $prop->all( "rdfs:domain" ) as $class )
		{
			$list[]=ontolib_brutally_shorten( $graph, $class );
		}
	 	$html []= "<p><strong>Domain:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $prop->has( "rdfs:range" ) )
	{
		$list = array();
		foreach( $prop->all( "rdfs:range" ) as $class )
		{
			$list[]=ontolib_brutally_shorten( $graph, $class );
		}
	 	$html []= "<p><strong>Range:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $prop->has( "rdfs:subPropertyOf" ) )
	{
		$list = array();
		foreach( $prop->all( "rdfs:subPropertyOf" ) as $class )
		{
			$list[]=ontolib_brutally_shorten( $graph, $class );
		}
	 	$html []= "<p><strong>Is sub-property of:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $prop->has( "-rdfs:subPropertyOf" ) )
	{
		$list = array();
		foreach( $prop->all( "-rdfs:subPropertyOf" ) as $class )
		{
			$list[]=ontolib_brutally_shorten( $graph, $class );
		}
	 	$html []= "<p><strong>Has sub-property:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $prop->has( "owl:equivalentProperty", "-owl:equivalentProperty" ) )
	{
		$list = array();
		foreach( $prop->all( "owl:equivalentProperty", "-owl:equivalentProperty" ) as $class )
		{
			$list[]=ontolib_brutally_shorten( $graph, $class );
		}
	 	$html []= "<p><strong>Has equivalent property:</strong> ".ontolib_heat( join( ", ",$list ) ).".</p>"; 
	}

	if( $prop->has( "rdfs:comment" ) )
	{ 
		$html []= "<p>".ontolib_heat( $prop->get( "rdfs:comment" )->toString() )."</p>"; 
	}

	$html []= "</div>";

	return join( '', $html );
}

function ontolib_render_cols( $list )
{
	#if( sizeof( $list ) < 3 ){ return $list->join( ", "); }
	$cols = floor( sizeof( $list ) / 3 );
	if( $cols > 3 ) { $cols = 3; }
	$len = ceil( sizeof( $list ) / 3 );
	$i = 0;
	$r="<table><tr><td style='padding-right:1em' valign='top'>";
	foreach( $list as $item )
	{
		$r.="<li>$item</li>";
		++$i;
		if( $i % $len == 0 ) { $r .= "</td><td style='padding-right:1em' valign='top'>"; }
	}
	$r.="</td></tr></table>";
	return $r;
}
	
