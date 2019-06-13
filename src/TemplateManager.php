<?php


/*
*   Replace placeholders by text
*/

class TemplateManager
{

    protected $application_context;
    protected $destination_instance;
    protected $quote_instance;
    protected $site_instance;
    protected $quote;
    protected $user;
    protected $data;
    protected $text;

    public function __construct()
    {

        $this->application_context = ApplicationContext::getInstance();
        $this->destination_instance = DestinationRepository::getInstance();
        $this->quote_instance = QuoteRepository::getInstance();
        $this->site_instance = SiteRepository::getInstance();
    }


    /*
    *   $tpl  : object
    *   $data : array
    *
    *   return string
    *
    */
    public function getTemplateComputed(Template $tpl, array $data)
    {

        if (!$tpl){
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $this->data = $data;
        $this->getQuote();
        $this->getUser();

        $replaced->subject = $this->computeText($replaced->subject);
        $replaced->content = $this->computeText($replaced->content);

        return $replaced;
    }


    /*
    *   $text : string
    *
    *   return string
    */
    private function computeText($text)
    {

        if ($this->quote){

            // find destination link tag
            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = $this->destination_instance->getById($this->quote->destinationId);
            }

            // find & replace destination name tag
            if(strpos($text, '[quote:destination_name]') !== false){
                $text = str_replace('[quote:destination_name]',$this->destination_instance->getById($this->quote->destinationId)->countryName,$text);
            }

            // is summary html or text
            $is_html = strpos($text, '[quote:summary_html]');
            $is_text = strpos($text, '[quote:summary]');

            // replace summary tag by text & return as html
            if ($is_html !== false || $is_text !== false) {
                if ($is_html !== false) {
                    $text = $this->getSumary($text, '[quote:summary_html]');
                }
                if ($is_text !== false) {
                    $text = $this->getSumary($text, '[quote:summary]');
                }
            }
        }

        // replace destination link tag by url
        if (isset($destination)){
            $text = str_replace('[quote:destination_link]', $this->site_instance->getById($this->quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $this->quote_instance->getById($this->quote->id)->id, $text);
        }else{
            $text = str_replace('[quote:destination_link]', '', $text);
        }

        /*
         * USER
         * [user:*]
         */
        if($this->user) {
            if(strpos($text, '[user:first_name]') !== false){
                $text = str_replace('[user:first_name]', ucfirst(mb_strtolower($this->user->firstname)), $text);
            }
        }

        return $text;
    }

    /*
    *   populate $this->quote with $this->data['quote']
    */
    protected function getQuote()
    {
        if( isset($this->data['quote']) ){
            $this->quote = ($this->data['quote'] instanceof Quote) ? $this->data['quote'] : null;
        }

        return $this;
    }

    /*
    *   populate $this->user with $this->data['user']
    */
    protected function getUser()
    {
        $this->user  = (isset($this->data['user'])  and ($this->data['user']  instanceof User))  ? $this->data['user']  : $this->application_context->getCurrentUser();

        return $this;
    }

    /*
    *   $text : string
    *   $quote_type : string
    *
    *   return string
    */
    protected function getSumary($text, $quote_type)
    {

        $text = str_replace( $quote_type, Quote::renderHtml($this->quote_instance->getById($this->quote->id)), $text );

        return $text;
    }

}
