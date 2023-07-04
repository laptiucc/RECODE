<?php
require_once 'includes/main.inc';
header('content-type: application/opensearchdescription+xml; charset=UTF-8');
print '<'.'?xml version="1.0" encoding="UTF-8"?'.">\n";
?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
 <ShortName>Recode2</ShortName>
 <Description>Recode2 database search</Description>
 <Url type="text/html" template="<?php print $config['server']; ?>search?q={searchTerms}"/>
 <Image width="16" height="16" type="image/x-icon">data:image/x-icon;base64,<?php print base64_encode(file_get_contents('favicon.ico')) ?></Image>
 <Developer>Michael Bekaert</Developer>
 <SyndicationRight>open</SyndicationRight>
 <AdultContent>false</AdultContent>
 <Language>en</Language>
 <InputEncoding>UTF-8</InputEncoding>
 <Query role="example" searchTerms="gag" />
</OpenSearchDescription>
