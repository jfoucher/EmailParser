<?php

namespace MS\Email\Parser;

/**
 * @author msmith
 */
class Parser
{
    protected $parts;

    public function parse($email){
        $this->parts = $this->parseEMail($email);

        return new Message(
            new Address($this->getHeader('from')),
            new AddressCollection($this->getHeader('to')),
            new AddressCollection($this->getHeader('cc')),
            $this->getHeader('subject'),
            $this->getText(),
            $this->getHtml(),
            $this->getAttachments(),
            $this->getHeader('date')
        );
    }

    protected function getHtml()
    {
        if (!is_array($this->parts['body'])) {
            if (preg_match('/(\<html|\<body)/', $this->parts['body'])) {
                $p = new Part(
                    $this->parts['body'],
                    $this->getHeader('content-transfer-encoding') ? $this->getHeader('content-transfer-encoding') : null,
                    $this->getHeader('content-type') ? $this->getHeader('content-type') : null,
                    $this->getHeader('content-disposition') ? $this->getHeader('content-disposition') : null,
                    null
                );
                return $this->replaceInlineImages($p->getDecodedContent());
            }
            return false;
        }
        if ($r = $this->searchByHeader('/content\-type/', '/text\/html/')) {
            return $this->replaceInlineImages($r[0]->getDecodedContent());
        }

        return false;
    }



    protected function replaceInlineImages($html)
    {
        $inlineImages = $this->getInlineImages();
        $matches = array();
        preg_match('/src=["\']?cid:([^"\'\s]+)["\']?/m', $html, $matches);
        if (count($matches) > 0) {
            $inline = $inlineImages[$matches[1]];
            $html = preg_replace('/'.$matches[0].'/m', 'src="data:'.$inline->getMimeType().';charset=utf-8;base64,'.base64_encode($inline->getContent()).'"', $html);
        }

        return $html;
    }

    /**
     * @return Attachment[]|bool
     */
    protected function getInlineImages() {
        if(!is_array($this->parts['body'])){
            return false;
        }
        $inline = $this->searchByHeader('/content\-disposition/','/inline/');
        $inline = $inline ? $inline : array();
        $inlineObjects = array();
        foreach($inline as $in){
            /** @var \MS\Email\Parser\Part $in   */
            $matches = array();
            preg_match('/filename=([^;]*)/', $in->getDisposition(), $matches);
            $filename = trim($matches[1], "\" \t\r\n\0\x0B");

            $matches = array();
            preg_match('/([^;]*)/', $in->getType(), $matches);
            $mimeType = $matches[1];

            $inlineObjects[$in->getId()] = new Attachment($filename, $in->getDecodedContent(), $mimeType);
        }

        return $inlineObjects;
    }

    protected function getText()
    {
        if (!is_array($this->parts['body'])) {
            if (preg_match('/(\<html|\<body)/', $this->parts['body'])) {
                return false;
            }
            return $this->parts['body'];
        }
        if ($r = $this->searchByHeader('/content\-type/', '/text\/plain/')) {
            return $r[0]->getDecodedContent();
        }

        return false;
    }


    protected function getAttachments()
    {
        if (!is_array($this->parts['body'])) {
            return false;
        }
        $attachments = $this->searchByHeader('/content\-disposition/', '/attachment/');
        $attachments = $attachments ? $attachments : array();

        $attachmentObjects = array();
        foreach ($attachments as $attachment) {
            /** @var Part $attachment */
            $matches = array();
            preg_match('/filename=([^;]*)/', $attachment->getDisposition(), $matches);
            if (!isset($matches[1])) {
                // No match, try with utf-8 thing
                preg_match('/filename\*=utf-8\'\'([^;]*)/', $attachment->getDisposition(), $matches);
                if (!isset($matches[1])) {
                    throw new InvalidAttachmentException('Attachement is invalid ' . $attachment->getDisposition());
                }
            }
            $filename = trim($matches[1], "\" \t\r\n\0\x0B");

            $matches = array();
            preg_match('/([^;]*)/', $attachment->getType(), $matches);
            $mimeType = $matches[1];

            $attachmentObjects[] = new Attachment($filename, $attachment->getDecodedContent(), $mimeType);
        }

        return $attachmentObjects;
    }

