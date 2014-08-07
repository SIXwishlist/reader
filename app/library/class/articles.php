<?php

	class articles extends check {

		static function update($condition = NULL) {

			//--------------------------------------------------
			// Config

				$db = db_get();

			//--------------------------------------------------
			// Condition

				if (is_numeric($condition)) { // A particular source

					$where_sql = '
						s.id = "' . $db->escape($condition) . '" AND
						s.deleted = "0000-00-00 00:00:00"';

				} else if ($condition === true) { // All sources

					$where_sql = '
						s.deleted = "0000-00-00 00:00:00"';

				} else { // Those not recently updated

					$where_sql = '
						s.updated    <= "' . $db->escape(date('Y-m-d H:i:s', strtotime('-10 minutes'))) . '" AND
						s.error_date <= "' . $db->escape(date('Y-m-d H:i:s', strtotime('-1 hour'))) . '" AND
						s.deleted = "0000-00-00 00:00:00"';

				}

			//--------------------------------------------------
			// For each source

				$sql = 'SELECT
							s.id,
							s.url_feed,
							s.article_count
						FROM
							' . DB_PREFIX . 'source AS s
						WHERE
							' . $where_sql;

				foreach ($db->fetch_all($sql) as $row) {

					//--------------------------------------------------
					// Details

						$error = false;

						$source_id = $row['id'];
						$source_url = $row['url_feed'];
						$source_articles = array();

						$article_count = intval($row['article_count']);
						if ($article_count < 30) {
							$article_count = 30;
						}
						$article_count += 10; // Bit of tolerance

					//--------------------------------------------------
					// Delete old articles

						$db->query('DELETE FROM
										' . DB_PREFIX . 'source_article
									WHERE
										id IN (
												SELECT
													*
												FROM (
														SELECT
															sa.id
														FROM
															' . DB_PREFIX . 'source_article AS sa
														LEFT JOIN
															' . DB_PREFIX . 'source_article_read AS sar ON sar.article_id = sa.id
														WHERE
															sa.source_id = "' . $db->escape($source_id) . '" AND
															sar.read_date <= "' . $db->escape(date('Y-m-d H:i:s', strtotime('-2 weeks'))) . '" AND
															sar.read_date IS NOT NULL
														ORDER BY
															sar.read_date DESC
														LIMIT
															' . intval($article_count) . ', 100000
													) AS x
											)');

							// Extra sub query required due to lack of support for "LIMIT" with "IN" (feature to be added to MySQL later).

							// Delete by "sar.read_date" (not "sa.published"), as websites like Coding Horror like to change their GUID.

					//--------------------------------------------------
					// Get XML ... don't do directly in simple xml as
					// FeedBurner has issues

						$headers = array(
								'User-Agent: RSS Reader',
								'Accept: application/rss+xml',
							);

						$context = stream_context_create(array(
								'http' => array(
										'method' => 'GET',
										'header' => implode("\r\n", $headers) . "\r\n",
									)
							));

						$rss_data = @file_get_contents($source_url, false, $context);

						if (trim($rss_data) == '') {
							$error = 'Cannot return feed';
						}

					//--------------------------------------------------
					// Parse XML

						if (!$error) {

							$rss_data = str_replace(' & ', ' &amp; ', $rss_data); // Try to cleanup bad XML (e.g. ampersand in <title>)

							$rss_xml = @simplexml_load_string($rss_data);

							if ($rss_xml === false) {
								$error = 'Cannot parse feed';
							}

						}

					//--------------------------------------------------
					// Extract articles

						if (!$error) {

							if (isset($rss_xml->channel->item)) { // RSS

								foreach ($rss_xml->channel->item as $item) {

									$description = strval($item->children('content', true)); // Namespaced <content:encoded> tag
									if ($description == '') {
										$description = strval($item->description);
									}

									$published = strval($item->pubDate);
									if ($published == '') {
										$dc_node = $item->children('dc', true);
										if ($dc_node) {
											$published = strval($dc_node->date); // Namespaced <dc:date> tag
										}
									}

									$guid = strval($item->guid);
									if ($guid == '') {
										$guid = md5($item->link);
									}

									$source_articles[] = array(
											'guid'        => $guid,
											'title'       => strval($item->title),
											'link'        => strval($item->link),
											'description' => $description,
											'published'   => $published,
										);

								}

							} else if (isset($rss_xml->entry)) { // Atom

								foreach ($rss_xml->entry as $entry) {

									if ($entry->content) {
										$description = strval($entry->content);
									} else {
										$description = strval($entry->summary);
									}

									$published = strval($entry->published);
									if ($published == '') {
										$published = strval($entry->updated);
									}

									$url = '';
									if (count($entry->link) > 1) { // ref "Chromium Blog"
										foreach ($entry->link as $link) {
											if ((!isset($link['type']) || $link['type'] != 'application/atom+xml') && (!isset($link['rel']) || $link['rel'] != 'replies')) {
												$url = strval($link['href']);
											}
										}
									} else {
										$url = strval($entry->link['href']);
									}

									$source_articles[] = array(
											'guid'        => strval($entry->id),
											'title'       => strval($entry->title),
											'link'        => $url,
											'description' => $description,
											'published'   => $published,
										);

								}

							} else {

								$error = 'Unknown feed format';

							}

							if (!$error && count($source_articles) == 0) {

								$error = 'No articles found';

							}

						}

					//--------------------------------------------------
					// Add articles

						foreach ($source_articles as $article) {

							//--------------------------------------------------
							// Insert and update values

								$article['title'] = html_decode($article['title']);

								$values_update = $article;
								$values_update['source_id'] = $source_id;
								$values_update['updated'] = date('Y-m-d H:i:s');

								$values_insert = $values_update;
								$values_insert['created'] = date('Y-m-d H:i:s');

							//--------------------------------------------------
							// Published date

								$published = strtotime($article['published']);

								if ($published === false) {

									$values_insert['published'] = date('Y-m-d H:i:s');

									unset($values_update['published']);

								} else {

									$values_insert['published'] = date('Y-m-d H:i:s', $published);
									$values_update['published'] = date('Y-m-d H:i:s', $published);

								}

							//--------------------------------------------------
							// Store

								$db->insert(DB_PREFIX . 'source_article', $values_insert, $values_update);

						}

					//--------------------------------------------------
					// Record as updated

						if ($error) {
							$values = array(
									'error_text' => $error,
									'error_date' => date('Y-m-d H:i:s'),
								);
						} else {
							$values = array(
									'article_count' => count($source_articles),
									'updated' => date('Y-m-d H:i:s'),
								);
						}

						$where_sql = '
							id = "' . $db->escape($source_id) . '" AND
							deleted = "0000-00-00 00:00:00"';

						$db->update(DB_PREFIX . 'source', $values, $where_sql);

				}

		}

	}

?>