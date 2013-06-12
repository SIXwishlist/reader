<?php

	class article_list_read_unit extends unit {

		public function setup($config = array()) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'source' => NULL,
						'read' => false,
					), $config);

			//--------------------------------------------------
			// Articles

				$db = db_get();

				$articles = array();

				$sql = 'SELECT
							sa.id,
							sa.title,
							s.ref AS source_ref,
							s.title AS source_title
						FROM
							' . DB_PREFIX . 'source_article AS sa
						LEFT JOIN
							' . DB_PREFIX . 'source_article_read AS sar ON sar.article_id = sa.id AND sar.user_id = "' . $db->escape(USER_ID) . '"
						LEFT JOIN
							' . DB_PREFIX . 'source AS s ON s.id = sa.source_id
						WHERE
							sar.article_id IS NOT NULL AND
							s.deleted = "0000-00-00 00:00:00"
						GROUP BY
							sa.id
						ORDER BY
							sar.read_date DESC
						LIMIT
							50';

				foreach ($db->fetch_all($sql) as $row) {

					$articles[] = array(
							'url' => url('/articles/:source/', array('source' => $row['source_ref'], 'id' => $row['id'])),
							'name' => $row['title'],
							'source' => $row['source_title'],
						);

				}

			//--------------------------------------------------
			// Variables

				$this->set('articles', $articles);

		}

	}

?>