<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']) && strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'xh')) $debug=get_switch('debug');

htmlheader(true);
?>
      <p>&nbsp;</p>
      <h2>About</h2>
      <h3>What is Recode<sup class="r2">2</sup></h3>
      <p>
        <strong>Recode<sup class="r2">2</sup></strong> is a <a href="http://en.wikipedia.org/wiki/Database" rel="nofollow">database</a> of <a href="http://en.wikipedia.org/wiki/Gene" rel="nofollow">genes</a> that utilize non-standard  <a href="http://en.wikipedia.org/wiki/Translation_%28genetics%29" rel="nofollow">translation</a> for gene <a href="http://en.wikipedia.org/wiki/Expression_(genetics)" rel="nofollow">expression</a> purposes.  Recoding events described in the database include programmed <a href="http://en.wikipedia.org/wiki/Ribosome" rel="nofollow">ribosomal</a> frameshifting, <a href="http://en.wikipedia.org/wiki/Translation_%28genetics%29" rel="nofollow">translational</a> bypassing (aka hopping) and <a href="http://en.wikipedia.org/wiki/Messenger_RNA">mRNA</a> specific <a href="http://en.wikipedia.org/wiki/Codon" rel="nofollow">codon</a> redefinition. <strong>Frameshifting</strong> at a particular site often yields two <a href="http://en.wikipedia.org/wiki/Protein" rel="nofollow">protein</a> products from one <a href="http://en.wikipedia.org/wiki/Reading_frame" rel="nofollow">coding sequence</a> and sometimes serves a regulatory purpose by acting as a sensor of the level of product protein or of some external <a href="http://en.wikipedia.org/wiki/Ligand" rel="nofollow">ligand</a>. <strong>Bypassing (hopping)</strong> allows the coupling of two <a href="http://en.wikipedia.org/wiki/ORF" rel="nofollow">ORFs</a> separated on an mRNA by a coding gap. <strong>Codon redefinition</strong> occurs when a stop codon is decoded as a standard <a href="http://en.wikipedia.org/wiki/Amino_acid" rel="nofollow">amino acid</a> (often <a href="http://en.wikipedia.org/wiki/Glutamine" rel="nofollow">glutamine</a> or <a href="http://en.wikipedia.org/wiki/Tryptophan" rel="nofollow">tryptophan</a>), or the 21<sup>st</sup> amino acid <a href="http://en.wikipedia.org/wiki/Selenocysteine" rel="nofollow">selenocysteine</a>. <br />These recoding events are in competition with standard decoding and are site specific. The efficiency of recoding is often modulated by <em>cis</em>-stimulators and sometimes by <em>trans</em>-factors. The sequences of the genes that use recoding for their expression are in the database. The recoding sites and the known stimulatory signals are annotated in the database together with notes on factors that are known to affect recoding efficiencies.<br />
        <strong>Recode<sup class="r2">2</sup></strong> does not yet describe certain newly discovered and poorly understood non-standard translation events, such as incorporation of the 22<sup>nd</sup> amino acid <a href="http://en.wikipedia.org/wiki/Pyrolysine" rel="nofollow">pyrrolysine</a> and <a href="http://dx.doi.org/10.1261/rna.487907" rel="nofollow">StopGo phenomenon</a>.<br /><strong>Recode<sup class="r2">2</sup></strong> database provides no information on non-standard decoding during gene expression levels other than translation, e.g. due to <a href="http://en.wikipedia.org/wiki/Rna_editing" rel="nofollow">RNA editing</a>.
      </p>
<?php
if (count($mirror) > 1) {
  print "      <h3>Mirrors</h3>\n      <p>";
  foreach ($mirror as $key => $value) {
    print '<a href="' . $value['url'] . '?mirror=' . $config['mirror'] . '" class="licence"><img src="' . $config['server'] . 'images/' . $key . '.png" alt="' . $value['name'] . ' mirror" width="50" height="25"
/></a>';
    $name[]='<a href="' . $value['url'] . '?mirror=' . $config['mirror'] . '">' . $value['name'] . ' mirror</a>';
  }
  print '<strong>Recode<sup class="r2">2</sup> mirrors: '. join(', ',$name).'</strong>' . (!empty($mirror[$config['mirror']]['thanks'])? '<br />'.$mirror[$config['mirror']]['thanks'] : '') . ".</p>\n";
}
?>
      <h3>Development</h3>
      <p id="development"><img src="<?php print $config['server']; ?>images/recode2.png" alt="recode2" width="80" height="15" /><br />Recode<sup class="r2">2</sup> was developed by efforts of <a href="#" class="zulu" rel="nofollow" rev="J8znvz3OVXJvc5bKQJ">Micha&euml;l Bekaert</a> and <a href="#" class="zulu" rel="nofollow" rev="NhTnVnQ1IuMZZhjF">Pasha V Baranov</a>.</p>
      <h3>Recode<sup class="r2">2</sup> Logo</h3>
      <p>The new <a href="http://xa0tik.deviantart.com/art/Recode-logo-2-104170428" rel="nofollow">recode logo</a> is a courtesy of <a href="http://xa0tik.deviantart.com/" rel="nofollow">Xa0tiK</a> from <a href="http://www.deviantart.com/" rel="nofollow">DeviantArt</a>.</p>
