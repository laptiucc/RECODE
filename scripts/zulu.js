$(document).ready(function() {
  $("a.zulu").each(function(){
   coded = this.rev.replace('@','@');
   key = "8BPWCdoJnZvK2X_lSx79t@1rQYIE4.L0y5pAwuOVi3qefhDbRUNHGMmkjacFgz6Ts";
   shift=coded.length;
   link="";
   for (i=0; i<coded.length; i++) {
    if (key.indexOf(coded.charAt(i))==-1) {
      ltr = coded.charAt(i);
      link += (ltr);
    }
    else {     
      ltr = (key.indexOf(coded.charAt(i))-shift+key.length) % key.length;
      link += (key.charAt(ltr));
    }
   }
	 this.href = 'mailto:' + link;
	});
});