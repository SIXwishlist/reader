<?php

	class user extends user_base {

		//--------------------------------------------------
		// Setup

			public function __construct() {

				$this->identification_type = 'username';

				$this->setup();

				$this->session->length_set(60*60*24*14);

			}

	}

?>