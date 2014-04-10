<?php

	class user_login_unit extends unit {

		protected $config = array(
				'helper' => NULL,
				'dest_url' => NULL,
			);

		protected function setup($config) {

			//--------------------------------------------------
			// Already logged in

				if ($config['helper']->id_get() > 0) {

					redirect($config['dest_url']);

				}

			//--------------------------------------------------
			// Login form

				//--------------------------------------------------
				// Form setup

					$form = $config['helper']->form_get();
					$form->form_button_set('Login');
					$form->form_class_set('basic_form');
					$form->autofocus_set(true);

					$field_username = $form->field_get('identification');
					$field_password = $form->field_get('password');

				//--------------------------------------------------
				// Form submitted

					if ($form->submitted()) {

						$result = $config['helper']->login();

						if ($result) {

							//--------------------------------------------------
							// Try to restore saved forms

								save_request_restore($field_username->value_get());

							//--------------------------------------------------
							// Next page

								$form->dest_redirect($config['dest_url']);

						}

					}

				//--------------------------------------------------
				// Form defaults

					if ($form->initial()) {

						$config['helper']->populate_login();

					}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

		}

	}

?>
