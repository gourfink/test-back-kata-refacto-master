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

    public function __construct(){

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

        if (!$tpl) {
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

        if ($this->quote)
        {
            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = $this->destination_instance->getById($this->quote->destinationId);
            }

            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = $this->getText($text, '[quote:summary_html]');
                }
                if ($containsSummary !== false) {
                    $text = $this->getText($text, '[quote:summary]');
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$this->destination_instance->getById($this->quote->destinationId)->countryName,$text);
        }

        if (isset($destination)):
            $text = str_replace('[quote:destination_link]', $this->site_instance->getById($this->quote->siteId)->url . '/' . $destination->countryName . '/quote/' . $this->quote_instance->getById($this->quote->id)->id, $text);
        else:
            $text = str_replace('[quote:destination_link]', '', $text);
        endif;

        /*
         * USER
         * [user:*]
         */
        if($this->user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]', ucfirst(mb_strtolower($this->user->firstname)), $text);
        }

        return $text;
    }

    /*
    *   populate $this->quote with $this->data['quote']
    */
    protected function getQuote()
    {

        $this->quote = (isset($this->data['quote']) and $this->data['quote'] instanceof Quote) ? $this->data['quote'] : null;

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
    public function getText($text, $quote_type)
    {

        $text = str_replace( $quote_type, Quote::renderHtml($this->quote_instance->getById($this->quote->id)), $text );

        return $text;
    }
}
