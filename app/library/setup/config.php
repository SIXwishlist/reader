<?php

//--------------------------------------------------
// Encryption key

	define('ENCRYPTION_KEY', 'bUwLgw+Q8NA4mQ==');

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'stage');

		//--------------------------------------------------
		// Database

			$config['db.host'] = 'localhost';
			$config['db.user'] = 'stage';
			$config['db.pass'] = 'st8ge';
			$config['db.name'] = 's-craig-reader';

			$config['db.prefix'] = 'rdr_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'craig@craigfrancis.co.uk';
			$config['email.testing'] = 'craig@craigfrancis.co.uk';
			$config['email.check_domain'] = false;

		//--------------------------------------------------
		// Misc

			$config['gateway.maintenance'] = true;

	} else if (prefix_match('/www/demo/', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

		//--------------------------------------------------
		// Database

			$config['db.host'] = 'localhost';
			$config['db.user'] = 'craig';
			$config['db.pass'] = 'cr8ig';
			$config['db.name'] = 'l-craig-reader';

			$config['db.prefix'] = 'rdr_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'craig@craigfrancis.co.uk';
			$config['email.testing'] = 'craig@craigfrancis.co.uk';
			$config['email.error'] = 'craig@craigfrancis.co.uk';

		//--------------------------------------------------
		// General

			$config['output.protocols'] = array('https');
			$config['output.domain'] = 'reader.craigfrancis.co.uk';

	}

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'Reader';

?>