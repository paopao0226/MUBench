<?php
// Routes
function dump($var){
	ob_start();
	var_dump($var);
	return ob_get_clean();
}

$app->get('/', function ($request, $response, $args) use ($settings) {
    return $this->renderer->render($response, 'index.phtml', array('experiments' => $settings['ex_template']));
});

$app->get('/impressum/', function ($request, $response, $args) use ($app) {
    return $this->renderer->render($response, 'impressum.html');
});

$app->get('/dataset/[{prefix}]', function ($request, $response, $args) use ($app, $settings) {
	$prefix = $args['prefix'];
	$data = $app->data->getDatasets($prefix);
	$template = $settings['ex_template'][split('[_]', $prefix)[0]];
    return $this->renderer->render($response, 'dataset.phtml', array('data' => $data, 'id' => $template['id'], 'title' => $template['title'], 'prefix' => $prefix));
});

$app->get('/experiment/[{prefix}]', function ($request, $response, $args) use ($app, $settings) {
	$prefix = $args['prefix'];
	$data = $app->data->getDetectors($prefix);
	$template = $settings['ex_template'][split('[_]', $prefix)[0]];
    return $this->renderer->render($response, 'experiment.phtml', array('data' => $data, 'id' => $template['id'], 'title' => $template['title'], 'prefix' => $prefix));
});

$app->get('/detect/[{detector}]', function ($request, $response, $args) use ($app) {
	$arr = split('[_]', $args['detector']);
	$exp = $arr[0];
	$detector = $arr[2];

	if(count($arr) != 3 || !($exp === "ex1" || $exp === "ex2" || $exp === "ex3") || $detector == ""){
		return;
	}
	$data = $app->data->getPotentialHitsIndex($args['detector']);
	$this->logger->info(dump($data));
	return $this->renderer->render($response, 'detector.phtml', array('exp' => $exp, 'identifier' => $args['detector'], 'detector' => $detector,'projects' => $data));
});

$app->get('/ex1/review/[{misuse}]', function ($request, $response, $args) use ($app) {
	$arr = split('[_]', $args['misuse']);
	$set = $arr[1];
	$detector = $arr[2];
	$project = $arr[3];
	$version = $arr[4];
	$misuse = $arr[5];
	if(count($arr) != 6  || $detector == ""){
		return;
	}
	$data = $app->data->getMetadata($misuse);
	$patterns = $app->data->getPatterns($misuse);
	$hits = $app->data->getHits("ex1_" . $set . "_" . $detector, $project, $version, $misuse);
	$this->logger->info(dump($patterns));
	return $this->renderer->render($response, 'review_ex1.phtml', array('detector' => $detector, 'version' => $version, 'project' => $project, 'misuse' => $misuse, 'desc' => $data['description'], 'fix_desc' => $data['fix_description'], 'diff_url' => $data['diff_url'], 'violation_types' => $data['violation_types'], 'file' => $data['file'], 'method' => $data['method'], 'code' => $hits[0]['target_snippets'], 'line' => $hits[0]['line'], 'pattern_code' => $patterns['code'], 'pattern_line' => $patterns['line'], 'pattern_name' => $patterns['name'], 'hits' => []));
});

$app->get('/ex2/review/[{misuse}]', function ($request, $response, $args) use ($app) {
	$arr = split('[_]', $args['misuse']);
	$set = $arr[1];
	$detector = $arr[2];
	$project = $arr[3];
	$version = $arr[4];
	$misuse = $arr[5];
	if(count($arr) != 6  || $detector == ""){
		return;
	}
	$data = $app->data->getMetadata($misuse);
	$patterns = $app->data->getPatterns($misuse);
	$hits = $app->data->getHits("ex2_" . $set . "_" . $detector, $project, $version, $misuse);
	$this->logger->info(dump($patterns));
	return $this->renderer->render($response, 'review_ex1.phtml', array('detector' => $detector, 'version' => $version, 'project' => $project, 'misuse' => $misuse, 'desc' => $data['description'], 'fix_desc' => $data['fix_description'], 'diff_url' => $data['diff_url'], 'violation_types' => $data['violation_types'], 'file' => $data['file'], 'method' => $data['method'], 'code' => $hits[0]['target_snippets'], 'line' => $hits[0]['line'], 'pattern_code' => $patterns['code'], 'pattern_line' => $patterns['line'], 'pattern_name' => $patterns['name'], 'hits' => []));
});

$app->get('/ex3/review/[{misuse}]', function ($request, $response, $args) use ($app) {
	$arr = split('[_]', $args['misuse']);
	$set = $arr[1];
	$detector = $arr[2];
	$project = $arr[3];
	$version = $arr[4];
	$misuse = $arr[5];
	if(count($arr) != 6  || $detector == ""){
		return;
	}
	$data = $app->data->getMetadata($misuse);
	$patterns = $app->data->getPatterns($misuse);
	$hits = $app->data->getHits("ex3_" . $set . "_" . $detector, $project, $version, $misuse);
	return $this->renderer->render($response, 'review_ex1.phtml', array('detector' => $detector, 'version' => $version, 'project' => $project, 'misuse' => $misuse, 'desc' => $data['description'], 'fix_desc' => $data['fix_description'], 'diff_url' => $data['diff_url'], 'violation_types' => $data['violation_types'], 'file' => $data['file'], 'method' => $data['method'], 'code' => $hits[0]['target_snippets'], 'line' => $hits[0]['line'], 'pattern_code' => $patterns['code'], 'pattern_line' => $patterns['line'], 'pattern_name' => $patterns['name'], 'hits' => []));
});

$app->post('/api/upload/[{experiment:ex[1-3]}]', function ($request, $response, $args) use ($app) {
	$obj = json_decode($_POST['data']);
	$experiment = $args['experiment'];
	if($obj){
		$app->upload->handleData($experiment, $obj, $obj->{'potential_hits'});
	}
	foreach($request->getUploadedFiles() as $img){
		$app->dir->handleImage($experiment, $obj->{'project'}, $obj->{'version'}, $img);
	}
});

$app->post('/api/upload/metadata', function ($request, $response, $args) use ($app) {
	$this->logger->info("Hallo");
	$obj = json_decode($request->getBody());
	$this->logger->info(dump($obj));
	foreach($obj as $o){
		$app->upload->handleMetaData($o);
	}
});