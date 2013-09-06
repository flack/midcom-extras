<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * E-Mail import handler.
 *
 * This uses the OpenPSA 2 email importer MDA system. Emails are imported
 * into blog, with a possible attached image getting stored using 'image'
 * type in schema if available.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_api_email extends midcom_baseclasses_components_handler
{
    /**
     * The article to operate on
     *
     * @var midcom_db_article
     */
    private $_article;

    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     */
    private $_content_topic = null;

    /**
     * Email importer
     *
     * @var org_openpsa_mail
     */
    private $_decoder;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    private function _create_article($title)
    {
        $this->_article = new midcom_db_article();
        $author = $this->_find_email_person($this->_request_data['from']);
        if (!$author)
        {
            debug_add("Author '{$this->_request_data['from']}' not found", MIDCOM_LOG_WARN);
            if ($this->_config->get('api_email_abort_authornotfound') !== false)
            {
                throw new midcom_error("Author '{$this->_request_data['from']}' not found");
            }
            $this->_article->author = midcom_connection::get_user();
        }
        else
        {
            // TODO: This code needs a bit of rethinking
            $author_user = midcom::get('auth')->get_user($author->guid);
            if (!$this->_content_topic->can_do('midgard:create', $author_user))
            {
                throw new midcom_error('Author doesn\'t have posting privileges');
            }
            $this->_article->author = $author->id;
        }

        //Default to first user in DB if author is not set
        if (!$this->_article->author)
        {
            $qb = midcom_db_person::new_query_builder();
            $qb->add_constraint('username', '<>', '');
            $qb->set_limit(1);
            $results = $qb->execute();
            unset($qb);
            if (empty($results))
            {
                //No users found
                throw new midcom_error('Cannot set any author for the article');
            }
            $this->_article->author = $results[0]->id;
        }

        $resolver = new midcom_helper_reflector_nameresolver($this->_article);
        $this->_article->topic = $this->_content_topic->id;
        $this->_article->title = $title;
        $this->_article->allow_name_catenate = true;
        $this->_article->name = $resolver->generate_unique_name('title');
        if (empty($this->_article->name))
        {
            debug_add('Could not generate unique name for the new article from title, using timestamp', MIDCOM_LOG_INFO);
            $this->_article->name = time();
            $resolver = new midcom_helper_reflector_nameresolver($this->_article);

            if (!$resolver->name_is_unique())
            {
                throw new midcom_error('Failed to create unique name for the new article, aborting.');
            }
        }

        if (! $this->_article->create())
        {
            debug_print_r('Failed to create article:', $this->_article);
            throw new midcom_error('Failed to create a new article. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        $this->_article->set_parameter('midcom.helper.datamanager2', 'schema_name', $this->_config->get('api_email_schema'));

        return true;
    }

    /**
     * Internal helper, loads the datamanager for the current article. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);

        if (   ! $this->_datamanager->set_schema($this->_config->get('api_email_schema'))
            || ! $this->_datamanager->set_storage($this->_article))
        {
            throw new midcom_error("Failed to create a DM2 instance for article {$this->_article->id}.");
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_import($handler_id, array $args, array &$data)
    {
        if ($handler_id === 'api-email-basicauth')
        {
            midcom::get('auth')->require_valid_user('basic');
        }

        //Content-Type
        midcom::get()->skip_page_style = true;
        midcom::get('cache')->content->content_type('text/plain');

        if (!isset($this->_request_data['schemadb'][$this->_config->get('api_email_schema')]))
        {
            throw new midcom_error('Schema "' . $this->_config->get('api_email_schema') . '" not found in schemadb "' . $this->_config->get('schemadb') . '"');
        }
        $schema_instance =& $this->_request_data['schemadb'][$this->_config->get('api_email_schema')];

        // Parse email
        $this->_decode_email();
        $this->_parse_email_persons();

        midcom::get('auth')->request_sudo('net.nehmer.blog');

        // Create article
        $this->_create_article($this->_decoder->subject);

        // Load the article to DM2
        $this->_load_datamanager();

        // Find image and tag fields in schema
        foreach ($schema_instance->fields as $name => $field)
        {
            if (is_a($this->_datamanager->types[$name], 'midcom_helper_datamanager2_type_image'))
            {
                $this->_request_data['image_field'] = $name;
                continue;
            }

            if (is_a($this->_datamanager->types[$name], 'midcom_helper_datamanager2_type_tags'))
            {
                $data['tags_field'] = $name;
                continue;
            }
        }

        // Try to find tags in email content
        $content = $this->_decoder->body;
        $content_tags = '';
        if (class_exists('net_nemein_tag_handler'))
        {
            // unconditionally tag
            debug_add("content before machine tag separation\n===\n{$content}\n===\n");
            $content_tags = net_nemein_tag_handler::separate_machine_tags_in_content($content);
            if (!empty($content_tags))
            {
                debug_add("found machine tags string: {$content_tags}");
                net_nemein_tag_handler::tag_object($this->_article, net_nemein_tag_handler::string2tag_array($content_tags));
            }
            debug_add("content AFTER machine tag separation\n===\n{$content}\n===\n");
        }

        // Populate rest of the data
        $this->_datamanager->types['content']->value = $content;
        if (!empty($data['tags_field']))
        {
            // if we have tags field put content_tags value there as well or they will get deleted!
            $this->_datamanager->types[$data['tags_field']]->value = $content_tags;
        }
        $body_switched = false;

        foreach ($this->_decoder->attachments as $att)
        {
            debug_add("processing attachment {$att['name']}");

            switch (true)
            {
                case (strpos($att['mimetype'], 'image/') !== false):
                    $this->_add_image($att);
                    break;
                case (strtolower($att['mimetype']) == 'text/plain'):
                    if (!$body_switched)
                    {
                        // Use first text/plain part as the content
                        $this->_datamanager->types['content']->value = $att['content'];
                        $body_switched = true;
                        break;
                    }
                // TODO: Add generic attachment handling here
            }
        }

        if (!$this->_datamanager->save())
        {
            // Remove the article, but get errstr first
            $errstr = midcom_connection::get_error_string();
            $this->_article->delete();

            throw new midcom_error('DM2 failed to save the article. Last Midgard error was: ' . $errstr);
        }

        // Index the article
        $indexer = midcom::get('indexer');
        net_nehmer_blog_viewer::index($this->_datamanager, $indexer, $this->_content_topic);

        if ($this->_config->get('api_email_autoapprove'))
        {
            if (!$this->_article->metadata->force_approve())
            {
                // Remove the article, but get errstr first
                $errstr = midcom_connection::get_error_string();
                $this->_article->delete();

                throw new midcom_error('Failed to force approval on article. Last Midgard error was: ' . $errstr);
            }
        }

        midcom::get('auth')->drop_sudo();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_import($handler_id, array &$data)
    {
        //All done
        echo "OK\n";
    }

    private function _decode_email()
    {
        //Make sure we have the components we use and the Mail_mimeDecode package
        if (!class_exists('org_openpsa_mail_decoder'))
        {
            throw new midcom_error('library org.openpsa.mail could not be loaded.');
        }

        if (!class_exists('Mail_mimeDecode'))
        {
            throw new midcom_error('Cannot decode attachments, aborting.');
        }

        //Make sure the message_source is POSTed
        if (empty($_POST['message_source']))
        {
            throw new midcom_error('_POST[\'message_source\'] not present or empty.');
        }

        $this->_decoder = new org_openpsa_mail_decoder();
        $this->_decoder->mime_decode($_POST['message_source']);
    }

    private function _parse_email_persons()
    {
        //Parse email addresses
        $regex = '/<?([a-zA-Z0-9_.-]+?@[a-zA-Z0-9_.-]+)>?[ ,]?/';
        $emails = array();
        if (preg_match_all($regex, $this->_decoder->headers['To'], $matches_to))
        {
            foreach ($matches_to[1] as $email)
            {
                //Each address only once
                $emails[$email] = $email;
            }
        }
        if (preg_match_all($regex, $this->_decoder->headers['Cc'], $matches_cc))
        {
            foreach ($matches_cc[1] as $email)
            {
                //Each address only once
                $emails[$email] = $email;
            }
        }
        if (preg_match_all($regex, $this->_decoder->headers['From'], $matches_from))
        {
            foreach ($matches_from[1] as $email)
            {
                //Each address only once
                $emails[$email] = $email;
                //It's unlikely that we'd get multiple matches in From, but we use the latest
                $this->_request_data['from'] = $email;
            }
        }
    }

    private function _add_image($att)
    {
        if (!array_key_exists('image_field', $this->_request_data))
        {
            // No image fields in schema, TODO: revert to regular attachment handling
            return false;
        }

        // Save image to a temp file
        $tmp_name = tempnam(midcom::get('config')->get('midcom_tempdir'), 'net_nehmer_blog_handler_api_email_');
        $fp = fopen($tmp_name, 'w');

        if (!fwrite($fp, $att['content']))
        {
            //Could not write, clean up and continue
            debug_add("Error when writing file {$tmp_name}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            fclose($fp);
            return false;
        }

        return $this->_datamanager->types[$this->_request_data['image_field']]->set_image($att['name'], $tmp_name, $att['name']);
    }

    private function _find_email_person($email, $prefer_user = true)
    {
        // TODO: Use the new helpers for finding persons by email (a person might have multiple ones...)
        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('email', '=', $email);
        $results = $qb->execute();
        if (empty($results))
        {
            return false;
        }
        if ($prefer_user)
        {
            foreach ($results as $person)
            {
                if (!empty($person->username))
                {
                    return $person;
                }
            }
        }
        return $results[0];
    }
}
