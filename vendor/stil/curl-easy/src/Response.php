<?php
namespace cURL;

class Response
{
    protected $ch;
    protected $error;
    protected $content = null;
    protected $cookies = [];
    protected $request;
    protected $headers = [];
    
    /**
     * Constructs response
     * 
     * @param Request $request Request
     * @param string  $content Content of reponse
     */
    public function __construct(Request $request, $content = null, $headers = [])
    {
        $this->ch = $request->getHandle();
        $this->request = $request;
        $this->header = $headers;

        if(!is_null($content)){
            preg_match_all('/^Set-Cookie: \s*([^;]*)/mi', $content, $matches);
            foreach($matches[1] as $item){
                parse_str($item, $cookie);
                $this->cookies = array_merge($this->cookies, $cookie);
            }
        }
        
        if (is_string($content)) {
            $this->content = $content;
        }
    }
    
    /**
     * Get information regarding a current transfer
     * If opt is given, returns its value as a string
     * Otherwise, returns an associative array with
     * the following elements (which correspond to opt), or FALSE on failure.
     *
     * @param int $key One of the CURLINFO_* constants
     * @return mixed
     */
    public function getInfo($key = null)
    {
        return $key === null ? curl_getinfo($this->ch) : curl_getinfo($this->ch, $key);
    }
    
    /**
     * Returns content of request
     * 
     * @return string    Content
     */
    public function getContent()
    {
        return $this->content;
    }

    public function getHeaders(){
        return $this->headers;
    }

    public function getHandle() {
        return $this->ch;
    }

    public function getRequest(){
        $this->request;
    }
    
    /**
     * Sets error instance
     * 
     * @param Error $error Error to set
     * @return void
     */
    public function setError(Error $error)
    {
        $this->error = $error;
    }
    
    /**
     * Returns a error instance
     * 
     * @return Error|null
     */
    public function getError()
    {
        return isset($this->error) ? $this->error : null;
    }
    
    /**
     * Returns the error number for the last cURL operation.    
     * 
     * @return int  Returns the error number or 0 (zero) if no error occurred. 
     */
    public function hasError()
    {
        return isset($this->error);
    }

    public function closeConnection(){
        // if (isset($this->ch)) {
            curl_close($this->ch);
        // }
    }
}
