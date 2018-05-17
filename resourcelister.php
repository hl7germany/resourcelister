<?php

/* 
 * resourcelister
 * 
 * Lists selected FHIR resource types from a FHIR endpoint
 */

// load configuration and initialize
require_once './config.inc.php';
$errors = [];
$outputData = [];


// retrieve data
foreach ($cfgFhirEndpoints as $endpoint) {
	foreach ($cfgResources as $resource) {
		// build URL
		list($url, $isExtension) = buildUrl($endpoint['base'], $resource);

		// connect and get the data
		if (! $ch = curl_init($url)) {
			die("Could not init connection to '$url'!");
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER,[
			'Content-Type: application/fhir+json',
			'Accept: application/fhir+json'
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$jsonData = curl_exec($ch);
		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (200 !== $httpStatus) {
			$errors[] = "$url: HTTP status $httpStatus";
			continue;
		}
		// TODO yes, we could do some more error handling here
		$data = json_decode($jsonData);
		// workaround for Simplifier bug
		if ($isExtension) {
			$data = filterForExtensions($data);
		}
		// diconnect
		curl_close($ch);

		// store relevant data
		foreach ($data->entry as $entry) {
			$outputData[] = [
				'endpoint' => $endpoint,
				'fullUrl' => $entry->fullUrl,
				'resource' => $entry->resource
			];
		}
	}
}


////////////////////////////////////////////////////////////////////////////////


// builds a URL
function buildUrl($base, $resource) {
	$isExtension = false;	// workaround for Simplifier bug
	$url = $base . '/' . $resource['resource'] . '?';

	if ('StructureDefinition' === $resource['resource']) {
		if (! empty($resource['type'])) {
			if ('Extension' === $resource['type']) {
				$isExtension = true;	// workaround for Simplifier bug
			} else {
				$url .= 'type=' . $resource['type'] . '&';
			}
		}
	}

	$url .= '_count=100000';

	return [$url, $isExtension];
}


////////////////////////////////////////////////////////////////////////////////


// filters out extensions from a Bundle of StructureDefinitions
function filterForExtensions($data) {
	foreach ($data->entry as $i => $entry) {
		if ('StructureDefinition' !== $entry->resource->resourceType) {
			unset($data->entry[$i]);
			continue;
		}
		if ('Extension' !== $entry->resource->type) {
			unset($data->entry[$i]);
			continue;
		}
	}
	return $data;
}


////////////////////////////////////////////////////////////////////////////////


// output
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title>resourcelister</title>
		<link rel="stylesheet" href="./resourcelister.css" />
	</head>
	<body>
		<?php if (0 < count($errors)): ?>
			<h1>Errors</h1>
			<ul>
				<?php foreach ($errors as $error): ?>
					<li><?php echo $error; ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<h1>Resources</h1>
		<table>
			<thead>
				<tr>
					<th>title</th>
					<th>canonical URL</th>
					<th>version</th>
					<th>status</th>
					<th>experimental</th>
					<th>fhirVersion</th>
					<th>description</th>
					<th>contextType</th>
					<th>context</th>
					<th>project</th>
					<th>publisher</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($outputData as $cur): ?>
					<tr>
						<td><a href="<?php echo $cur['fullUrl']; ?>"><?php echo $cur['resource']->title; ?></a></td>
						<td><a href="<?php echo $cur['resource']->url; ?>"><?php echo $cur['resource']->url; ?></a></td>
						<td><?php echo $cur['resource']->version; ?></td>
						<td><?php echo $cur['resource']->status; ?></td>
						<td><?php echo $cur['resource']->experimental; ?></td>
						<td><?php echo $cur['resource']->fhirVersion; ?></td>
						<td><?php echo $cur['resource']->description; ?></td>
						<td><?php echo $cur['resource']->contextType; ?></td>
						<td><?php echo implode(', ', $cur['resource']->context); ?></td>
						<td><?php echo $cur['endpoint']['name']; ?></td>
						<td><?php echo $cur['resource']->publisher; ?></td>
					</tr>
				<?php endforeach; ?>				
			</tbody>
		</table>
	</body>
</html>
