<?php

require_once("boot.php");

$a = new App;

@include(".htconfig.php");
require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once("session.php");
require_once("datetime.php");

// FIXME - generalise for other content, probably create a notify queue in 
// the db with type and recipient list

if($argc < 3)
	exit;

	$baseurl = trim(hex2bin($argv[1]));

	$cmd = $argv[2];

	switch($cmd) {

		default:
			$item_id = intval($argv[3]);
			if(! $item_id)
				killme();
			break;
	}


	$recipients = array();

	// find ancestors

	$r = q("SELECT `parent`, `uid`, `edited` FROM `item` WHERE `id` = %d LIMIT 1",
		intval($item_id)
	);
	if(! count($r))
		killme();

	$parent = $r[0]['parent'];
	$uid = $r[0]['uid'];
	$updated = $r[0]['edited'];

	$items = q("SELECT * FROM `item` WHERE `parent` = %d ORDER BY `id` ASC",
		intval($parent)
	);

	if(! count($items))
		killme();

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($uid)
	);

	if(count($r))
		$owner = $r[0];
	else
		killme();


	require_once('include/group.php');

	$parent = $items[0];

	if(strlen($parent['remote-id'])) {
		$followup = true;
		$conversant_str = dbesc($parent['contact-id']);
	}
	else {
		$followup = false;

		$allow_people = expand_acl($parent['allow_cid']);
		$allow_groups = expand_groups(expand_acl($parent['allow_gid']));
		$deny_people = expand_acl($parent['deny_cid']);
		$deny_groups = expand_groups(expand_acl($parent['deny_gid']));

		$conversants = array();

		foreach($items as $item) {
			$recipients[] = $item['contact-id'];
			$conversants[] = $item['contact-id'];
		}

		$conversants = array_unique($conversants,SORT_NUMERIC);


		$recipients = array_unique(array_merge($recipients,$allow_people,$allow_groups),SORT_NUMERIC);
		$deny = array_unique(array_merge($deny_people,$deny_groups),SORT_NUMERIC);
		$recipients = array_diff($recipients,$deny);
	
		$conversant_str = dbesc(implode(', ',$conversants));
	}

	$r = q("SELECT * FROM `contact` WHERE `id` IN ( $conversant_str ) ");

	if( ! count($r))
		killme();

	$contacts = $r;


	$feed_template = file_get_contents('view/atom_feed.tpl');
	$tomb_template = file_get_contents('view/atom_tomb.tpl');
	$item_template = file_get_contents('view/atom_item.tpl');
	$cmnt_template = file_get_contents('view/atom_cmnt.tpl');

	$atom = '';


	$atom .= replace_macros($feed_template, array(
			'$feed_id' => xmlify($baseurl),
			'$feed_title' => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , 'Y-m-d\Th:i:s\Z')) ,
			'$name' => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$thumb' => xmlify($owner['thumb'])
	));

	if($followup) {
		$atom .= replace_macros($cmnt_template, array(
			'$name' => xmlify($contact['name']),
			'$profile_page' => xmlify($contact['url']),
			'$thumb' => xmlify($contact['thumb']),
			'$item_id' => xmlify("urn:X-dfrn:{$item['hash']}"),
			'$title' => xmlify($item['title']),
			'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\Th:i:s\Z')),
			'$content' =>xmlify($item['body']),
			'$parent_id' => xmlify("{$items[0]['remote-id']}")
		));
	}
	else {
		foreach($items as $item) {
			if($item['deleted']) {
				$atom .= replace_macros($tomb_template, array(
					'$id' => xmlify("urn:X-dfrn:{$item['hash']}"),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\Th:i:s\Z'))
				));
			}
			else {
				foreach($contacts as $contact) {
					if($item['contact-id'] == $contact['id']) {
						if($item['parent'] == $item['id']) {
							$atom .= replace_macros($item_template, array(
								'$name' => xmlify($contact['name']),
								'$profile_page' => xmlify($contact['url']),
								'$thumb' => xmlify($contact['thumb']),
								'$item_id' => xmlify("urn:X-dfrn:{$item['hash']}"),
								'$title' => xmlify($item['title']),
								'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\Th:i:s\Z')),
								'$content' =>xmlify($item['body'])
							));
						}
						else {
							$atom .= replace_macros($cmnt_template, array(
								'$name' => xmlify($contact['name']),
								'$profile_page' => xmlify($contact['url']),
								'$thumb' => xmlify($contact['thumb']),
								'$item_id' => xmlify("urn:X-dfrn:{$item['hash']}"),
								'$title' => xmlify($item['title']),
								'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\Th:i:s\Z')),
								'$content' =>xmlify($item['body']),
								'$parent_id' => xmlify("urn:X-dfrn:{$items[0]['hash']}")
							));
						}
					}
				}
			}
		}
	}
	$atom .= "</feed>";

print_r($atom);


dbg(3);



print_r($recipients);

	if($followup)
		$recip_str = $parent['contact-id'];
	else
		$recip_str = implode(', ', $recipients);

	$r = q("SELECT * FROM `contact` WHERE `id` IN ( %s ) ",
		dbesc($recip_str)
	);
	if(! count($r))
		killme();

	// delivery loop

	foreach($r as $rr) {
		if($rr['self'])
			continue;

		if(! strlen($rr['dfrn-id']))
			continue;
		$url = $rr['notify'] . '?dfrn_id=' . $rr['dfrn-id'];
print_r($url);
		$xml = fetch_url($url);
echo $xml;

print_r($xml);
		if(! $xml)
			continue;

		$res = simplexml_load_string($xml);
print_r($res);
var_dump($res);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || ($res->dfrn_id != $rr['dfrn-id']))
			continue;

		$postvars = array();

		$postvars['dfrn_id'] = $rr['dfrn-id'];
		$challenge = hex2bin($res->challenge);
echo "dfrn-id:" . $res->dfrn_id . "\r\n";
echo "challenge:" . $res->challenge . "\r\n";
echo "pubkey:" . $rr['pubkey'] . "\r\n";

		openssl_public_decrypt($challenge,$postvars['challenge'],$rr['pubkey']);

		$postvars['data'] = $atom;

print_r($postvars);
		$xml = post_url($url,$postvars);

print_r($xml);				
	}

	killme();

