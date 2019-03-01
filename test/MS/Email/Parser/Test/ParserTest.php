<?php

namespace MS\Email\Parser\Test;

use MS\Email\Parser\Parser;

/**
 * @author msmith
 */
class ParserTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testParse($file, $fromName, $fromAddress, $toAddresses, $ccAddresses, $date, $subject, $textBody, $htmlBody, $attachments)
    {
        $p = new Parser();

        $m = $p->parse(file_get_contents(__DIR__ . '/files/' . $file . '.txt'));

        $this->assertEquals($fromName, $m->getFrom()->getName());
        $this->assertEquals($fromAddress, $m->getFrom()->getAddress());

        $this->assertCount(count($toAddresses), $m->getTo());
        foreach($toAddresses as $key => $toAddress){
            $this->assertEquals($toAddress[0], $m->getTo()->get($key)->getName());
            $this->assertEquals($toAddress[1], $m->getTo()->get($key)->getAddress());
        }

        $this->assertCount(count($ccAddresses), $m->getCC());
        foreach($ccAddresses as $key => $ccAddress){
            $this->assertEquals($ccAddress[0], $m->getCC()->get($key)->getName());
            $this->assertEquals($ccAddress[1], $m->getCC()->get($key)->getAddress());
        }

        $this->assertEquals($date, $m->getDate());
        $dateObj = $m->getDateAsDateTime();
        $this->assertEquals($date, $dateObj->format('D, j M Y H:i:s O'));

        $this->assertEquals($subject, $m->getSubject());

        $this->assertEquals($textBody, $m->getTextBody());

        $this->assertEquals($htmlBody, $m->getHtmlBody());

        $attachmentObjects = $m->getAttachments();
        $this->assertCount(count($attachments), $attachmentObjects);
        foreach($attachments as $key => $attachment){
            $this->assertEquals($attachment[0], $attachmentObjects[$key]->getFilename());
            $this->assertEquals($attachment[1], $attachmentObjects[$key]->getmimeType());
        }
    }

    public function provider()
    {
        return array(
            array(0, 'Pawel', 'pawel@test.com',
                array(
                    array('', 'dan@test.com')
                ),
                array(),
                'Tue, 28 Aug 2012 11:40:10 +0200',
                '=?UTF-8?B?emHFvMOzxYLEhyBnxJnFm2zEhSBqYcW6xYQgaSB6csOzYiBwcsOzYm4=?= =?UTF-8?B?ZSB6YWRhbmllICUldG9k?=',
                'This is just a test. I
Repeat just a test',
                '<html><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"></head><body
 style="font-family: tt; font-size: 10pt;" bgcolor="#FFFFFF"
text="#000000">
<div style="font-size: 10pt;font-family: tt;"><span style="font-family:
monospace;">This is just a test. I<br>Repeat just a test<br>Pawel<br></span></div>
</body>
</html>',
                array()
            ),
            array(1, 'Dan @ Test.com', 'dan@test.com',
                array(
                    array('Daniele', 'danielet@test.com')
                ),
                array(),
                'Tue, 18 Sep 2012 10:41:22 +0100',
                'this is a test',
                'Hope it works!',
                '',
                array()
            ),
            array(2, 'Dan', 'dan@test.com',
                array(
                    array('Daniele', 'daniele@test.com')
                ),
                array(),
                'Tue, 18 Sep 2012 11:26:11 +0100',
                '=?ISO-2022-JP?B?GyRCJDMkbCRPJUYlOSVIJEckORsoQg==?=',
                'それは作品を期待',
                'それは作品を期待<div title="signature"> </div>',
                array()
            ),
            array(3, 'Dan Occhi', 'dan@example.com',
                array(
                    array('Inbox_danocch.it_2063@examplebox.com', 'Inbox_danocch.it_2063@examplebox.com')
                ),
                array(),
                'Tue, 9 Oct 2012 18:23:09 +0100',
                'Voice Memo',
                '


Sent from my iPad
',
                '',
                array(
                    array('example_vmr_09102012182307.3gp', 'audio/caf')
                )
            ),
            array('gmail', 'Michael Smith', 'example@gmail.com',
                array(
                    array('', 'atapi@astrotraker.com')
                ),
                array(),
                'Wed, 30 Jan 2013 13:03:55 -0600',
                'gmail test',
                'Thanks,
Michael',
                '<div dir="ltr"><br clear="all"><div>Thanks,<div>Michael</div></div>
</div>',
                array(
                    array('e0t0tY2Py0WeHXw8qANI6A2 (1).jpg', 'image/jpeg')
                )
            ),
            array('mac_outlook', 'Cary Howell', 'example@gmail.com',
                array(
                    array('', 'atapi@astrotraker.com')
                ),
                array(),
                'Tue, 29 Jan 2013 15:54:48 -0500',
                'Test subject line',
                'Test body line.

Signature follows:
---
J. Cary Howell
e. example@gmail.com
http://www.web-developer.us


',
                '<html><head></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; color: rgb(0, 0, 0); font-size: 14px; font-family: Calibri, sans-serif; "><div><div><div>Test body line.</div><div><br></div><div>Signature follows:</div><div><div><div><span class="Apple-style-span" style="font-family: Courier; border-collapse: collapse; "><font class="Apple-style-span" size="2">---</font></span></div><div><span class="Apple-style-span" style="border-collapse: collapse; "><span class="Apple-style-span" style="font-family: Calibri; ">J. Cary Howell</span></span></div><span class="Apple-style-span" style="border-collapse: collapse; font-size: 12px; ">e. <a href="mailto:example@gmail.com">example@gmail.com</a>&nbsp;</span></div><div><span class="Apple-style-span" style="border-collapse: collapse; font-size: 12px; ">http://www.web-developer.us</span><div><blockquote style="margin: 0px 0px 0px 40px; border-style: none; padding: 0px; "></blockquote></div></div><div><br></div></div></div></div></body></html>',
                array()
            ),
            array('android', 'Michael Smith', 'example@textilemanagement.com',
                array(
                    array('atapi@astrotraker.com', 'atapi@astrotraker.com')
                ),
                array(),
                'Wed, 30 Jan 2013 17:25:07 -0500',
                'Tests',
                "\r\n\r\nSent from my Verizon Wireless 4GLTE smartphone\r\n\r\n",
                "\r\n<br><br>Sent from my Verizon Wireless 4GLTE smartphone<br><br>\r\n",
                array(
                    array('earth-moon-space-sun.jpg', 'application/octet-stream')
                )
            ),
            array('android2', 'Michael Smith', 'example@textilemanagement.com',
                array(
                    array('atapi@astrotraker.com', 'atapi@astrotraker.com'),
                    array('Michael Smith', 'example@textilemanagement.com'),
                ),
                array(
                    array('atapi@astrotraker.com', 'atapi2@astrotraker.com'),
                    array('Michael Smith', 'example2@textilemanagement.com'),
                ),
                'Wed, 30 Jan 2013 17:25:07 -0500',
                'Tests',
                "\r\n\r\nSent from my Verizon Wireless 4GLTE smartphone\r\n\r\n",
                "\r\n<br><br>Sent from my Verizon Wireless 4GLTE smartphone<br><br>\r\n",
                array()
            ),
             array('thunderbird', 'Michael Smith', 'example@textilemanagement.com',
                 array(
                     array('', 'atapi@astrotraker.com')
                 ),
                 array(),
                'Wed, 30 Jan 2013 16:18:32 -0600',
                'Fwd: test subject',
                '

--

Thanks,
Michael
',
                '<html>
  <head>

    <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
  </head>
  <body bgcolor="#FFFFFF" text="#000000">
    <br>
      <br>
      <pre>--

Thanks,
Michael


</pre>
      <br>
    </div>
    <br>
  </body>
</html>',
                array(
                    array('e0t0tY2Py0WeHXw8qANI6A2 (1).jpg', 'image/jpeg')
                )
            ),
//             array('ipad', 'Michael Smith', 'example@textilemanagement.com', 'atapi@astrotraker.com', 'atapi@astrotraker.com',
//                'Wed, 30 Jan 2013 17:46:37 -0500',
//                'Fwd: Tests',
//                "\r\n\r\nSent from my Verizon Wireless 4GLTE smartphone\r\n\r\n",
//                "\r\n<br><br>Sent from my Verizon Wireless 4GLTE smartphone<br><br>\r\n",
//                array(
//                    array('e0t0tY2Py0WeHXw8qANI6A2 (1).jpg', 'application/octet-stream')
//                )
//            ),
             array('ipad2', 'Cary Howell', 'example@textilemanagement.com',
                 array(
                     array('atapi@astrotraker.com', 'atapi@astrotraker.com')
                 ),
                 array(),
                'Thu, 31 Jan 2013 16:20:45 -0500',
                'Samples P200',
                'Test',
                '',
                array(
                    array('packing_slip.png', 'image/png'),
                    array('Screen shot 2013-01-31 at 9.21.59 AM.png', 'image/png'),
                    array('tracking_number.png', 'image/png'),
                )
            ),
        );
    }

    public function testReplaceInlineImages()
    {
        $raw = <<<'EOF'
To: test@test.com
From: Jonathan Foucher <jfoucher@6px.eu>
Subject: inline image test
Message-ID: <2706f77f-fae2-39a1-f598-b2e8a9f8f68a@6px.eu>
Date: Fri, 1 Mar 2019 11:57:56 +0100
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:60.0)
 Gecko/20100101 Thunderbird/60.5.1
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="------------87181B1275E41FD2CC8CC1B2"
Content-Language: en-US

