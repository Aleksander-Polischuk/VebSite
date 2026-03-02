<?php

@define ("HttpRequestParameterAddress", "address");
@define ("HttpRequestParameterContentType", "contentType");
@define ("HttpContentTypeBase64", "X-user/base64-data");

@define ("UseProxy", false);
@define ("ProxyAddress", "");
@define ("ProxyPort", "3128");
@define ("ProxyLoginPassword", "username:password");

const URL_MAX_LENGTH	= 255;
const URL_REG_EX		= "/^(https?:\/\/)?([a-zA-Z0-9.\-]+)(:[0-9]{1,5})?(\/(.*))?$/";

$sKnownHosts = array(
	"czo.gov.ua",
	"zc.bank.gov.ua",
	"acskidd.gov.ua",
	"ca.informjust.ua",
	"csk.uz.gov.ua",
	"masterkey.ua",
	"ocsp.masterkey.ua",
	"tsp.masterkey.ua",
	"csk.uss.gov.ua",
	"csk.ukrsibbank.com",
	"acsk.privatbank.ua",
	"ca.mil.gov.ua",
	"acsk.dpsu.gov.ua",
	"acsk.er.gov.ua",
	"ca.mvs.gov.ua",
	"canbu.bank.gov.ua",
	"uakey.com.ua",
	"altersign.com.ua",
	"ca.altersign.com.ua",
	"ocsp.altersign.com.ua",
	"acsk.treasury.gov.ua",
	"ocsp.treasury.gov.ua",
	"ca.oschadbank.ua",
	"cihsm-api.oschadbank.ua",
	"ca.gp.gov.ua",
	"acsk.oree.com.ua",
	"ca.treasury.gov.ua:43221",
	"ca.treasury.gov.ua:43222",
	"ca.depositsign.com",
	"csk.pivdenny.ua",
	"xca.credit-agricole.com.ua",
	"ca.iit.com.ua",
	"ca.tascombank.com.ua",
	"ca.tascombank.ua",
	"ssign.diia.gov.ua",
	"depositsign.com",
	"pki.pumb.ua",
	"ca.alfabank.kiev.ua",
	"cesaris.itsway.kiev.ua",
	"ca.credit-agricole.ua",
	"csk-corp.aval.ua",
	"ca.e-life.com.ua",
	"ocsp.e-life.com.ua",
	"tsp.e-life.com.ua",
	"cmp.e-life.com.ua",
	"cabinet.e-life.com.ua",
	"ca.bankalliance.ua",
	"ca.vchasno.ua",
	"cs.vchasno.ua",
	"qca.ukrgasbank.com",
	"apiext.pumb.ua",
	"ca.pravex.com.ua",
	"ca.tax.gov.ua",
	"smart-sign.tax.gov.ua",
	"ca.diia.gov.ua",
	"va1-knedp.ssu.gov.ua",
	"vtms-api-csk.ukrgasbank.com",
	"ca.sensebank.com.ua",
	"vtms-api-qca.ukrgasbank.com",
	"cihsm-api.bankalliance.ua",
	"cihsm-api.infotech.gov.ua",
	"cihsm-api.gp.gov.ua",
	"sign.ca.mvs.gov.ua",
	"sid.uakey.com.ua",
	"ca.szru.gov.ua",
	"amokey.com.ua",
	"ca.monobank.ua",
	"sedediatid.mineco.gob.es",
	"trusted-list.lu",
	"ssi.gouv.fr",
	"www.digst.dk",
	"eidas.agid.gov.it",
	"www.gns.gov.pt",
	"files.dcenr.gov.ie",
	"tsl.mca.org.mt",
	"tl.nbu.gov.sk",
	"sr.riik.ee",
	"www.eett.gr",
	"trustedlist.pts.se",
	"www.nccert.pl",
	"www.signatur.rtr.at",
	"www.adr.gov.ro",
	"www.nmhh.hu",
	"elektroninisparasas.lt",
	"tl-norway.no",
	"dp.trustedlist.fi",
	"tsl.belgium.be",
	"www.fjarskiptastofa.is",
	"tl.bundesnetzagentur.de",
	"www.agentschaptelecom.nl",
	"www.llv.li",
	"mingor.gov.hr",
	"crc.bg",
	"www.tl.gov.si",
	"ec.europa.eu",
	"dec.dmrid.gov.cy",
	"trustlist.gov.lv",
	"tsl.gov.cz",
	"www.gov.am",
	"data.gov.ge",
	"aia.sk.ee",
	"ocsp.aped.gov.gr",
	"root-test.czo.gov.ua",
	"ca-test.czo.gov.ua",
	"cihsm-api.gp.gov.ua",
	"cihsm-commiter.gp.gov.ua",
	"smart-sign.dpsu.gov.ua"
);

function is_known_host($value) {
	if ((strlen($value) > URL_MAX_LENGTH) ||
		!preg_match(URL_REG_EX, $value)) {
		return false;
	}

	if (!filter_var($value, FILTER_VALIDATE_URL))
		return false;

	$aUrl = parse_url($value);
	if (is_null($aUrl) or !$aUrl) {
		return false;
	}
	
	if ($aUrl['scheme'] != 'http' && 
		$aUrl['scheme'] != 'https') {
		return false;
	}

	foreach ($GLOBALS["sKnownHosts"] as $sKnownHost) {
		$sKnownHost = explode(":", $sKnownHost);
		switch (count($sKnownHost)) {
			case 1:
				if ($aUrl['host'] == $sKnownHost[0] &&
					($aUrl['port'] == "" ||
						$aUrl['port'] == "80" &&
							$aUrl['scheme'] == 'http' || 
						$aUrl['port'] == "443" && 
							$aUrl['scheme'] == 'https')) {
					return true;
				}
				break;
			case 2:
				if ($aUrl['host'] == $sKnownHost[0] &&
					($aUrl['port'] == $sKnownHost[1])) {
					return true;
				}
				break;
			default:
				break;
		}
	}

	return false;
}

