<?php
require_once 'includes/main.inc';

function WSPseudoViewer($sequence, $bracket, $start=1, $name='recode') {
	global $config;
	$socket = fsockopen('165.246.44.42', 80, $errno, $errstr, 10);
	if ($socket) {
		$buffer='';
		$envelop = "POST /WSPseudoViewer/WSPseudoViewer.asmx?WSDL HTTP/1.0\nHost: 165.246.44.42\nPowered by: " . $config['powered'] . "\nNode: " . $config['node'] . "\nContent-Type: text/xml; charset=ISO-8859-1\nSOAPAction: \"http://wilab.inha.ac.kr/WSPseudoViewer/WSPVRun\"\nContent-Length: ";
		$body = '<'.'?xml version="1.0" encoding="utf-8"?'.'><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><WSPVRun xmlns="http://wilab.inha.ac.kr/WSPseudoViewer/"><WSPVRequest xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://wilab.inha.ac.kr/WSPseudoViewer/"><WSPVIn_data><Sequence><Sequence_data Name="'.$name.'" Start_Base="'.$start.'">'.$sequence.'</Sequence_data></Sequence><Bracket_view>'.$bracket.'</Bracket_view></WSPVIn_data></WSPVRequest></WSPVRun></soap:Body></soap:Envelope>';
		fwrite($socket, $envelop . strlen($body). "\n\n" . $body);
		while (!feof($socket)) {
			$buffer.=fgets($socket, 128);
		}
		fclose($socket);
		if ( (strpos($buffer, 'faultcode')===false) && (preg_match('@WSPVOutput width="(\d+)" height="(\d+)".*<WSPVOut_URL format="\w+">(http://.*)</WSPVOut_URL>@ims', $buffer, $matches))) {
			return array($matches[3], $matches[1], $matches[2]);
		}
	}
}

$map=array(0=>array(0=>'(', 1=>')'), 1=>array(0=>'[', 1=>']'), 2=>array(0=>'{', 1=>'}'));

if (!empty($_GET['rna']) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	$id_morphorna=urldecode($_GET['rna']);
	$result=@sql_query('SELECT a.id, a.id_recode, a.structure, a.description, b.sequence FROM morphorna AS a, molecules AS b WHERE a.id='.sql_escape_string($id_morphorna).' AND a.structure>=3 AND a.id_molecule=b.id;', $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
		$row=sql_fetch_array($result);
		$genome=bzdecompress(base64_decode($row['sequence']));
		$sequence='';
		$structure='';
		preg_match("/^(\d+)/", $row['description'], $sstart);
		if ($row['structure'] <6) {
			$start=10000000000;
			$end=0;
			$loop=0;
			foreach ( explode(';', $row['description']) as $stem) {
				$flag=0;
				foreach (explode(',', $stem) as $strand) {
					$coord=explode('|', $strand, 3);
					$start=min($start, $coord[0]);
					$end=max($end, $coord[1]);
					for ($i=$coord[0];$i<=$coord[1];$i++) { $rna[$i] = $map[$loop][$flag]; }
					if ($flag) { $flag=0; } else { $flag=1; }
				}
				$loop++;
			}
			for ($i=$start;$i<=$end;$i++) { $structure.= (empty($rna[$i])?'.':$rna[$i]); }
			$sequence=substr($genome, $start-1, strlen($structure));
		} else {
			$coord=explode('|', $row['description'], 4);
			$sequence=substr($genome, $coord[0]-1, $coord[2]);
			$structure=$coord[3];
		}
		$sequence=str_replace('T', 'U', $sequence);
		if (($picture=WSPseudoViewer($sequence, $structure, $sstart[1], $row['id_recode'].'.'.$row['id']))!==false) {
			header('Location: ' . $picture[0]);
			exit(0);
		}
	}
}
header('HTTP/1.0 404 Not Found');
?>
