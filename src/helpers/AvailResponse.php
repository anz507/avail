<?php namespace Anzware\Avail\Helper;
/**
 * Common controller response for Avail
 *
 * @author Ahmad Anshori <anz507@gmail.com>
 */
use Response;

class AvailResponse
{
    /**
     * Status code
     *
     * @var int
     */
    protected $code;

    /**
     * Status of the response
     *
     * @var string
     */
    protected $status;

    /**
     * Response message
     *
     * @var string
     */
    protected $message;

    /**
     * The response data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Creating default value for properties
     */
    public function __construct()
    {
        $this->code     = 0;
        $this->status   = 'success';
        $this->message  = 'Request OK';
        $this->data     = NULL;
    }

    /**
     * Render the output
     *
     * @return Response
     */
    public function render()
    {
        $output = '';

        $json = new \stdClass();
        $json->code = $this->code;
        $json->status = $this->status;
        $json->message = $this->message;
        $json->data = $this->data;

        $output = json_encode($json);

        $headers = array('Content-Type' => 'application/json');

        return Response::make($output, 200, $headers);
    }
}
