'api-email' => Array
(
    'handler' => array('net_nehmer_blog_handler_api_email', 'import'),
    'fixed_args' => array('api', 'email'),
),
'api-email-basicauth' => Array
(
    'handler' => array('net_nehmer_blog_handler_api_email', 'import'),
    'fixed_args' => array('api', 'email_basicauth'),
),

// Match /wiki_email_import
'wiki_email_import' => array
(
    'handler' => Array('net_nemein_wiki_handler_emailimport', 'emailimport'),
    'fixed_args' => 'wiki_email_import',
),