<?php
if (isset($debug) && $debug) {
?>
      <h3>Standards Framework</h3>
      <p>
        <img src="<?php print $config['server']; ?>images/about/xhtml11.png" alt="XHTML 1.1 valid" />&nbsp;Conform to the <abbr title="Extensible HyperText Markup Language">XHTML</abbr> 1.1 standard recommended by the <abbr title="World Wide Web Consortium">W3C</abbr><br />
        <img src="<?php print $config['server']; ?>images/about/css.png" alt="CSS 2.1 valid" />&nbsp;Conform to the <abbr title="Cascading Style Sheets">CSS</abbr> 2.1 standard recommended by the <abbr title="World Wide Web Consortium">W3C</abbr><br />
        <img src="<?php print $config['server']; ?>images/about/waiaaa.png" alt="WAI-Triple A valid" />&nbsp;Conform to the <abbr title="Web Accessibility Initiative">WAI</abbr>-Triple A 1.0 standard recommended by the <abbr title="World Wide Web Consortium">W3C</abbr><br />
        <img src="<?php print $config['server']; ?>images/about/p3p.png" alt="P3P policy valid" />&nbsp;Conform to the <abbr title="Privacy Preferences Project">P3P</abbr> standard<br />
        <img src="<?php print $config['server']; ?>images/about/xmlfeed.png" alt="OpenSearch 1.1 valid" />&nbsp;Conform to the OpenSearch 1.1 standard<br />
        <img src="<?php print $config['server']; ?>images/about/browsers.png" alt="All browers valid" />&nbsp;Optimised for all browsers
      </p>
      <h3>Implementation</h3>
      <p>
<?php
	if ($config['sqlreplica'] == 'master') {
		print '        <img src="' . $config['server'] . 'images/about/master.png" class="licence" alt="Master" width="32" height="32" />'."\n";
	}elseif ($config['sqlreplica'] == 'slave') {
		print '        <img src="' . $config['server'] . 'images/about/slave.png" class="licence" alt="Slave" width="32" height="32" />'."\n";
	}elseif ($config['sqlreplica'] == 'sync') {
		print '        <img src="' . $config['server'] . 'images/about/sync.png" class="licence" alt="Full Synchronisation" width="32" height="32" />'."\n";
	}else {
		print '        <img src="' . $config['server'] . 'images/about/standalone.png" class="licence" alt="Standalone" width="32" height="32" />'."\n";
  }
	if ($config['sqlserver'] == 'postgresql') {
		print '        <img src="' . $config['server'] . 'images/about/pgsql.png" alt="PostgreSQL" width="80" height="15" />&nbsp;Site developed using PostgreSQL<br />'."\n";
	}elseif ($config['sqlserver'] == 'mysql' || $config['sqlserver'] == 'mysqli') {
		print '        <img src="' . $config['server'] . 'images/about/mysql.png" alt="MySQL" width="80" height="15" />&nbsp;Site developed using MySQL<br />'."\n";
	}
?>
        <img src="<?php print $config['server']; ?>images/about/php.png" alt="PHP" />&nbsp;Site developed using <abbr title="recursive acronym for PHP: Hypertext Preprocessor">PHP</abbr>
      </p>
      <h3>Licence</h3>
      <p>
        <img src="<?php print $config['server']; ?>images/about/cc.png" alt="Creative common" width="80" height="15" />&nbsp;This work is licenced under a Creative Commons Licence
      </p>
<?php } ?>
      <h3>How to contact us</h3>
      <p>If you have any questions please email <a href="#" class="zulu" rel="nofollow" rev="NhTnVnQ1IuMZZhjF">Pasha V Baranov</a> or contact:<br /><br/>Dr. Pavel V Baranov<br/><a href="http://lapti.ucc.ie" rel="nofollow">LAPTI</a>, Department of Biochemistry<br/>University College Cork<br/>Cork<br />Ireland<br /><br/><a href="<?php print $config['server']; ?>recode2.kmz" type="application/vnd.google-earth.kmz"><img src="<?php print $config['server']; ?>/images/download_kml.png" width="93" height="25" alt="Google Earth Recode server location" /></a></p>
      <h3>Financial support</h3>
      <p><img src="<?php print $config['server']; ?>images/sfi.png" alt="SFI" width="100" height="77" /><br /><strong>Recode<sup class="r2">2</sup></strong> development has been supported by <a href="http://www.sfi.ie" rel="nofollow">SFI</a> grants to Pavel V Baranov and John F Atkins</p>
<?php
htmlfooter();
?>
