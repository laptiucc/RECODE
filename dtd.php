<?php
require_once 'includes/main.inc';

htmlheader();
?>
      <p>&nbsp;</p>
      <h2>RecodeML <small>v. 0.3</small></h2>
      <ul>
        <li><a href="<?php print $config['server'] ?>dtd/recodeml.dtd" type="application/xml-dtd">XML Document Type Definition</a> (DTD) file<br /></li>
        <li><a href="<?php print $config['server'] ?>dtd/recodeml.xsd" type="application/xml">XML Schema</a> (XSD) file</li>
      </ul>
      <h2>RecodeML <small>v. 0.4 (experimental)</small></h2>
      <ul>
        <li><a href="<?php print $config['server'] ?>dtd/recodeml-0.4.dtd" type="application/xml-dtd">XML Document Type Definition</a> (DTD) file<br /></li>
        <li><a href="<?php print $config['server'] ?>dtd/recodeml-0.4.xsd" type="application/xml">XML Schema</a> (XSD) file</li>
      </ul>
      <h3>Associating the DTD with documents</h3>
      <p>A RecodeML DTD is associated with an XML document via a Document Type Definition, which is a tag that appears near the start of the XML document. The declaration establishes that the document is an instance of the type defined by the RecodeML DTD.</p>
      <div class="box"><code><?php print '&lt;!DOCTYPE recodeml PUBLIC "-//Recode//DTD Recodeml 0.3//EN"<br />&nbsp;"'.$config['master'].'dtd/recodeml.dtd"'."&gt;\n"; ?></code></div>
<?php
htmlfooter();
?>