This is a multi-part message in MIME format.
--------------87181B1275E41FD2CC8CC1B2
Content-Type: text/plain; charset=utf-8; format=flowed
Content-Transfer-Encoding: 7bit

icon

-- 
Jonathan Foucher
https://jfoucher.com
tel : 06 95 65 55 65


--------------87181B1275E41FD2CC8CC1B2
Content-Type: multipart/related;
 boundary="------------D98DD8DB12EEC2C7AEA95CDA"


--------------D98DD8DB12EEC2C7AEA95CDA
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: 7bit

<html>
  <head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  </head>
  <body text="#000000" bgcolor="#FFFFFF">
    <img moz-do-not-send="false"
      src="cid:part1.63FD9C4A.14C35126@6px.eu" alt="icon" width="2"
      height="2">
    <pre class="moz-signature" cols="72">-- 
Jonathan Foucher
<a class="moz-txt-link-freetext" href="https://jfoucher.com">https://jfoucher.com</a>
tel : 06 95 65 55 65</pre>
  </body>
</html>

--------------D98DD8DB12EEC2C7AEA95CDA
Content-Type: image/png;
 name="favicon.png"
Content-Transfer-Encoding: base64
Content-ID: <part1.63FD9C4A.14C35126@6px.eu>
Content-Disposition: inline;
 filename="favicon.png"

iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFklEQVR4AWPIX9IQMjOXoXBp
U9TsQgApjgXsTr1mYQAAAABJRU5ErkJggg==
--------------D98DD8DB12EEC2C7AEA95CDA--

--------------87181B1275E41FD2CC8CC1B2--
EOF;
        $parser = new Parser();
        $message = $parser->parse($raw);
        $expected = <<<'EOF'
<html>
  <head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  </head>
  <body text="#000000" bgcolor="#FFFFFF">
    <img moz-do-not-send="false"
      src="data:image/png;charset=utf-8;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFklEQVR4AWPIX9IQMjOXoXBpU9TsQgApjgXsTr1mYQAAAABJRU5ErkJggg==" alt="icon" width="2"
      height="2">
    <pre class="moz-signature" cols="72">-- 
Jonathan Foucher
<a class="moz-txt-link-freetext" href="https://jfoucher.com">https://jfoucher.com</a>
tel : 06 95 65 55 65</pre>
  </body>
</html>
EOF;
        $this->assertEquals($expected, $message->getHtmlBody());
    }
}
