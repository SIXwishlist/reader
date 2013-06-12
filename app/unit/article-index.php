<?php

	class article_index_unit extends unit {

		public function setup($config = array()) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'read_url' => NULL,
					), $config);

			//--------------------------------------------------
			// Sources

				$sources = array();

				$db = db_get();

				$sql = 'SELECT
							s.ref,
							s.title,
							COUNT(sa.id) AS article_total,
							COUNT(sar.article_id) AS article_read
						FROM
							' . DB_PREFIX . 'source AS s
						LEFT JOIN
							' . DB_PREFIX . 'source_article AS sa ON sa.source_id = s.id
						LEFT JOIN
							' . DB_PREFIX . 'source_article_read AS sar ON sar.article_id = sa.id AND sar.user_id = "' . $db->escape(USER_ID) . '"
						WHERE
							s.deleted = "0000-00-00 00:00:00"
						GROUP BY
							s.id
						ORDER BY
							s.sort';

				foreach ($db->fetch_all($sql) as $row) {

					$unread_count = ($row['article_total'] - $row['article_read']);

					if ($unread_count > 0) {

						$sources[] = array(
								'url' => url('/articles/:source/', array('source' => $row['ref'])),
								'ref' => $row['ref'],
								'name' => $row['title'],
								'count' => $unread_count,
							);

					}

				}

			//--------------------------------------------------
			// Variables

				$this->set('sources', $sources);
				$this->set('read_url', $config['read_url']);

		}

	}

?>