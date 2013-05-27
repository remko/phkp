# [PHKP: A PHP implementation for a HKP keyserver](http://el-tramo.be/software/phkp)


## About

PHKP is an implementation of the 
[OpenPGP HTTP Keyserver Protocol (HKP)](http://ietfreport.isoc.org/all-ids/draft-shaw-openpgp-hkp-00.txt) in PHP.
It allows people to serve a PGP keyserver on most webservers, provided that the
webserver has [GnuPG](http://www.gnupg.org/) and PHP with `exec()` 
enabled. Searching, requesting and
submitting (optional) of keys are all supported.

## Installation

- Copy the `phkp.php` script to your webserver directory. If you can put it
  in the root directory of your web dir, you can rename it to `index.php`.
  If you don't do this, you will need to redirect every request to /pks
  to this script. For example, using Apache rewrite rules:

		RewriteRule ^/pks/(.*)   /phkp.php?/pks/$1

- Modify the values in `index.php` to reflect your settings and create the
  necessary directories


## Usage

Simply point your gpg to the right keyserver and port. For example:

		gpg --keyserver hkp://example.com:80 --search-keys Remko
		gpg --keyserver hkp://example.com:80 --send-keys 8E041080
		gpg --keyserver hkp://example.com:80 --recv-keys 8E041080
	

## Known Issues

- Expiration and revocation is only detected with the english version
  of GnuPG. Other languages will omit this information.


## TODO

- Provide more information for uids in searches
- Return human readable output if 'mr' option is not set
- Make more robust, fool proof and secure
- Better logging
- More graceful calls to GnuPG
- Fine-tune GnuPG options (try to avoid creation of trustdb etc. if possible)
- Look at expiration date computation. Currently has a workaround to avoid
  being one day off.


## Disclaimer

This software is not production-ready. It probably contains
bugs and security leaks. Use at your own risk.

