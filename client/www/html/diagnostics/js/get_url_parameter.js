/*
 * Returns value of URL parameter, null if not found.
 */
function get_url_parameter(r,url) {
  url = (!url) ? location.href : url;

  // querystring
  var ix = url.indexOf('?');
  if( ix < 0 ) { 
    return null;
  }
  var q = url.substring(ix+1);

  // remove hash portion
  ix = q.indexOf('#');
  if( ix >= 0 ) { 
    q = q.substring(0,ix);
  }

  // parameters
  var p = q.split("&");
  // loop through parameters
  for(var i=0,n=p.length; i<n; i++) {
    // key=value
    var s = p[i].split("=");
    // if key == requested key
    if( s[0] == r ) {
      // return value
      return s[1];
    }
  }
  return null;
}