    protected function getHeader($_key, $_head=false){
        if(!$_head){
            $_head = $this->parts['header'];
        }
        foreach($_head as $k=>$v){
            if($_key == $k) {
                return $v;
            }
        }
        return false;
    }

    protected function searchHeader($_key,$_val){
        if(empty($this->parts['header'])){
            throw new \Exception ('Email header is not there');
        }
        foreach($this->parts['header'] as $k=>$v){
            if(preg_match($_key,$k) && preg_match($_val,$v)){
                return array('key'=>$k,'value'=>$v);
            }
        }
        return false;
    }

    protected function getParsed(){
        return $this->parts;
    }

    /**
     * @param $_key
     * @param $_val
     * @param bool $_bd
     *
     * @return Part[]|bool
     */
    protected function searchByHeader($_key,$_val,$_bd=false){
        if(!$_bd){
            $_bd = $this->parts['body'];
        }
        if(!is_array($_bd)){
            return false;
        }

        $res = array();
        foreach($_bd as $k=>$v){
            if(!is_array($v)){
                continue;
            }
            if(!isset($v['header'])){
                continue;
            }
            foreach($v['header'] as $j=>$x){
                if(!preg_match($_key,$j) || !preg_match($_val,$x)){
                    continue;
                }
                $res[] = $v; break;
            }
            if(is_array($v['body']) && $r = $this->searchByHeader($_key,$_val,$v['body'])){
                $res += $r;
            }
        }

        $parts = array();
        foreach($res as $result){
            if($result instanceof Part){
                $parts[] = $result;
            }else{
                $parts[] = new Part(
                    $result['body'],
                    isset($result['header']['content-transfer-encoding']) ? $result['header']['content-transfer-encoding'] : null,
                    isset($result['header']['content-type']) ? $result['header']['content-type'] : null,
                    isset($result['header']['content-disposition']) ? $result['header']['content-disposition'] : null,
                    isset($result['header']['content-id']) ? str_replace(array('<', '>'), '',$result['header']['content-id']) : null
                );
            }
        }
        return $parts;
    }

    private function parseEmail($str){

        $str = preg_replace('/\r\n/',"\n",$str);
        $str = preg_replace('/\r/',"\n",$str);
        preg_match("/(.*?)\n\n(.*)/s",$str,$m);

        //parse header
        $h = $this->parseHeader($m[1]);

        //parse body
        $b = null;
        if($this->isMultipart($h)){
            $b = array();
            $bo = $this->getBound($h['content-type']);
            $bod = explode('--'.$bo, trim($m[2]));

            //figure out boundary end and pop the array if needed
            if(preg_match('`\-\-'.preg_quote($bo).'\-\-`',$m[2]))
                array_pop($bod);

            //delete first array, contains nothing.
            array_splice($bod,0,1);

            foreach($bod as $k=>$v){
                $b[] = $this->parseEmail($v);
            }
        } else {
            $b = preg_replace('/\n\n$/sD','',$m[2]);
        }

        return array("header" => $h, "body" => $b);
    }

    private function getBound($ct){
        $r = preg_match('/boundary\=(.*)/',$ct,$m);
        if(sizeof($m) < 2){
            return false;
        }
        $b = trim($m[1]);
        $b = preg_replace('/^("|\')/','', $b);
        $b = preg_replace('/(\'|")$/','', $b);
        return $b;
    }

    private function parseHeader($str){
        $str = explode("\n",preg_replace('/\n\s+/',' ',$str));
        $h = array();
        foreach($str as $k=>$v){
            if(!$v){
                continue;
            }
            $p=strpos($v,':');
            $h[ strtolower(substr($v,0,$p)) ] = trim(substr($v,$p+1));
        }
        return $h;

    }

    private function isMultipart($ct){
        if(!array_key_exists('content-type',$ct)){
            return false;
        }
        return preg_match('/multipart/iD',$ct['content-type']);
    }

}
