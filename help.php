<?php
require_once 'includes/main.inc';

htmlheader();
?>
      <p>&nbsp;</p>
      <h2>How to use this database</h2>
      <p>Search is simple: just type whatever comes to mind in the search box, hit Enter or click on the Search button. Most of the time you'll find exactly what you were looking for with just a basic query. However the following tips can help you refine your technique to make the most of your searches.</p>
      <ul>
        <li>Every word matters. Generally, all the words you put in the query will be used.</li>
        <li>Search is always case insensitive. Searching for <code>SARS</code> is the same as searching for <code>sars</code>.</li>
        <li>With some exceptions, punctuation is ignored (that is, you can't search for @#$%^&amp;*()=+[]\ and other special characters).</li>
      </ul>
      <p>The short output contains just the name of the organism, gene and type of recoding. This information for each entry is within one row. In order to get detailed information, the corresponding entry should be clicked. The detailed output contains almost all information stored in the database for each entry. Very often the positions of the <em>cis</em>-elements are very important and so <em>cis</em>-elements are highlighted in the corresponding DNA sequence. The logo of Recode 1 database below can be used as a key for interpretation of highlighting decarations that are used for annotation of stimulatory signals in the database.<br /><img src="<?php print $config['server']; ?>images/recodekey.png" alt="recode key" width="345" height="197" /></p>
      <p>It is difficult to fit all the available information about recoding in this database. More detailed information is available in the primary research papers. Corresponding references are given for each entry with hyper links to <a href="http://www.ncbi.nlm.nih.gov/pubmed/" rel="nofollow">Medline</a> abstracts.</p>
      <h2>Cases that are not indexed</h2>
      <p>Some <a href="http://en.wikipedia.org/wiki/Scientific_paper" rel="nofollow">publications</a> contain description of the recoding events whose existence was not confirmed later. Such cases are not necessarily results of scientific misconduct. Recoding events were mistakenly reported due to misinterpretations, honest errors, etc.</p>
      <p>If you believe that the database is lacking description of certain genes that are known to utilize recoding, please, join our team of Recode contributors by signing in above to obtain Recode account. This would allow you to submit your information directly into the database.</p>
<?php
htmlfooter();
?>
