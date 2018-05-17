<?php

/* 
 * resourcelister configuration file
 */

// FHIR endpoints to query
$cfgFhirEndpoints = [
	[
		'base' => 'https://stu3.simplifier.net/BasisprofilDE',
		'name' => 'Deutsche Basisprofile'
	],
	// Nictiz for testing only
	[
		'base' => 'https://stu3.simplifier.net/NictizSTU3',
		'name' => 'Nictiz (STU3)'
	]
];

// Resource types to query for
$cfgResources = [
	[
		'resource' => 'StructureDefinition',
		'type' => 'Extension'
	]
];
