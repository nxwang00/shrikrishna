<?php

class MailerLiteClient
{

    private $url;
    private $headers;

    /**
     * Client constructor
     *
     * @access      public
     * @return      void
     * @since       1.6.0
     */
    public function __construct($url, $headers)
    {

        $this->url = $url;
        $this->headers = $headers;
    }

    /**
     * Client for GET requests
     *
     * @access      public
     * @since       1.6.0
     */
    public function remote_get($endpoint, $args = [])
    {

        $args['body'] = $args;
        $args['headers'] = $this->headers;
        $args['timeout'] = 45;
        $args['user-agent'] = 'MailerLite WooCommerce/' . WOO_MAILERLITE_VER;

        return wp_remote_get($this->url.$endpoint, $args);
    }

    /**
     * Client for POST requests
     *
     * @access      public
     * @since       1.6.0
     */
    public function remote_post($endpoint, $args = [])
    {

        $params = [];
        $params['headers'] = $this->headers;
        $params['body'] = json_encode( $args );
        $params['timeout'] = 45;
        $params['user-agent'] = 'MailerLite WooCommerce/' . WOO_MAILERLITE_VER;

        return wp_remote_post($this->url.$endpoint, $params);
    }

    /**
     * Client for PUT requests
     *
     * @access      public
     * @since       1.6.0
     */
    public function remote_put($endpoint, $args = [])
    {

        $params = [];
        $params['method'] = 'PUT';
        $params['headers'] = $this->headers;
        $params['body'] = json_encode( $args );
        $params['timeout'] = 45;
        $params['user-agent'] = 'MailerLite WooCommerce/' . WOO_MAILERLITE_VER;

        return wp_remote_post($this->url.$endpoint, $params);
    }

    /**
     * Client for DELETE requests
     *
     * @access      public
     * @since       1.6.0
     */
    public function remote_delete($endpoint, $args = [])
    {

        $params = [];
        $params['method'] = 'DELETE';
        $params['headers'] = $this->headers;
        $params['body'] = json_encode( $args );
        $params['timeout'] = 45;
        $params['user-agent'] = 'MailerLite WooCommerce/' . WOO_MAILERLITE_VER;

        return wp_remote_post($this->url.$endpoint, $params);
    }
}