function get_content_type($value) {
	$sPath = parse_url($value, PHP_URL_PATH);
	if (is_null($sPath) or !$sPath)
		return 'text/plain';

	if (strpos($sPath, '/cloud/api/back/') !== false ||
		strpos($sPath, '/ss/') !== false ||
		strpos($sPath, '/api/EDG/Sign') !== false ||
		strpos($sPath, '/smartid/iit/') !== false ||
		strpos($sPath, '/hogsmeade/striga/v1') !== false ||
		strpos($sPath, '/iit-signer/api/v1') !== false) {
		return 'application/json';
	}

	switch ($sPath) {
		case '/services/cmp':
		case '/services/cmp/':
		case '/public/x509/cmp':
		case '/cmp':
		case '/api/PKI/CMP':
			return '';

		case '/services/ocsp':
		case '/services/ocsp/':
		case '/public/ocsp':
		case '/ocsp':
		case '/ocsp-rsa':
		case '/ocsp-ecdsa':
		case '/OCSPsrv/ocsp':
		case '/queries/ocsp/':
		case '/eid2016':
			return 'application/ocsp-request';

		case '/services/tsp':
		case '/services/tsp/':
		case '/services/tsp/dstu':
		case '/services/tsp/dstu/':
		case '/services/tsp/rsa':
		case '/services/tsp/rsa/':
		case '/services/tsp/ecdsa':
		case '/services/tsp/ecdsa/':
		case '/public/tsa':
		case '/public/tsp':
		case '/tsp':
		case '/tsp-rsa':
		case '/tsp-ecdsa':
		case '/TspHTTPServer/tsp':
			return 'application/timestamp-query';

		default:
			return 'text/plain';
	}
}

function convert_bytes($value) {
	if (is_numeric($value)) {
		return $value;
	} else {
		$value_length = strlen($value);
		$qty = substr($value, 0, $value_length - 1);
		$unit = strtolower(substr($value, $value_length - 1 ));
		switch ($unit) {
			case 'k':
				$qty *= 1024;
				break;
			case 'm':
				$qty *= 1024 * 1024;
				break;
			case 'g':
				$qty *= 1024* 1024 * 1024;
				break;
		}
		return $qty;
	}
}

function handle_request($requestMethod) {
	$sAddress = NULL;
	$sContentType = NULL;
	$sContent = NULL;
	$sServerRequest = NULL;
	$sResponse = NULL;

	if ($requestMethod == 'POST') {
		$postMaxSize = convert_bytes(ini_get('post_max_size'));
		if ($_SERVER['CONTENT_LENGTH'] > $postMaxSize) {
			return 'HTTP/1.0 413 Request Entity Too Large';
		}

		$sContent = file_get_contents('php://input');
		$sServerRequest = base64_decode($sContent);
	}

	$sAddress = @$_GET[HttpRequestParameterAddress];
	if(empty($sAddress)) {
		return 'HTTP/1.0 400 Bad Request';
	}

	if (!is_known_host($sAddress)) {
		return 'HTTP/1.0 403 Forbidden';
	}

	if (isset($_GET[HttpRequestParameterContentType])) {
		$sContentType = @$_GET[HttpRequestParameterContentType];

		if ($sContentType != "" &&
			$sContentType != "application/timestamp-query" &&
			$sContentType != "application/ocsp-request" &&
			$sContentType != "application/json") {
			return 'HTTP/1.0 400 Bad Request';
		}
	} else {
		$sContentType = get_content_type($sAddress);
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $sAddress);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
	curl_setopt($ch, CURLOPT_FAILONERROR, 0);
	curl_setopt($ch, CURLOPT_PROTOCOLS, 
		CURLPROTO_HTTP | CURLPROTO_HTTPS);
	curl_setopt($ch, CURLOPT_USERAGENT, "signature.proxy");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);

	if (UseProxy) {
		curl_setopt($ch, CURLOPT_PROXY, ProxyAddress);
		curl_setopt($ch, CURLOPT_PROXYPORT, ProxyPort);
		curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, ProxyLoginPassword);
	}

	$headers = array(
		'Content-Type: '.$sContentType
	);

	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true); // Set only for PHP = 5.5
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$sServerRequest);

	$response = curl_exec($ch);

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = explode(PHP_EOL,trim(substr($response, 0, $header_size)));
	$sResponse = substr($response, $header_size);

	if (!isset($sResponse) || $sResponse=='') {
		return 'HTTP/1.0 500 Internal Server Error';
	}

	header("Content-type: " . HttpContentTypeBase64 . "; charset=utf-8");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	echo base64_encode($sResponse);

	return '';
}

$sStatus = NULL;

try {
	$sRequestMethod = $_SERVER['REQUEST_METHOD'];
	if ($sRequestMethod == 'GET' || $sRequestMethod == 'POST') {
		$sStatus = handle_request($sRequestMethod);
	} else {
		$sStatus = 'HTTP/1.0 400 Bad Request';
	}
} catch (Exception $e) {
	$sStatus = 'HTTP/1.0 500 Internal Server Error';
	echo $e;
}

if ($sStatus != '') {
	header('Content-Type: text/html; charset=utf-8');
	header($sStatus);
	echo 'Виникла помилка при обробці запиту';
	exit;
}

?>