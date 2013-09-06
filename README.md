Module that will create links to merged CSS/JS files based on md5 hash of file modification dates

This module is intended to be used with any CDN, as it prevents caching of JS/CSS old versions, i.e. as soon as any CSS or JS file is changed, hash (and link to merged file) is changed and CDN will fetch it once more.

Also, this module handle specifically data:image links in CSS - not adding absolute path to url like that
